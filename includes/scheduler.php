<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Core Appointment Scheduling Engine
 * 
 * Handles salon opening hours, stylist rosters, time-slot generation,
 * overlap conflict detection, concurrency locking, and cancellation policies.
 */

class Scheduler {
    public const CANCELLATION_HOURS_LIMIT = 24; // Salon cancellation window in hours
    public const SLOT_INTERVAL_MINUTES = 30;    // Time slot granularity

    /**
     * Get salon operating hours for a specific calendar date
     * 
     * Monday: Closed
     * Tuesday – Friday: 09:00 to 20:30
     * Saturday – Sunday: 09:30 to 19:00
     */
    public static function getSalonHours(string $date): array {
        $timestamp = strtotime($date);
        if (!$timestamp) {
            return ['is_open' => false, 'reason' => 'Invalid calendar date.'];
        }

        $dayOfWeek = date('D', $timestamp); // Mon, Tue, Wed, Thu, Fri, Sat, Sun

        if ($dayOfWeek === 'Mon') {
            return [
                'is_open'     => false,
                'day_name'    => 'Monday',
                'reason'      => 'Our atelier is closed on Mondays for masterclasses and internal education.'
            ];
        }

        if (in_array($dayOfWeek, ['Sat', 'Sun'], true)) {
            return [
                'is_open'     => true,
                'day_name'    => date('l', $timestamp),
                'open_time'   => '09:30:00',
                'close_time'  => '19:00:00',
                'description' => '09:30 AM – 07:00 PM'
            ];
        }

        // Tuesday through Friday
        return [
            'is_open'     => true,
            'day_name'    => date('l', $timestamp),
            'open_time'   => '09:00:00',
            'close_time'  => '20:30:00',
            'description' => '09:00 AM – 08:30 PM'
        ];
    }

