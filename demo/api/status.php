<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Demo Appointment Status Management API (Admin only)
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
Demo::requireEnabled();
DemoStore::initSessionStore();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed. Use POST.", [], 405);
}

// Role guard: demo admin
if (!DemoAuth::isAdmin()) {
    Response::error("Demo administrator authentication required.", [], 401);
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
$status = trim((string)($input['status'] ?? ''));

if (empty($ref) || empty($status)) {
    Response::error("Booking reference and new status are required.", [], 422);
}

$success = DemoStore::updateAppointmentStatus($ref, $status);

if ($success) {
    Response::json([
        'success' => true,
        'message' => "Appointment [{$ref}] status successfully transitioned to {$status}.",
        'booking_reference' => $ref,
        'status' => $status
    ]);
} else {
    Response::error("Failed to update status. Record not found.", [], 404);
}
