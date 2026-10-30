<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Cross-Site Request Forgery (CSRF) Protection
 * 
 * Provides cryptographically secure token generation and constant-time verification.
 */

class Csrf {
    private const SESSION_KEY = '_csrf_token';

    /**
     * Get or generate the active CSRF token
     */
    public static function token(): string {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (empty($_SESSION[self::SESSION_KEY]) || !is_string($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(32));
        }

        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Render an HTML hidden input containing the CSRF token
     */
    public static function field(): string {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }

    /**
     * Verify an incoming token using constant-time comparison
     */
    public static function verify(?string $token): bool {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? '';
        if (empty($sessionToken) || empty($token)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Extract token from POST payload or HTTP header
     */
    public static function getSubmittedToken(): ?string {
        if (!empty($_POST['csrf_token']) && is_string($_POST['csrf_token'])) {
            return $_POST['csrf_token'];
        }

        $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!empty($headerToken) && is_string($headerToken)) {
            return $headerToken;
        }

        // Also check raw JSON input if Content-Type is application/json
        $raw = file_get_contents('php://input');
        if (!empty($raw)) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded) && !empty($decoded['csrf_token'])) {
                return (string)$decoded['csrf_token'];
            }
        }

        return null;
    }

    /**
     * Enforce CSRF check or abort with error
     */
    public static function validateOrFail(): void {
        $token = self::getSubmittedToken();
        if (!self::verify($token)) {
            if (class_exists('AppLogger')) {
                AppLogger::warning('CSRF Token Validation Failed', [
                    'ip'      => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
                    'uri'     => $_SERVER['REQUEST_URI'] ?? '',
                    'method'  => $_SERVER['REQUEST_METHOD'] ?? '',
                ]);
            }

            if (class_exists('Response')) {
                Response::error('Invalid or expired security token. Please refresh the page and try again.', [
                    'csrf_token' => 'CSRF verification failed'
                ], 403);
            } else {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'error' => 'Security token invalid or expired. Please refresh the page.'
                ]);
                exit;
            }
        }
    }
}

/**
 * Procedural helper functions
 */
function csrf_token(): string {
    return Csrf::token();
}

function csrf_field(): string {
    return Csrf::field();
}

function verify_csrf_token(?string $token): bool {
    return Csrf::verify($token);
}
