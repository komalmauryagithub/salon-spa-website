<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Customer Appointment Cancellation API
 * 
 * Enforces:
 * 1. CSRF token validation
 * 2. Authenticated customer session check
 * 3. Strict IDOR protection (cannot cancel another guest's booking)
 * 4. 24-hour salon cancellation policy window
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Accept only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed. Use POST.", [], 405);
}

// 1. Authentication check
if (!Auth::check()) {
    Response::error("Authentication required to manage appointments.", [], 401);
}

$customerId = Auth::customerId();
if (!$customerId) {
    Response::error("No client profile associated with this account.", [], 403);
}

// Support raw JSON or form POST
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

// 2. CSRF Token verification
$submittedToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!Csrf::verify($submittedToken)) {
    Response::error("Security token validation failed. Please refresh your session.", [], 403);
}

// 3. Validate appointment ID
$appointmentId = (int)($input['appointment_id'] ?? $input['id'] ?? 0);
if ($appointmentId <= 0) {
    Response::error("A valid appointment ID is required.", ['appointment_id' => 'Required'], 422);
}

$reason = !empty($input['reason']) ? Validator::sanitizeString((string)$input['reason']) : 'Cancelled by guest via atelier portal';

try {
    $pdo = Database::getConnection();

    // 4. Fetch appointment and verify ownership (IDOR Protection)
    $stmt = $pdo->prepare("
        SELECT 
            id, booking_reference, customer_id, staff_id, 
            appointment_date, appointment_time, duration_minutes, status 
        FROM appointments 
        WHERE id = :id 
        LIMIT 1
    ");
    $stmt->execute(['id' => $appointmentId]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        Response::error("Appointment record not found.", [], 404);
    }

    // Strict IDOR Check
    if ((int)$appointment['customer_id'] !== $customerId) {
        AppLogger::warning("IDOR Violation: User ID " . Auth::id() . " (Customer #{$customerId}) attempted to cancel Appointment #{$appointmentId} belonging to Customer #{$appointment['customer_id']}");
        Response::error("Unauthorized. You can only manage reservations created under your personal account.", [], 403);
    }

    // 5. Check salon cancellation business rules via Scheduler
    $check = Scheduler::canCustomerCancel($appointment);
    if (!$check['can_cancel']) {
        Response::error($check['reason'] ?? "This appointment cannot be cancelled online.", [
            'policy' => '24-hour advance notice requirement',
            'hours_left' => $check['hours_left'] ?? null
        ], 422);
    }

    // 6. Update appointment status to 'cancelled'
    $upStmt = $pdo->prepare("
        UPDATE appointments 
        SET status = 'cancelled', cancellation_reason = :reason, updated_at = NOW() 
        WHERE id = :id
    ");
    $upStmt->execute([
        'reason' => $reason,
        'id'     => $appointmentId
    ]);

    AppLogger::info("Appointment #{$appointmentId} [{$appointment['booking_reference']}] successfully cancelled by Customer #{$customerId}");

    Response::success("Your reservation [{$appointment['booking_reference']}] has been cancelled.", [
        'appointment_id'    => $appointmentId,
        'booking_reference' => $appointment['booking_reference'],
        'status'            => 'cancelled',
        'cancellation_reason' => $reason
    ]);

} catch (Exception $e) {
    AppLogger::error("Appointment cancellation error: " . $e->getMessage());
    Response::error("An error occurred while processing your cancellation request.", [], 500);
}
