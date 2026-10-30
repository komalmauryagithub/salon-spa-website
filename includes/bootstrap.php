<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Core Backend Bootstrap Engine
 * 
 * Initializes session security, error handling, logging, and core utilities.
 */

// 0. Auto-load .env environment file if present
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $envLine) {
        $envLine = trim($envLine);
        if ($envLine === '' || str_starts_with($envLine, '#')) {
            continue;
        }
        if (str_contains($envLine, '=')) {
            [$envKey, $envVal] = explode('=', $envLine, 2);
            $envKey = trim($envKey);
            $envVal = trim($envVal);
            if ((str_starts_with($envVal, '"') && str_ends_with($envVal, '"')) ||
                (str_starts_with($envVal, "'") && str_ends_with($envVal, "'"))) {
                $envVal = substr($envVal, 1, -1);
            }
            putenv("{$envKey}={$envVal}");
            $_ENV[$envKey] = $envVal;
            $_SERVER[$envKey] = $envVal;
        }
    }
}

// 1. Load Configuration
$configFile = __DIR__ . '/../config/config.php';
$exampleConfigFile = __DIR__ . '/../config/config.example.php';

if (file_exists($configFile)) {
    $config = require $configFile;
} elseif (file_exists($exampleConfigFile)) {
    $config = require $exampleConfigFile;
} else {
    $config = [
        'app_env'   => 'development',
        'app_debug' => true,
        'timezone'  => 'America/New_York'
    ];
}

// 2. Set Default Timezone
$timezone = $config['timezone'] ?? 'America/New_York';
date_default_timezone_set($timezone);

// 3. Load Core Classes & Utilities
require_once __DIR__ . '/logger.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/response.php';
require_once __DIR__ . '/validator.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/scheduler.php';
require_once __DIR__ . '/settings.php';

// 4. Secure Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    $sessConfig = $config['session'] ?? [];
    
    // PHP ini session directives
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_trans_sid', '0');
    ini_set('session.cookie_httponly', '1');
    
    $lifetime = $sessConfig['lifetime'] ?? 7200;
    $path     = $sessConfig['cookie_path'] ?? '/';
    $domain   = $sessConfig['cookie_domain'] ?? '';
    $secure   = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $httponly = true;
    $samesite = $sessConfig['cookie_samesite'] ?? 'Lax';

    // Set cookie parameters with SameSite support (PHP 7.3+)
    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => $path,
        'domain'   => $domain,
        'secure'   => $secure,
        'httponly' => $httponly,
        'samesite' => $samesite,
    ]);

    session_name($sessConfig['name'] ?? 'LETOILE_SESSID');
    @session_start();
}

// 5. Global Error & Exception Handling
$isDebug = !empty($config['app_debug']);

if ($isDebug) {
    error_reporting(E_ALL);
    ini_set('display_errors', '0'); // Keep off in output; custom handler formats cleanly
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

// Custom Error Handler to log notices/warnings
set_error_handler(function(int $errno, string $errstr, string $errfile, int $errline) use ($isDebug) {
    if (!(error_reporting() & $errno)) {
        return false;
    }

    $severity = 'NOTICE';
    switch ($errno) {
        case E_USER_ERROR:
        case E_RECOVERABLE_ERROR:
            $severity = 'ERROR';
            break;
        case E_WARNING:
        case E_USER_WARNING:
            $severity = 'WARNING';
            break;
    }

    AppLogger::warning("PHP {$severity}: {$errstr}", [
        'file' => basename($errfile),
        'line' => $errline
    ]);

    // Treat fatal errors as exceptions
    if ($errno === E_USER_ERROR || $errno === E_RECOVERABLE_ERROR) {
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    return true;
});

// Custom Exception Handler to prevent leaking database credentials or stack traces
set_exception_handler(function(Throwable $e) use ($isDebug) {
    // Log full trace securely to logs/app.log
    AppLogger::error("Uncaught Exception: " . $e->getMessage(), [
        'class' => get_class($e),
        'file'  => $e->getFile(),
        'line'  => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);

    $isApi = (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) || 
             (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

    if ($isApi) {
        $message = $isDebug ? $e->getMessage() : "A server processing error occurred. The atelier engineering team has been notified.";
        Response::error($message, [], 500);
    } else {
        http_response_code(500);
        echo "<!DOCTYPE html>
        <html lang='en'>
        <head>
          <meta charset='UTF-8'>
          <title>500 — Atelier Service Interruption | L'Étoile</title>
          <style>
            body { background-color: #F5EFE6; color: #252321; font-family: 'Plus Jakarta Sans', sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
            .card { background: #FFFFFF; border: 1px solid rgba(197, 164, 109, 0.35); padding: 40px; border-radius: 4px; max-width: 520px; text-align: center; box-shadow: 0 10px 30px rgba(0,0,0,0.05); }
            h1 { font-family: 'Cormorant Garamond', Georgia, serif; font-size: 2.2rem; margin-bottom: 12px; color: #252321; }
            p { color: #5E5853; line-height: 1.6; margin-bottom: 24px; }
            .btn { display: inline-block; background-color: #252321; color: #F5EFE6; padding: 12px 28px; text-decoration: none; font-size: 0.85rem; letter-spacing: 0.1em; text-transform: uppercase; font-weight: 600; border-radius: 2px; }
          </style>
        </head>
        <body>
          <div class='card'>
            <h1>Atelier Service Interruption</h1>
            <p>Our concierge system encountered an unexpected condition. Your connection details and personal data remain fully secure.</p>
            <a href='index.html' class='btn'>Return to Sanctuary</a>
          </div>
        </body>
        </html>";
        exit;
    }
});
