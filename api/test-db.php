<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Database Diagnostics Endpoint
 * 
 * Safely verifies PDO connection and schema readiness without leaking credentials.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

try {
    $pdo = Database::getConnection();
    
    // Check tables in letoile_salon_db
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    $driverName = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

    Response::success("Salon database connection established successfully.", [
        'status'         => 'connected',
        'driver'         => $driverName,
        'server_version' => $serverVersion,
        'database'       => 'letoile_salon_db',
        'tables_found'   => count($tables),
        'tables_list'    => $tables,
        'environment'    => $config['app_env'] ?? 'development',
    ]);
} catch (DatabaseException $e) {
    Response::error($e->getMessage(), [
        'status'      => 'disconnected',
        'suggestion'  => "Ensure MySQL service is started in XAMPP Control Panel and database.sql has been imported into phpMyAdmin."
    ], 503);
} catch (Exception $e) {
    Response::error("Unexpected diagnostic error occurred.", [], 500);
}
