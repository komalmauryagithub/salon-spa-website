<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Admin Appointment Details Inspection API
 * 
 * Securely retrieves complete appointment, guest, and treatment data
 * for administrative inspection and concierge handling.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Accept GET or POST
if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'POST'], true)) {
    Response::error("Method not allowed.", [], 405);
}

// 1. Guard check: Must be administrative staff
Auth::requireRole(['admin', 'manager', 'receptionist']);

$appointmentId = (int)($_GET['id'] ?? $_POST['id'] ?? $_POST['appointment_id'] ?? 0);
if ($appointmentId <= 0) {
    Response::error("A valid appointment ID is required.", ['id' => 'Required'], 422);
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("
        SELECT 
            a.id,
            a.booking_reference,
            a.customer_id,
            a.staff_id,
            a.service_id,
            a.offer_id,
            a.appointment_date,
            a.appointment_time,
            a.duration_minutes,
            a.total_price,
            a.status,
            a.silent_appointment,
            a.notes,
            a.cancellation_reason,
            a.created_at,
            a.updated_at,
            c.first_name,
            c.last_name,
            c.email AS customer_email,
            c.phone AS customer_phone,
            c.gender_preference,
            c.allergies_notes,
            c.is_vip,
            c.created_at AS customer_since,
            COALESCE(s.name, o.title, 'Bespoke Atelier Treatment') AS service_name,
            s.slug AS service_slug,
            sc.name AS service_category_name,
            st.full_name AS specialist_name,
            st.role_title AS specialist_role,
            st.working_days AS specialist_working_days
        FROM appointments a
        JOIN customers c ON a.customer_id = c.id
        LEFT JOIN services s ON a.service_id = s.id
        LEFT JOIN service_categories sc ON s.category_id = sc.id
        LEFT JOIN offers o ON a.offer_id = o.id
        LEFT JOIN staff st ON a.staff_id = st.id
        WHERE a.id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $appointmentId]);
    $appointment = $stmt->fetch();

    if (!$appointment) {
        Response::error("Appointment #{$appointmentId} not found.", [], 404);
    }

    $formattedDate = date('l, F j, Y', strtotime($appointment['appointment_date']));
    $formattedTime = date('g:i A', strtotime($appointment['appointment_time']));

    Response::success("Appointment details retrieved successfully.", [
        'id'                  => (int)$appointment['id'],
        'booking_reference'   => $appointment['booking_reference'],
        'status'              => $appointment['status'],
        'status_label'        => ucfirst(str_replace('_', ' ', $appointment['status'])),
        'appointment_date'    => $appointment['appointment_date'],
        'appointment_time'    => $appointment['appointment_time'],
        'formatted_date'      => $formattedDate,
        'formatted_time'      => $formattedTime,
        'duration_minutes'    => (int)$appointment['duration_minutes'],
        'total_price'         => (float)$appointment['total_price'],
        'formatted_price'     => '$' . number_format((float)$appointment['total_price'], 2),
        'silent_appointment'  => (bool)$appointment['silent_appointment'],
        'notes'               => $appointment['notes'],
        'cancellation_reason' => $appointment['cancellation_reason'],
        'created_at'          => $appointment['created_at'],
        'updated_at'          => $appointment['updated_at'],
        'customer'            => [
            'id'                => (int)$appointment['customer_id'],
            'full_name'         => $appointment['first_name'] . ' ' . $appointment['last_name'],
            'first_name'        => $appointment['first_name'],
            'last_name'         => $appointment['last_name'],
            'email'             => $appointment['customer_email'],
            'phone'             => $appointment['customer_phone'],
            'gender_preference' => $appointment['gender_preference'],
            'allergies_notes'   => $appointment['allergies_notes'],
            'is_vip'            => (bool)$appointment['is_vip'],
            'customer_since'    => $appointment['customer_since'] ? date('M j, Y', strtotime($appointment['customer_since'])) : null
        ],
        'service'             => [
            'name'          => $appointment['service_name'],
            'category'      => $appointment['service_category_name'] ?? 'Curated Package',
            'slug'          => $appointment['service_slug']
        ],
        'specialist'          => [
            'id'           => $appointment['staff_id'] ? (int)$appointment['staff_id'] : null,
            'name'         => $appointment['specialist_name'] ?: 'Unassigned / Any Available',
            'role'         => $appointment['specialist_role'],
            'working_days' => $appointment['specialist_working_days']
        ]
    ]);

} catch (Exception $e) {
    AppLogger::error("Appointment detail inspection error: " . $e->getMessage());
    Response::error("An error occurred while inspecting the appointment.", [], 500);
}
