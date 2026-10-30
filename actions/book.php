<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Synchronous Booking Form Submission Handler (Fallback)
 */

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::redirect('../book-appointment.html');
}

// 1. Verify CSRF
$token = $_POST['csrf_token'] ?? null;
if (!empty($token) && !Csrf::verify($token)) {
    Response::redirect('../book-appointment.html', 'Security validation expired. Please resubmit your booking request.', 'error');
}

// 2. Validate
$validator = Validator::make($_POST)
    ->required('guest-name', 'Please provide your full name.')
    ->required('guest-email', 'Please provide a valid email address.')
    ->email('guest-email')
    ->required('guest-phone', 'Please provide a valid contact phone number.')
    ->phone('guest-phone', null, true)
    ->required('booking-date', 'Please select an appointment date.')
    ->date('booking-date', true);

if ($validator->fails()) {
    Response::redirect('../book-appointment.html', $validator->firstError() ?: 'Please check your booking fields.', 'error');
}

try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    $email = $validator->value('guest-email');
    $name  = $validator->value('guest-name');
    $phone = $validator->value('guest-phone');
    $date  = $validator->value('booking-date');
    $time  = !empty($_POST['booking-time']) ? date('H:i:00', strtotime($_POST['booking-time'])) : '10:00:00';

    // Customer
    $cStmt = $pdo->prepare("SELECT id FROM customers WHERE email = :email LIMIT 1");
    $cStmt->execute(['email' => $email]);
    $existing = $cStmt->fetch();

    if ($existing) {
        $customerId = (int)$existing['id'];
    } else {
        $parts = preg_split('/\s+/', trim($name), 2);
        $ins = $pdo->prepare("INSERT INTO customers (first_name, last_name, email, phone, created_at) VALUES (?, ?, ?, ?, NOW())");
        $ins->execute([$parts[0] ?? 'Guest', $parts[1] ?? 'Client', $email, $phone]);
        $customerId = (int)$pdo->lastInsertId();
    }

    // Service resolution
    $serviceSlug = trim($_POST['service_choice'] ?? $_POST['service_id'] ?? 'haircut');
    $serviceId = null;
    $durationMinutes = 60;
    $price = 85.00;

    $srvStmt = $pdo->prepare("SELECT id, name, duration_minutes, price FROM services WHERE slug = :slug OR id = :id LIMIT 1");
    $srvStmt->execute(['slug' => $serviceSlug, 'id' => is_numeric($serviceSlug) ? (int)$serviceSlug : 0]);
    $srv = $srvStmt->fetch();
    if ($srv) {
        $serviceId = (int)$srv['id'];
        $durationMinutes = (int)$srv['duration_minutes'];
        $price = (float)$srv['price'];
    }

    // Stylist resolution
    $stylistSlug = trim($_POST['stylist_choice'] ?? $_POST['stylist_id'] ?? 'any');
    $staffId = null;
    if ($stylistSlug !== 'any' && $stylistSlug !== '' && $stylistSlug !== '0') {
        $stfStmt = $pdo->prepare("SELECT id FROM staff WHERE slug = :slug OR id = :id LIMIT 1");
        $stfStmt->execute(['slug' => $stylistSlug, 'id' => is_numeric($stylistSlug) ? (int)$stylistSlug : 0]);
        $stf = $stfStmt->fetch();
        if ($stf) {
            $staffId = (int)$stf['id'];
        }
    }

    // Scheduler verification & concurrency lock
    $slotCheck = Scheduler::verifySlotAndAssignStaff($pdo, $date, $time, $durationMinutes, $staffId);
    if (!$slotCheck['available']) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        Response::redirect('../book-appointment.html', $slotCheck['error'] ?? 'Selected time slot is no longer available. Please select another slot.', 'error');
    }

    if ($staffId === null && !empty($slotCheck['assigned_staff_id'])) {
        $staffId = (int)$slotCheck['assigned_staff_id'];
    }

    $ref = 'LETOILE-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));

    $app = $pdo->prepare("
        INSERT INTO appointments (
            booking_reference, customer_id, staff_id, service_id, 
            appointment_date, appointment_time, duration_minutes, total_price, 
            status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    $app->execute([$ref, $customerId, $staffId, $serviceId, $date, $time, $durationMinutes, $price]);
    $pdo->commit();

    Response::redirect('../book-appointment.html', "Appointment booked successfully! Your reservation reference is: {$ref}", 'success');
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    AppLogger::error("Synchronous booking error: " . $e->getMessage());
    Response::redirect('../book-appointment.html', 'The reservation database is temporarily unavailable. Please call our concierge.', 'error');
}
