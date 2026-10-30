<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Demo Appointment Cancellation API
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
Demo::requireEnabled();
DemoStore::initSessionStore();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed. Use POST.", [], 405);
}

$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) $input = $decoded;
    }
}

$ref = trim((string)($input['booking_reference'] ?? $input['id'] ?? ''));
$reason = trim((string)($input['reason'] ?? 'Cancelled via Demo API'));

if (empty($ref)) {
    Response::error("A valid booking reference is required.", [], 422);
}

$success = DemoStore::cancelAppointment($ref, $reason);

if ($success) {
    Response::json([
        'success' => true,
        'message' => "Demo reservation [{$ref}] has been cancelled.",
        'booking_reference' => $ref,
        'status' => 'cancelled'
    ]);
} else {
    Response::error("Reservation not found in demo session storage.", [], 404);
}
