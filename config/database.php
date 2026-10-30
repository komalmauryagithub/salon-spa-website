<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Reusable PDO Database Connection Manager
 * 
 * Provides a resilient singleton PDO instance with strict prepared statements,
 * error handling, and credential masking.
 */

// Custom Exception for clean, sanitized database errors
class DatabaseException extends Exception {}

class Database {
    /** @var PDO|null Cached PDO connection instance */
    private static ?PDO $instance = null;
    
    /** @var array Cached configuration array */
    private static ?array $config = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {}

    /**
     * Get the singleton PDO connection instance
     * 
     * @return PDO
     * @throws DatabaseException
     */
    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $config = self::getConfig();
        $db = $config['database'] ?? [];

        $host     = $db['host'] ?? '127.0.0.1';
        $port     = $db['port'] ?? 3306;
        $dbname   = $db['database'] ?? 'letoile_salon_db';
        $charset  = $db['charset'] ?? 'utf8mb4';
        $username = $db['username'] ?? 'root';
        $password = $db['password'] ?? '';
        $options  = $db['options'] ?? [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        try {
            self::$instance = new PDO($dsn, $username, $password, $options);
            return self::$instance;
        } catch (PDOException $e) {
            // Intelligent local fallback: if username is 'root' and 1045 access denied, try alternate common local password ('root' <-> '')
            if ((int)$e->getCode() === 1045 && $username === 'root') {
                $altPassword = ($password === 'root') ? '' : 'root';
                try {
                    self::$instance = new PDO($dsn, $username, $altPassword, $options);
                    return self::$instance;
                } catch (PDOException $altEx) {
                    // Fall through to primary error logging
                }
            }

            // Mask password and sensitive info in logger
            if (class_exists('AppLogger')) {
                AppLogger::error("Database Connection Failure: " . $e->getMessage(), [
                    'host'     => $host,
                    'port'     => $port,
                    'database' => $dbname,
                    'user'     => $username,
                ]);
            }

            // Sanitized public exception without leaking credentials
            $errorMessage = "Unable to connect to the salon database. Please verify MySQL service is running in XAMPP and 'letoile_salon_db' has been imported.";
            
            // In debug mode, include the driver code but never the password
            if (!empty($config['app_debug'])) {
                $errorMessage .= " [Driver Code: " . $e->getCode() . "]";
            }

            throw new DatabaseException($errorMessage, (int)$e->getCode());
        }
    }

    /**
     * Helper to test if database connection succeeds
     * 
     * @return bool
     */
    public static function isConnected(): bool {
        try {
            self::getConnection();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Helper to close/reset connection if needed
     */
    public static function closeConnection(): void {
        self::$instance = null;
    }

    /**
     * Load configuration safely
     * 
     * @return array
     */
    public static function getConfig(): array {
        if (self::$config !== null) {
            return self::$config;
        }

        $configPath = __DIR__ . '/config.php';
        $examplePath = __DIR__ . '/config.example.php';

        if (file_exists($configPath)) {
            self::$config = require $configPath;
        } elseif (file_exists($examplePath)) {
            self::$config = require $examplePath;
        } else {
            self::$config = [
                'database' => [
                    'host' => '127.0.0.1',
                    'port' => 3306,
                    'database' => 'letoile_salon_db',
                    'username' => 'root',
                    'password' => '',
                    'charset' => 'utf8mb4'
                ],
                'app_debug' => true
            ];
        }

        return self::$config;
    }
}
