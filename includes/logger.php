<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Application Logger Utility
 * 
 * Secure logging to logs/app.log without exposing sensitive credentials or passwords.
 */

class AppLogger {
    private const LOG_FILE = __DIR__ . '/../logs/app.log';

    /**
     * Log an informational message
     */
    public static function info(string $message, array $context = []): void {
        self::write('INFO', $message, $context);
    }

    /**
     * Log a warning message
     */
    public static function warning(string $message, array $context = []): void {
        self::write('WARNING', $message, $context);
    }

    /**
     * Log an error message
     */
    public static function error(string $message, array $context = []): void {
        self::write('ERROR', $message, $context);
    }

    /**
     * Internal method to write formatted log entry
     */
    private static function write(string $level, string $message, array $context = []): void {
        $logDir = dirname(self::LOG_FILE);
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        // Sanitize context to prevent leaking passwords, tokens, or credentials
        $sanitizedContext = self::sanitizeContext($context);

        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $contextString = !empty($sanitizedContext) ? ' ' . json_encode($sanitizedContext, JSON_UNESCAPED_SLASHES) : '';
        $logLine = sprintf("[%s] [%s] [%s] %s%s%s", $timestamp, $level, $ip, $message, $contextString, PHP_EOL);

        @file_put_contents(self::LOG_FILE, $logLine, FILE_APPEND | LOCK_EX);
    }

    /**
     * Strip sensitive keys from context array
     */
    private static function sanitizeContext(array $context): array {
        $sensitiveKeys = ['password', 'password_hash', 'password_confirm', 'token', 'csrf_token', 'credit_card', 'cvv', 'secret'];
        
        foreach ($context as $key => $val) {
            if (is_string($key) && in_array(strtolower($key), $sensitiveKeys, true)) {
                $context[$key] = '***REDACTED***';
            } elseif (is_array($val)) {
                $context[$key] = self::sanitizeContext($val);
            }
        }
        
        return $context;
    }
}
