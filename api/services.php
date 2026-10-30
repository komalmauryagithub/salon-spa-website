<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Services Catalog API
 * 
 * Provides categorized service data for dynamic rendering and booking options.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

try {
    $pdo = Database::getConnection();

    $catStmt = $pdo->query("SELECT id, slug, name, description FROM service_categories WHERE is_active = 1 ORDER BY display_order ASC");
    $categories = $catStmt->fetchAll();

    $srvStmt = $pdo->query("
        SELECT s.id, s.category_id, s.slug, s.name, s.short_description, s.gender_suitability, s.duration_minutes, s.price, s.image_url, s.is_featured, c.slug as category_slug
        FROM services s
        JOIN service_categories c ON s.category_id = c.id
        WHERE s.is_active = 1
        ORDER BY s.display_order ASC
    ");
    $services = $srvStmt->fetchAll();

    Response::success("Service catalog retrieved successfully.", [
        'count'      => count($services),
        'categories' => $categories,
        'services'   => $services,
        'source'     => 'mysql'
    ]);
} catch (Exception $e) {
    AppLogger::warning("Database offline while querying services catalog. Providing fallback response.");
    Response::error("Unable to load services from database.", [
        'suggestion' => 'Please verify MySQL service in XAMPP is running.'
    ], 503);
}
