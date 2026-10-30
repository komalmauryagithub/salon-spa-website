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
    
    $fallbackCategories = [
        ['id' => 1, 'slug' => 'hair-architecture', 'name' => 'Hair Architecture', 'description' => 'Precision cuts, texturizing and bespoke blowouts'],
        ['id' => 2, 'slug' => 'color-highlights', 'name' => 'Color & Highlights', 'description' => 'Dimensional balayage, organic glosses and couture color'],
        ['id' => 3, 'slug' => 'holistic-spa', 'name' => 'Holistic Spa', 'description' => 'Scalp therapy, Japanese head spa and organic relaxation'],
        ['id' => 4, 'slug' => 'curated-packages', 'name' => 'Curated Packages', 'description' => 'Combined multi-ritual sanctuary experiences']
    ];

    $fallbackServices = [
        ['id' => 1, 'category_id' => 1, 'slug' => 'haircut', 'name' => 'Artisanal Precision Haircut', 'short_description' => 'Custom consultation, scalp massage, tailored cut and finished blowout.', 'duration_minutes' => 60, 'price' => 85.00, 'category_slug' => 'hair-architecture'],
        ['id' => 2, 'category_id' => 2, 'slug' => 'balayage', 'name' => 'Couture French Balayage', 'short_description' => 'Hand-painted dimensional brilliance with nourishing gloss glaze.', 'duration_minutes' => 150, 'price' => 195.00, 'category_slug' => 'color-highlights'],
        ['id' => 3, 'category_id' => 3, 'slug' => 'hair-spa', 'name' => 'Japanese Head Spa & Scalp Cure', 'short_description' => 'Micro-mist steam, shiatsu pressure point massage and botanical scalp therapy.', 'duration_minutes' => 75, 'price' => 120.00, 'category_slug' => 'holistic-spa'],
        ['id' => 4, 'category_id' => 1, 'slug' => 'keratin', 'name' => 'Clean Botanical Keratin Treatment', 'short_description' => 'Formaldehyde-free smoothing therapy restoring silky luster and strength.', 'duration_minutes' => 150, 'price' => 260.00, 'category_slug' => 'hair-architecture'],
        ['id' => 5, 'category_id' => 3, 'slug' => 'facial', 'name' => 'Cellular Rejuvenation Facial', 'short_description' => 'Peptide infusion, cellular hydration and lymphatic drainage.', 'duration_minutes' => 75, 'price' => 140.00, 'category_slug' => 'holistic-spa'],
        ['id' => 11, 'category_id' => 4, 'slug' => 'executive-package', 'name' => 'The Executive Revival Package', 'short_description' => 'Precision cut, beard or scalp ritual, and botanical express facial.', 'duration_minutes' => 90, 'price' => 135.00, 'category_slug' => 'curated-packages'],
        ['id' => 12, 'category_id' => 4, 'slug' => 'occasion-package', 'name' => 'The Occasion Suite Package', 'short_description' => 'Full styling, couture makeup consultation and restorative glass gloss.', 'duration_minutes' => 180, 'price' => 295.00, 'category_slug' => 'curated-packages']
    ];

    Response::success("Service catalog retrieved successfully (Showcase Fallback).", [
        'count'      => count($fallbackServices),
        'categories' => $fallbackCategories,
        'services'   => $fallbackServices,
        'source'     => 'showcase_fallback'
    ]);
}
