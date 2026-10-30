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
    AppLogger::warning("Database offline while querying staff roster. Providing showcase fallback.");
    
    $fallbackStaff = [
        ['id' => 1, 'slug' => 'clara-valmont', 'full_name' => 'Clara Valmont', 'role_title' => 'Master Colorist & Spa Director', 'bio' => 'Celebrated for botanical glosses and meditative scalp treatments with over a decade of French training.', 'specialties' => 'Japanese Head Spa, Balayage, Precision Cut', 'experience_years' => 12, 'is_available' => 1],
        ['id' => 2, 'slug' => 'julian-moreau', 'full_name' => 'Julian Moreau', 'role_title' => 'Creative Director of Balayage', 'bio' => 'Trained in Paris, Julian crafts seamless, multidimensional sun-kissed tones suited to every texture.', 'specialties' => 'French Balayage, Color Correction, Gloss', 'experience_years' => 10, 'is_available' => 1],
        ['id' => 3, 'slug' => 'elena-rostova', 'full_name' => 'Elena Rostova', 'role_title' => 'Head of Aesthetics & Wellness', 'bio' => 'Specializing in holistic facial therapy, lymphatic drainage, and cellular skin renewal rituals.', 'specialties' => 'Cellular Facial, Gua Sha, Micro-current', 'experience_years' => 14, 'is_available' => 1],
        ['id' => 4, 'slug' => 'marcus-sterling', 'full_name' => 'Marcus Sterling', 'role_title' => 'Master Barber Director', 'bio' => 'Master of razor fades, luxury hot towel shaves, and artisanal gentleman sculpting.', 'specialties' => 'Gentleman Barbering, Keratin, Hot Towel', 'experience_years' => 9, 'is_available' => 1]
    ];

    Response::success("Master specialist roster retrieved successfully (Showcase Fallback).", [
        'count'  => count($fallbackStaff),
        'staff'  => $fallbackStaff,
        'source' => 'showcase_fallback'
    ]);
}