    /**
     * Check if a stylist is scheduled to work on a given date
     */
    public static function isStylistWorking(int $staffId, string $date): array {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT id, full_name, working_days, is_available FROM staff WHERE id = :id LIMIT 1");
            $stmt->execute(['id' => $staffId]);
            $staff = $stmt->fetch();

            if (!$staff) {
                return ['working' => false, 'reason' => 'Specialist not found in atelier registry.'];
            }

            if ((int)$staff['is_available'] !== 1) {
                return ['working' => false, 'reason' => "{$staff['full_name']} is currently on sabbatical leave."];
            }

            $dayAbbr = date('D', strtotime($date)); // e.g. 'Tue', 'Wed'
            $workingDays = array_map('trim', explode(',', (string)$staff['working_days']));

            if (!in_array($dayAbbr, $workingDays, true)) {
                return [
                    'working' => false,
                    'reason'  => "{$staff['full_name']} is not scheduled to work on " . date('l', strtotime($date)) . "s."
                ];
            }

            return ['working' => true, 'staff' => $staff];
        } catch (Exception $e) {
            AppLogger::error("Error checking stylist schedule: " . $e->getMessage());
            return ['working' => false, 'reason' => 'Database error checking stylist schedule.'];
        }
    }

    /**
     * Generate all valid time slots for a given date, service duration, and specialist
     */
    public static function getAvailableSlots(string $date, int $durationMinutes, ?int $staffId = null): array {
        $salonHours = self::getSalonHours($date);
        if (!$salonHours['is_open']) {
            return [
                'is_open' => false,
                'reason'  => $salonHours['reason'] ?? 'Salon is closed.',
                'slots'   => []
            ];
        }

        // If specific stylist selected, check their working schedule
        if ($staffId !== null) {
            $stylistCheck = self::isStylistWorking($staffId, $date);
            if (!$stylistCheck['working']) {
                return [
                    'is_open' => true,
                    'reason'  => $stylistCheck['reason'],
                    'slots'   => []
                ];
            }
        }

        $openTimestamp = strtotime("{$date} {$salonHours['open_time']}");
        $closeTimestamp = strtotime("{$date} {$salonHours['close_time']}");
        $now = time();

        // Load all active stylists working on this day (needed if staffId is null/any)
        $dayAbbr = date('D', strtotime($date));
        $workingStylists = self::getWorkingStylistsForDay($dayAbbr);

        if (empty($workingStylists)) {
            return [
                'is_open' => true,
                'reason'  => 'No master specialists are scheduled on this date.',
                'slots'   => []
            ];
        }

        // Load all existing appointments on this date with active status
        $appointments = self::getActiveAppointmentsForDate($date);

        $slots = [];
        $currentSlot = $openTimestamp;

        while ($currentSlot + ($durationMinutes * 60) <= $closeTimestamp) {
            $slotTimeStr = date('H:i:00', $currentSlot);
            $slotLabel   = date('g:i A', $currentSlot);
            $slotStart   = $currentSlot;
            $slotEnd     = $currentSlot + ($durationMinutes * 60);

            // Cannot book past slots if booking for today
            if ($slotStart <= $now) {
                $slots[] = [
                    'time'      => date('H:i', $currentSlot),
                    'label'     => $slotLabel,
                    'available' => false,
                    'reason'    => 'Time slot has passed'
                ];
                $currentSlot += (self::SLOT_INTERVAL_MINUTES * 60);
                continue;
            }

            // Check availability
            if ($staffId !== null) {
                // Check specific stylist conflict
                $hasConflict = self::hasOverlappingAppointment($appointments, $staffId, $slotStart, $slotEnd);
                $slots[] = [
                    'time'      => date('H:i', $currentSlot),
                    'label'     => $slotLabel,
                    'available' => !$hasConflict,
                    'reason'    => $hasConflict ? 'Reserved' : 'Available'
                ];
            } else {
                // 'Any Available': At least ONE working stylist must be free during [slotStart, slotEnd]
                $availableStylistCount = 0;
                foreach ($workingStylists as $stf) {
                    if (!self::hasOverlappingAppointment($appointments, (int)$stf['id'], $slotStart, $slotEnd)) {
                        $availableStylistCount++;
                    }
                }

                $slots[] = [
                    'time'             => date('H:i', $currentSlot),
                    'label'            => $slotLabel,
                    'available'        => ($availableStylistCount > 0),
                    'available_stylists' => $availableStylistCount,
                    'reason'           => ($availableStylistCount > 0) ? 'Available' : 'Fully Reserved'
                ];
            }

            $currentSlot += (self::SLOT_INTERVAL_MINUTES * 60);
        }

        return [
            'is_open'     => true,
            'date'        => $date,
            'day_name'    => $salonHours['day_name'],
            'hours'       => $salonHours['description'],
            'duration'    => $durationMinutes,
            'total_slots' => count($slots),
            'slots'       => $slots
        ];
    }

    /**
     * Recheck availability at submission time inside an active transaction with row locking
     * 
     * @param string $date YYYY-MM-DD
     * @param string $time HH:MM:00
     * @param int $durationMinutes
     * @param int|null $requestedStaffId Specific stylist or null for 'any'
     * @return array ['available' => bool, 'assigned_staff_id' => int|null, 'error' => string|null]
     */
    public static function verifySlotAndAssignStaff(
        PDO $pdo,
        string $date,
        string $time,
        int $durationMinutes,
        ?int $requestedStaffId = null
    ): array {
        $salonHours = self::getSalonHours($date);
        if (!$salonHours['is_open']) {
            return ['available' => false, 'assigned_staff_id' => null, 'error' => $salonHours['reason']];
        }

        $slotStart = strtotime("{$date} {$time}");
        $slotEnd   = $slotStart + ($durationMinutes * 60);
        $closeTime = strtotime("{$date} {$salonHours['close_time']}");

        if ($slotStart < strtotime("{$date} {$salonHours['open_time']}")) {
            return ['available' => false, 'assigned_staff_id' => null, 'error' => 'Selected time is earlier than salon opening hours.'];
        }

        if ($slotEnd > $closeTime) {
            return ['available' => false, 'assigned_staff_id' => null, 'error' => 'This service duration extends past salon closing hours.'];
        }

        if ($slotStart <= time()) {
            return ['available' => false, 'assigned_staff_id' => null, 'error' => 'Cannot reserve an appointment time that has already passed.'];
        }

        $dayAbbr = date('D', strtotime($date));

        // 1. If specific stylist requested:
        if ($requestedStaffId !== null) {
            $workingCheck = self::isStylistWorking($requestedStaffId, $date);
            if (!$workingCheck['working']) {
                return ['available' => false, 'assigned_staff_id' => null, 'error' => $workingCheck['reason']];
            }

            // Pessimistic Lock on existing appointments for this stylist on this date
            $lockStmt = $pdo->prepare("
                SELECT id, appointment_time, duration_minutes 
                FROM appointments 
                WHERE staff_id = :sid 
                  AND appointment_date = :dt 
                  AND status IN ('pending', 'confirmed', 'in_progress')
                FOR UPDATE
            ");
            $lockStmt->execute(['sid' => $requestedStaffId, 'dt' => $date]);
            $existing = $lockStmt->fetchAll();

            foreach ($existing as $apt) {
                $aptStart = strtotime("{$date} {$apt['appointment_time']}");
                $aptEnd   = $aptStart + ((int)$apt['duration_minutes'] * 60);

                if (self::intervalsOverlap($slotStart, $slotEnd, $aptStart, $aptEnd)) {
                    return [
                        'available'         => false,
                        'assigned_staff_id' => null,
                        'error'             => "We apologize, but this time slot with your selected specialist was just reserved by another guest. Please select an alternate time slot."
                    ];
                }
            }

            return ['available' => true, 'assigned_staff_id' => $requestedStaffId, 'error' => null];
        }

        // 2. If 'Any Available Specialist' requested:
        // Find all active stylists working on this day
        $stfStmt = $pdo->prepare("
            SELECT id, full_name 
            FROM staff 
            WHERE is_available = 1 
              AND FIND_IN_SET(:day, REPLACE(working_days, ' ', '')) > 0
            ORDER BY display_order ASC
        ");
        $stfStmt->execute(['day' => $dayAbbr]);
        $workingStylists = $stfStmt->fetchAll();

        if (empty($workingStylists)) {
            return ['available' => false, 'assigned_staff_id' => null, 'error' => 'No specialists are on duty on this day.'];
        }

        // Lock appointments across all working stylists for this date
        $staffIds = array_column($workingStylists, 'id');
        $inClause = implode(',', array_map('intval', $staffIds));

        $lockStmt = $pdo->query("
            SELECT id, staff_id, appointment_time, duration_minutes 
            FROM appointments 
            WHERE staff_id IN ({$inClause}) 
              AND appointment_date = '{$date}' 
              AND status IN ('pending', 'confirmed', 'in_progress')
            FOR UPDATE
        ");
        $allAppointments = $lockStmt->fetchAll();

        // Group appointments by staff_id
        $appointmentsByStaff = [];
        foreach ($allAppointments as $apt) {
            $sid = (int)$apt['staff_id'];
            $appointmentsByStaff[$sid][] = $apt;
        }

        // Pick the first stylist with NO overlap
        foreach ($workingStylists as $stf) {
            $sid = (int)$stf['id'];
            $staffApts = $appointmentsByStaff[$sid] ?? [];

            $hasOverlap = false;
            foreach ($staffApts as $apt) {
                $aptStart = strtotime("{$date} {$apt['appointment_time']}");
                $aptEnd   = $aptStart + ((int)$apt['duration_minutes'] * 60);

                if (self::intervalsOverlap($slotStart, $slotEnd, $aptStart, $aptEnd)) {
                    $hasOverlap = true;
                    break;
                }
            }

            if (!$hasOverlap) {
                // Found an available stylist!
                return ['available' => true, 'assigned_staff_id' => $sid, 'error' => null];
            }
        }

        return [
            'available'         => false,
            'assigned_staff_id' => null,
            'error'             => "All master specialists are fully booked for this treatment duration at {$time}. Please choose another time slot."
        ];
    }

    /**
     * Check if a customer is eligible to self-cancel an appointment according to salon policies
     * 
     * Rule: Cancellation allowed online up to 24 hours prior to appointment time.
     * Within 24 hours requires telephone concierge contact.
     */
    public static function canCustomerCancel(array $appointment): array {
        $status = strtolower((string)$appointment['status']);

        if ($status === 'cancelled') {
            return ['can_cancel' => false, 'reason' => 'This appointment is already cancelled.'];
        }

        if ($status === 'completed') {
            return ['can_cancel' => false, 'reason' => 'Completed appointments cannot be cancelled.'];
        }

        $appointmentDateTime = strtotime("{$appointment['appointment_date']} {$appointment['appointment_time']}");
        $hoursUntil = ($appointmentDateTime - time()) / 3600;

        if ($hoursUntil <= 0) {
            return [
                'can_cancel' => false,
                'reason'     => 'This appointment time has already passed.'
            ];
        }

        if ($hoursUntil < self::CANCELLATION_HOURS_LIMIT) {
            return [
                'can_cancel' => false,
                'hours_left' => round($hoursUntil, 1),
                'reason'     => "Online cancellation is permitted up to 24 hours prior to your ritual. Because your service is scheduled in less than 24 hours (" . round($hoursUntil) . " hrs), please contact our concierge desk directly at +1 (800) 555-0192."
            ];
        }

        return ['can_cancel' => true, 'hours_left' => round($hoursUntil, 1)];
    }

    /**
     * Interval overlap check: [startA, endA) and [startB, endB)
     */
    public static function intervalsOverlap(int $startA, int $endA, int $startB, int $endB): bool {
        return ($startA < $endB) && ($startB < $endA);
    }

    /**
     * Check if a specific stylist has an overlapping appointment in list
     */
    private static function hasOverlappingAppointment(array $appointments, int $staffId, int $start, int $end): bool {
        foreach ($appointments as $apt) {
            if ((int)$apt['staff_id'] !== $staffId) continue;

            $aptStart = strtotime("{$apt['appointment_date']} {$apt['appointment_time']}");
            $aptEnd   = $aptStart + ((int)$apt['duration_minutes'] * 60);

            if (self::intervalsOverlap($start, $end, $aptStart, $aptEnd)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Fetch all active appointments on a date
     */
    private static function getActiveAppointmentsForDate(string $date): array {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT id, staff_id, appointment_date, appointment_time, duration_minutes, status 
                FROM appointments 
                WHERE appointment_date = :dt 
                  AND status IN ('pending', 'confirmed', 'in_progress')
            ");
            $stmt->execute(['dt' => $date]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            AppLogger::error("Error loading active appointments: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Fetch all working stylists for day of week
     */
    private static function getWorkingStylistsForDay(string $dayAbbr): array {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("
                SELECT id, full_name, working_days 
                FROM staff 
                WHERE is_available = 1 
                  AND FIND_IN_SET(:day, REPLACE(working_days, ' ', '')) > 0
                ORDER BY display_order ASC
            ");
            $stmt->execute(['day' => $dayAbbr]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            AppLogger::error("Error loading working stylists: " . $e->getMessage());
            return [];
        }
    }
}
