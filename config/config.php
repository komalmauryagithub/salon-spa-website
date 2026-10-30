<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Local Environment Configuration
 * 
 * Target DBMS: MySQL 5.7+ / MySQL 8.0+ / MariaDB (XAMPP default)
 * This configuration is protected against direct web access by config/.htaccess
 */

return [
    // Application Environment: 'development' | 'production'
    'app_env' => $_ENV['APP_ENV'] ?? getenv('APP_ENV') ?: 'development',
    
    // Debug Mode: false in production, true in local development
    'app_debug' => filter_var($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOLEAN),
    
    // Local application base URL
    'app_url' => $_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'http://localhost/Hair%20salon%20and%20spa',
    
    // Standard Timezone
    'timezone' => $_ENV['APP_TIMEZONE'] ?? getenv('APP_TIMEZONE') ?: 'America/New_York',
    
    // Database Credentials (XAMPP Defaults: host 127.0.0.1, user 'root', password '')
    'database' => [
        'host'     => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => (int)($_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306),
        'database' => $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'letoile_salon_db',
        'username' => $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root',
        'password' => $_ENV['DB_PASS'] ?? (getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'root'),
        'charset'  => 'utf8mb4',
        'collation'=> 'utf8mb4_unicode_ci',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ],
    ],
    
    // Secure Session Configuration
    'session' => [
        'name'            => 'LETOILE_SESSID',
        'lifetime'        => 7200, // 2 hours
        'cookie_path'     => '/',
        'cookie_domain'   => '',
        'cookie_secure'   => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],
    
    // Concierge & Brand Constants
    'concierge' => [
        'salon_name'    => "L'Étoile Luxury Unisex Hair Salon & Spa",
        'contact_email' => 'concierge@letoile-salon-placeholder.com',
        'phone'         => '+1 (800) 555-0192',
    ]
];
