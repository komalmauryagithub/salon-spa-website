<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Response & Flash Messaging Utility
 * 
 * Standardizes API responses and redirects with structured JSON envelopes.
 */

class Response {
    /**
     * Send standard JSON response and terminate execution
     */
    public static function json(array $payload, int $statusCode = 200): void {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-cache, no-store, must-revalidate');
        }

        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Send success JSON response
     */
    public static function success(string $message, array $extra = [], int $statusCode = 200): void {
        self::json(array_merge([
            'success' => true,
            'message' => $message,
            'timestamp' => date('c'),
        ], $extra), $statusCode);
    }

    /**
     * Send error JSON response
     */
    public static function error(string $message, array $errors = [], int $statusCode = 400): void {
        self::json([
            'success' => false,
            'error'   => $message,
            'errors'  => $errors,
            'timestamp' => date('c'),
        ], $statusCode);
    }

    /**
     * Redirect to another URL with optional flash message
     */
    public static function redirect(string $url, ?string $flashMessage = null, string $flashType = 'success'): void {
        if ($flashMessage !== null) {
            set_flash($flashType, $flashMessage);
        }

        if (!headers_sent()) {
            header("Location: {$url}");
            exit;
        }

        echo "<script>window.location.href=" . json_encode($url) . ";</script>";
        exit;
    }
}

/**
 * Flash message helpers stored in session
 */
function set_flash(string $type, string $message): void {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $_SESSION['_flash'] = [
        'type'    => $type,
        'message' => $message,
    ];
}

function get_flash(): ?array {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    if (isset($_SESSION['_flash'])) {
        $flash = $_SESSION['_flash'];
        unset($_SESSION['_flash']);
        return $flash;
    }
    return null;
}

function has_flash(): bool {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    return isset($_SESSION['_flash']);
}
