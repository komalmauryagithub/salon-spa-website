<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Demo Available Time Slots API
 * 
 * Provides mock available slots for demonstration booking.
 */

require_once __DIR__ . '/../../includes/bootstrap.php';
Demo::requireEnabled();

$date = $_GET['date'] ?? date('Y-m-d');
$dayOfWeek = date('w', strtotime($date));

// Closed on Mondays in luxury French tradition (0 = Sun, 1 = Mon)
$isOpen = ($dayOfWeek != 1);

$slots = [
    ['time' => '09:30:00', 'label' => '09:30 AM', 'available' => true],
    ['time' => '10:30:00', 'label' => '10:30 AM', 'available' => true],
    ['time' => '11:30:00', 'label' => '11:30 AM', 'available' => true],
    ['time' => '12:00:00', 'label' => '12:00 PM', 'available' => false, 'reason' => 'Reserved in demonstration schedule'],
    ['time' => '13:30:00', 'label' => '01:30 PM', 'available' => true],
    ['time' => '14:30:00', 'label' => '02:30 PM', 'available' => true],
    ['time' => '15:30:00', 'label' => '03:30 PM', 'available' => true],
    ['time' => '16:30:00', 'label' => '04:30 PM', 'available' => true],
    ['time' => '17:30:00', 'label' => '05:30 PM', 'available' => true],
    ['time' => '18:30:00', 'label' => '06:30 PM', 'available' => true],
    ['time' => '19:00:00', 'label' => '07:00 PM', 'available' => true],
    ['time' => '19:45:00', 'label' => '07:45 PM', 'available' => false, 'reason' => 'Reserved']
];

Response::json([
    'success' => true,
    'data' => [
        'date'        => $date,
        'day_name'    => date('l', strtotime($date)),
        'is_open'     => $isOpen,
        'salon_hours' => $isOpen ? '09:30 AM – 08:00 PM' : 'Closed on Mondays',
        'slots'       => $isOpen ? $slots : [],
        'reason'      => $isOpen ? null : 'Our atelier sanctuary is closed on Mondays for master colorist apprenticeships.'
    ]
]);
