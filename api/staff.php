<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Master Stylist & Specialist Roster API
 * 
 * Provides active team profiles matching team.html and booking selectors.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->query("
        SELECT id, slug, full_name, role_title, bio, specialties, experience_years, avatar_url, working_days, is_available
        FROM staff
        WHERE is_available = 1
        ORDER BY display_order ASC
    ");
    $staff = $stmt->fetchAll();

    Response::success("Master specialist roster retrieved successfully.", [
        'count' => count($staff),
        'staff' => $staff,
        'source' => 'mysql'
    ]);
} catch (Exception $e) {
    AppLogger::warning("Database offline while querying staff roster.");
    Response::error("Unable to load specialist roster from database.", [], 503);
}
