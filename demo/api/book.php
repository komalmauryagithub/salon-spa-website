<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Demo Appointment Booking API
 * 
 * Saves demo appointments directly to isolated PHP Session storage with zero database dependency.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
Demo::requireEnabled();
DemoStore::initSessionStore();

// Accept only POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed. Use POST.", [], 405);
}

// Read JSON or form POST
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

// Validation
$guestName  = trim((string)($input['guest_name'] ?? ''));
$guestEmail = trim((string)($input['guest_email'] ?? ''));
$guestPhone = trim((string)($input['guest_phone'] ?? ''));
$date       = trim((string)($input['date'] ?? ''));
$time       = trim((string)($input['time'] ?? ''));
$serviceId  = trim((string)($input['service_id'] ?? $input['service_slug'] ?? 'haircut'));
$stylistId  = trim((string)($input['stylist_id'] ?? $input['stylist_slug'] ?? 'any'));
$notes      = trim((string)($input['notes'] ?? ''));
$silent     = !empty($input['silent_appointment']) ? 1 : 0;

$errors = [];
if (empty($guestName))  $errors['guest_name']  = 'Please provide your full name.';
if (empty($guestEmail)) $errors['guest_email'] = 'Please provide an email address.';
if (empty($guestPhone)) $errors['guest_phone'] = 'Please provide a contact telephone number.';
if (empty($date))       $errors['date']        = 'Please select an appointment date.';
if (empty($time))       $errors['time']        = 'Please select a preferred appointment time slot.';

if (!empty($errors)) {
    Response::error("Please complete all required appointment fields.", $errors, 422);
}

// Save into DemoStore session
$result = DemoStore::createAppointment([
    'service_id'         => $serviceId,
    'stylist_id'         => $stylistId,
    'date'               => $date,
    'time'               => $time,
    'guest_name'         => $guestName,
    'guest_email'        => $guestEmail,
    'guest_phone'        => $guestPhone,
    'notes'              => $notes,
    'silent_appointment' => $silent
]);

$apt = $result['appointment'];

Response::json([
    'success'           => true,
    'message'           => 'Your demo appointment has been successfully confirmed in session memory.',
    'booking_reference' => $result['booking_reference'],
    'status'            => 'pending',
    'details'           => [
        'booking_reference' => $result['booking_reference'],
        'guest_name'        => $apt['customer_name'],
        'guest_email'       => $apt['customer_email'],
        'guest_phone'       => $apt['customer_phone'],
        'service_name'      => $apt['service_name'],
        'stylist_name'      => $apt['staff_name'],
        'date'              => $apt['appointment_date'],
        'time'              => $apt['appointment_time'],
        'duration'          => $apt['duration_minutes'] . ' mins',
        'estimated_price'   => '$' . number_format((float)$apt['total_price'], 2),
        'silent_service'    => $apt['silent_appointment'] ? 'Silent Appointment (Quiet Consultation)' : 'Standard Consultation'
    ]
], 201);
