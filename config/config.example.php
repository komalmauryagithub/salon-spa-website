<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Safe Local Configuration Template
 * 
 * Copy this file to 'config.php' and customize for your local or production environment.
 * NEVER commit 'config.php' containing real production secrets to version control.
 */

return [
    // Application Environment: 'development', 'staging', or 'production'
    'app_env' => getenv('APP_ENV') ?: 'development',
    
    // Debug Mode: Set to false in production to prevent stack trace leaks
    'app_debug' => filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOLEAN),
    
    // Application URL (Used for absolute redirects and email links)
    'app_url' => getenv('APP_URL') ?: 'http://localhost/Hair%20salon%20and%20spa',
    
    // Default Timezone
    'timezone' => getenv('APP_TIMEZONE') ?: 'America/New_York',
    
    // Database Configuration (Default XAMPP MySQL Settings)
    'database' => [
        'host'     => getenv('DB_HOST') ?: '127.0.0.1',
        'port'     => (int)(getenv('DB_PORT') ?: 3306),
        'database' => getenv('DB_NAME') ?: 'letoile_salon_db',
        'username' => getenv('DB_USER') ?: 'root',
        'password' => getenv('DB_PASS') !== false ? getenv('DB_PASS') : '', // XAMPP default is blank
        'charset'  => 'utf8mb4',
        'collation'=> 'utf8mb4_unicode_ci',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ],
    ],
    
    // Session Security Configuration
    'session' => [
        'name'            => 'LETOILE_SESSID',
        'lifetime'        => 7200, // 2 hours
        'cookie_path'     => '/',
        'cookie_domain'   => '',
        'cookie_secure'   => false, // Set to true if running under HTTPS/SSL
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ],
    
    // Salon Contact & Notification Settings (Placeholders for Phase 4)
    'concierge' => [
        'salon_name'    => "L'Étoile Luxury Unisex Hair Salon & Spa",
        'contact_email' => 'concierge@letoile-salon-placeholder.com',
        'phone'         => '+1 (800) 555-0192',
    ]
];
