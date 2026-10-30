<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Concierge & Administrative Appointment Status Management API
 * 
 * Allows authorized administrators, salon managers, and concierge receptionists
 * to confirm, complete, cancel, or flag appointments with audit logging.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Accept only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed. Use POST.", [], 405);
}

// 1. Guard check: Must have elevated atelier role
Auth::requireRole(['admin', 'manager', 'receptionist']);

// Support JSON or form POST
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }
}

// 2. Validate CSRF Token
$submittedToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!Csrf::verify($submittedToken)) {
    Response::error("Security token validation failed. Please refresh the page.", [], 403);
}

// 3. Validate Inputs
$validator = Validator::make($input)
    ->required('appointment_id', 'Appointment ID is required.')
    ->required('status', 'Target appointment status is required.')
    ->in('status', ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'], 'Invalid status value.');

if ($validator->fails()) {
    Response::error($validator->firstError() ?: "Validation failed.", $validator->errors(), 422);
}

$appointmentId = (int)$validator->value('appointment_id');
$newStatus     = (string)$validator->value('status');
$reason        = !empty($input['cancellation_reason']) 
    ? Validator::sanitizeString((string)$input['cancellation_reason']) 
    : (!empty($input['notes']) ? Validator::sanitizeString((string)$input['notes']) : null);

try {
    $pdo = Database::getConnection();

    // Check appointment exists
    $stmt = $pdo->prepare("
        SELECT id, booking_reference, customer_id, staff_id, appointment_date, appointment_time, status 
        FROM appointments 
        WHERE id = :id 
        LIMIT 1
    ");
    $stmt->execute(['id' => $appointmentId]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        Response::error("Appointment #{$appointmentId} not found in atelier registry.", [], 404);
    }

    $oldStatus = $appointment['status'];

    // Update appointment status
    if ($newStatus === 'cancelled') {
        $cancelReason = $reason ?: 'Cancelled by concierge administration';
        $up = $pdo->prepare("
            UPDATE appointments 
            SET status = :status, cancellation_reason = :reason, updated_at = NOW() 
            WHERE id = :id
        ");
        $up->execute([
            'status' => $newStatus,
            'reason' => $cancelReason,
            'id'     => $appointmentId
        ]);
    } else {
        $up = $pdo->prepare("
            UPDATE appointments 
            SET status = :status, updated_at = NOW() 
            WHERE id = :id
        ");
        $up->execute([
            'status' => $newStatus,
            'id'     => $appointmentId
        ]);
    }

    $adminUser = Auth::user();
    AppLogger::info("Admin #{$adminUser['id']} ({$adminUser['username']} [{$adminUser['role']}]) changed Appointment #{$appointmentId} [{$appointment['booking_reference']}] status: '{$oldStatus}' -> '{$newStatus}'");

    Response::success("Reservation #{$appointment['booking_reference']} updated to " . ucfirst($newStatus) . ".", [
        'appointment_id'    => $appointmentId,
        'booking_reference' => $appointment['booking_reference'],
        'old_status'        => $oldStatus,
        'new_status'        => $newStatus,
        'status_label'      => ucfirst(str_replace('_', ' ', $newStatus)),
        'updated_at'        => date('Y-m-d H:i:s')
    ]);

} catch (Exception $e) {
    AppLogger::error("Admin status update error: " . $e->getMessage());
    Response::error("An error occurred while updating the appointment status.", [], 500);
}
