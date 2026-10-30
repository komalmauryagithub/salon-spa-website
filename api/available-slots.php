<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Live Time Slot Availability API
 * 
 * Computes available slots for a given date, service duration, and specialist,
 * respecting salon hours, stylist rosters, and existing active reservations.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error("Method not allowed. Use GET.", [], 405);
}

$date = trim((string)($_GET['date'] ?? ''));
$serviceSlug = trim((string)($_GET['service_slug'] ?? $_GET['service_id'] ?? 'haircut'));
$staffSlug   = trim((string)($_GET['staff_slug'] ?? $_GET['staff_id'] ?? 'any'));

if (empty($date)) {
    Response::error("Please provide an appointment date (YYYY-MM-DD).", ['date' => 'Required'], 422);
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    Response::error("Date must follow format YYYY-MM-DD.", ['date' => 'Invalid format'], 422);
}

// Cannot book past dates
if ($date < date('Y-m-d')) {
    Response::error("Cannot view availability for past calendar dates.", ['date' => 'Date in past'], 422);
}

// Maximum booking window: 90 days in advance
$maxDate = date('Y-m-d', strtotime('+90 days'));
if ($date > $maxDate) {
    Response::error("Appointments may be scheduled up to 90 days in advance.", ['date' => 'Exceeds maximum booking window'], 422);
}

try {
    $pdo = Database::getConnection();

    // 1. Resolve Canonical Service Duration & Price from Database
    $srvStmt = $pdo->prepare("
        SELECT id, slug, name, duration_minutes, price 
        FROM services 
        WHERE (slug = :slug OR id = :id) AND is_active = 1 
        LIMIT 1
    ");
    $srvStmt->execute(['slug' => $serviceSlug, 'id' => is_numeric($serviceSlug) ? (int)$serviceSlug : 0]);
    $serviceRow = $srvStmt->fetch();

    $serviceData = null;
    $durationMinutes = 60;
    $price = 85.00;

    if ($serviceRow) {
        $serviceData = [
            'id'               => (int)$serviceRow['id'],
            'slug'             => $serviceRow['slug'],
            'name'             => $serviceRow['name'],
            'duration_minutes' => (int)$serviceRow['duration_minutes'],
            'price'            => (float)$serviceRow['price'],
            'formatted_price'  => '$' . number_format((float)$serviceRow['price'], 2)
        ];
        $durationMinutes = (int)$serviceRow['duration_minutes'];
        $price           = (float)$serviceRow['price'];
    } else {
        // Check offers table
        $offStmt = $pdo->prepare("
            SELECT id, slug, title, estimated_duration_minutes, package_price 
            FROM offers 
            WHERE slug = :slug AND is_active = 1 
            LIMIT 1
        ");
        $offStmt->execute(['slug' => $serviceSlug]);
        $offerRow = $offStmt->fetch();

        if ($offerRow) {
            $serviceData = [
                'id'               => (int)$offerRow['id'],
                'slug'             => $offerRow['slug'],
                'name'             => $offerRow['title'],
                'duration_minutes' => (int)$offerRow['estimated_duration_minutes'],
                'price'            => (float)$offerRow['package_price'],
                'formatted_price'  => '$' . number_format((float)$offerRow['package_price'], 2)
            ];
            $durationMinutes = (int)$offerRow['estimated_duration_minutes'];
            $price           = (float)$offerRow['package_price'];
        } else {
            // Default fallback
            $serviceData = [
                'id'               => 1,
                'slug'             => 'haircut',
                'name'             => 'Atelier Precision Haircut',
                'duration_minutes' => 60,
                'price'            => 85.00,
                'formatted_price'  => '$85.00'
            ];
        }
    }

    // 2. Resolve Specialist
    $staffId = null;
    $staffData = [
        'id'        => null,
        'slug'      => 'any',
        'full_name' => 'Any Available Master Specialist'
    ];

    if ($staffSlug !== 'any' && $staffSlug !== '' && $staffSlug !== '0') {
        $stfStmt = $pdo->prepare("
            SELECT id, slug, full_name, role_title, working_days, is_available 
            FROM staff 
            WHERE (slug = :slug OR id = :id) 
            LIMIT 1
        ");
        $stfStmt->execute(['slug' => $staffSlug, 'id' => is_numeric($staffSlug) ? (int)$staffSlug : 0]);
        $staffRow = $stfStmt->fetch();

        if ($staffRow) {
            $staffId = (int)$staffRow['id'];
            $staffData = [
                'id'           => $staffId,
                'slug'         => $staffRow['slug'],
                'full_name'    => $staffRow['full_name'],
                'role_title'   => $staffRow['role_title'],
                'working_days' => $staffRow['working_days']
            ];
        }
    }

    // 3. Compute Real Available Time Slots
    $slotResult = Scheduler::getAvailableSlots($date, $durationMinutes, $staffId);

    Response::success("Available slots calculated successfully.", [
        'date'         => $date,
        'is_open'      => $slotResult['is_open'],
        'day_name'     => $slotResult['day_name'] ?? date('l', strtotime($date)),
        'salon_hours'  => $slotResult['hours'] ?? null,
        'reason'       => $slotResult['reason'] ?? null,
        'service'      => $serviceData,
        'specialist'   => $staffData,
        'total_slots'  => $slotResult['total_slots'] ?? 0,
        'slots'        => $slotResult['slots'] ?? []
    ]);

} catch (Exception $e) {
    AppLogger::error("Slot calculation error: " . $e->getMessage());
    Response::error("An error occurred while calculating available appointment slots.", [], 500);
}
