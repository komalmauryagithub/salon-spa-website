<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Isolated Demo Mode Engine
 * 
 * Provides completely isolated session-based authentication, mock data store,
 * appointment scheduling, and admin reporting with ZERO dependency on MySQL.
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

if (!defined('DEMO_CUSTOMER_EMAIL')) {
    define('DEMO_CUSTOMER_EMAIL', 'demo@salon.com');
    define('DEMO_CUSTOMER_PASS', 'Demo@123');
    define('DEMO_ADMIN_EMAIL', 'admin@salon.com');
    define('DEMO_ADMIN_PASS', 'Admin@123');
}

/**
 * Demo Configuration and Global State
 */
class Demo {
    /**
     * Check if Demo Mode is enabled via environment or configuration
     */
    public static function isEnabled(): bool {
        $val = $_ENV['DEMO_MODE'] ?? getenv('DEMO_MODE') ?? null;
        if ($val !== null) {
            return filter_var($val, FILTER_VALIDATE_BOOLEAN);
        }
        return true; // Enabled by default for demonstration flexibility
    }

    /**
     * Guard to enforce demo mode availability on demo routes
     */
    public static function requireEnabled(): void {
        if (!self::isEnabled()) {
            http_response_code(403);
            echo "<!DOCTYPE html>
            <html lang='en'>
            <head>
              <meta charset='UTF-8'>
              <title>Demo Mode Disabled — L'Étoile Sanctuary</title>
              <style>
                body { background:#FAF7F2; color:#161413; font-family:'Plus Jakarta Sans',sans-serif; display:flex; align-items:center; justify-content:center; min-height:100vh; margin:0; padding:20px; }
                .box { background:#FFFFFF; border-left:4px solid #C5A46D; padding:40px; max-width:480px; box-shadow:0 15px 35px rgba(0,0,0,0.06); border-radius:4px; text-align:center; }
                h1 { font-family:'Cormorant Garamond',serif; font-size:2rem; margin:0 0 10px; }
                p { color:#5E5853; font-size:0.95rem; line-height:1.5; margin-bottom:24px; }
                a { display:inline-block; background:#161413; color:#FAF7F2; text-decoration:none; padding:12px 24px; font-size:0.85rem; font-weight:600; text-transform:uppercase; letter-spacing:0.08em; border-radius:2px; }
              </style>
            </head>
            <body>
              <div class='box'>
                <h1>Demo Mode Deactivated</h1>
                <p>Interactive Demo Mode is currently disabled in the salon environment configuration (<code>DEMO_MODE=false</code>). Set <code>DEMO_MODE=true</code> in <code>.env</code> to activate.</p>
                <a href='../index.html'>&larr; Return to Sanctuary</a>
              </div>
            </body>
            </html>";
            exit;
        }
    }

    /**
     * Get base URL for demo routes
     */
    public static function baseUrl(): string {
        $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $proto . $host . '/Hair%20salon%20and%20spa/demo';
    }
}

/**
 * Isolated CSRF Token Manager for Demo Forms
 */
class DemoCsrf {
    public static function token(): string {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (empty($_SESSION['demo_csrf_token'])) {
            $_SESSION['demo_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['demo_csrf_token'];
    }

    public static function verify(?string $token): bool {
        if (empty($token) || session_status() === PHP_SESSION_NONE) {
            return false;
        }
        $validToken = $_SESSION['demo_csrf_token'] ?? null;
        if (!$validToken) {
            return false;
        }
        return hash_equals($validToken, (string)$token);
    }

    public static function field(): string {
        $token = htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="demo_csrf_token" value="' . $token . '">';
    }
}

/**
 * Isolated Authentication Manager for Demo Customer & Demo Admin
 */
class DemoAuth {
    /**
     * Authenticate Demo Customer
     */
    public static function attemptCustomer(string $email, string $password): array {
        $cleanEmail = strtolower(trim($email));
        $cleanPass  = trim($password);

        if ($cleanEmail === strtolower(DEMO_CUSTOMER_EMAIL) && $cleanPass === DEMO_CUSTOMER_PASS) {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            // Regenerate session id safely
            session_regenerate_id(true);

            $_SESSION['demo_customer_auth'] = true;
            $_SESSION['demo_user_role']     = 'customer';
            
            // Ensure customer profile data in session
            DemoStore::initSessionStore();
            $customer = DemoStore::getCustomer(DEMO_CUSTOMER_EMAIL);
            $_SESSION['demo_customer_profile'] = $customer;

            return ['success' => true, 'customer' => $customer];
        }

        return [
            'success' => false,
            'error'   => 'Invalid demo customer credentials. Use demo@salon.com / Demo@123.'
        ];
    }

    /**
     * Authenticate Demo Admin
     */
    public static function attemptAdmin(string $email, string $password): array {
        $cleanEmail = strtolower(trim($email));
        $cleanPass  = trim($password);

        if ($cleanEmail === strtolower(DEMO_ADMIN_EMAIL) && $cleanPass === DEMO_ADMIN_PASS) {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            session_regenerate_id(true);

            $_SESSION['demo_admin_auth'] = true;
            $_SESSION['demo_user_role']  = 'admin';
            $_SESSION['demo_admin_user'] = [
                'username'   => 'director_concierge',
                'full_name'  => 'Madame Vivienne Laurent',
                'role'       => 'admin',
                'role_title' => 'Salon Creative Director',
                'email'      => DEMO_ADMIN_EMAIL
            ];

            DemoStore::initSessionStore();

            return ['success' => true, 'admin' => $_SESSION['demo_admin_user']];
        }

        return [
            'success' => false,
            'error'   => 'Invalid demo admin credentials. Use admin@salon.com / Admin@123.'
        ];
    }

    public static function isCustomer(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        return !empty($_SESSION['demo_customer_auth']) && ($_SESSION['demo_user_role'] ?? '') === 'customer';
    }

    public static function isAdmin(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        return !empty($_SESSION['demo_admin_auth']) && ($_SESSION['demo_user_role'] ?? '') === 'admin';
    }

    public static function customer(): ?array {
        if (!self::isCustomer()) return null;
        DemoStore::initSessionStore();
        return DemoStore::getCustomer(DEMO_CUSTOMER_EMAIL);
    }

    public static function admin(): ?array {
        if (!self::isAdmin()) return null;
        return $_SESSION['demo_admin_user'] ?? [
            'username'   => 'director_concierge',
            'full_name'  => 'Madame Vivienne Laurent',
            'role'       => 'admin',
            'role_title' => 'Salon Creative Director',
            'email'      => DEMO_ADMIN_EMAIL
        ];
    }

    public static function requireCustomer(string $redirect = 'login.php'): array {
        if (!self::isCustomer()) {
            header("Location: {$redirect}?notice=" . urlencode("Please sign in to your Demo Customer Account."));
            exit;
        }
        return self::customer();
    }

    public static function requireAdmin(string $redirect = 'admin-login.php'): array {
        if (!self::isAdmin()) {
            header("Location: {$redirect}?notice=" . urlencode("Please sign in with Demo Admin credentials."));
            exit;
        }
        return self::admin();
    }

    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        unset(
            $_SESSION['demo_customer_auth'],
            $_SESSION['demo_admin_auth'],
            $_SESSION['demo_user_role'],
            $_SESSION['demo_customer_profile'],
            $_SESSION['demo_admin_user']
        );
    }
}

/**
 * Isolated In-Memory & Session Storage for Demo Mode
 * Zero MySQL database dependency!
 */
class DemoStore {
    public static function initSessionStore(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        if (empty($_SESSION['demo_store_initialized'])) {
            self::seedInitialDemoData();
        }
    }

    public static function seedInitialDemoData(): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        // 1. Demo Customer Profile
        $_SESSION['demo_customer_data'] = [
            'id'                => 101,
            'first_name'        => 'Adrienne',
            'last_name'         => 'Laurent',
            'email'             => DEMO_CUSTOMER_EMAIL,
            'phone'             => '+1 (555) 234-5678',
            'gender_preference' => "Women's Styling Focus",
            'allergies_notes'   => 'Sensitive scalp; prefers organic botanical washes and fragrance-free gloss.',
            'is_vip'            => 1,
            'created_at'        => '2026-01-15 10:30:00'
        ];

        // 2. Realistic Demo Appointments
        $today = date('Y-m-d');
        $tomorrow = date('Y-m-d', strtotime('+1 day'));
        $nextWeek = date('Y-m-d', strtotime('+4 days'));
        $pastDate1 = date('Y-m-d', strtotime('-12 days'));
        $pastDate2 = date('Y-m-d', strtotime('-28 days'));

        $_SESSION['demo_appointments'] = [
            [
                'id'                 => 1001,
                'booking_reference'  => 'DEMO-2026-8A4F1',
                'customer_id'        => 101,
                'customer_name'      => 'Adrienne Laurent',
                'customer_email'     => DEMO_CUSTOMER_EMAIL,
                'customer_phone'     => '+1 (555) 234-5678',
                'service_id'         => 3,
                'service_name'       => 'Japanese Head Spa & Scalp Cure',
                'staff_id'           => 1,
                'staff_name'         => 'Clara Valmont',
                'staff_role'         => 'Master Colorist & Spa Director',
                'appointment_date'   => $nextWeek,
                'appointment_time'   => '14:30:00',
                'duration_minutes'   => 75,
                'total_price'        => 120.00,
                'status'             => 'confirmed',
                'silent_appointment' => 1,
                'notes'              => 'Silent appointment requested. Organic lavender steam cure.',
                'created_at'         => date('Y-m-d H:i:s', strtotime('-2 days'))
            ],
            [
                'id'                 => 1002,
                'booking_reference'  => 'DEMO-2026-9B2C3',
                'customer_id'        => 101,
                'customer_name'      => 'Adrienne Laurent',
                'customer_email'     => DEMO_CUSTOMER_EMAIL,
                'customer_phone'     => '+1 (555) 234-5678',
                'service_id'         => 2,
                'service_name'       => 'Couture French Balayage',
                'staff_id'           => 2,
                'staff_name'         => 'Julian Moreau',
                'staff_role'         => 'Creative Director of Balayage',
                'appointment_date'   => $tomorrow,
                'appointment_time'   => '10:00:00',
                'duration_minutes'   => 150,
                'total_price'        => 195.00,
                'status'             => 'pending',
                'silent_appointment' => 0,
                'notes'              => 'Root blend and dimensional golden blonde gloss.',
                'created_at'         => date('Y-m-d H:i:s', strtotime('-1 day'))
            ],
            [
                'id'                 => 1003,
                'booking_reference'  => 'DEMO-2026-3X7Z9',
                'customer_id'        => 102,
                'customer_name'      => 'Marcus Vance',
                'customer_email'     => 'marcus.vance@example.com',
                'customer_phone'     => '+1 (555) 345-6789',
                'service_id'         => 11,
                'service_name'       => 'The Executive Revival Package',
                'staff_id'           => 4,
                'staff_name'         => 'Marcus Sterling',
                'staff_role'         => 'Master Barber Director',
                'appointment_date'   => $today,
                'appointment_time'   => '16:00:00',
                'duration_minutes'   => 90,
                'total_price'        => 135.00,
                'status'             => 'confirmed',
                'silent_appointment' => 0,
                'notes'              => 'Scissor trim, hot towel wrap & razor detailing.',
                'created_at'         => date('Y-m-d H:i:s', strtotime('-3 days'))
            ],
            [
                'id'                 => 1004,
                'booking_reference'  => 'DEMO-2026-5K1L2',
                'customer_id'        => 101,
                'customer_name'      => 'Adrienne Laurent',
                'customer_email'     => DEMO_CUSTOMER_EMAIL,
                'customer_phone'     => '+1 (555) 234-5678',
                'service_id'         => 1,
                'service_name'       => 'Artisanal Precision Haircut',
                'staff_id'           => 1,
                'staff_name'         => 'Clara Valmont',
                'staff_role'         => 'Master Colorist & Spa Director',
                'appointment_date'   => $pastDate1,
                'appointment_time'   => '11:30:00',
                'duration_minutes'   => 60,
                'total_price'        => 85.00,
                'status'             => 'completed',
                'silent_appointment' => 1,
                'notes'              => 'Botanical blowout with silk gloss.',
                'created_at'         => $pastDate1 . ' 10:00:00'
            ],
            [
                'id'                 => 1005,
                'booking_reference'  => 'DEMO-2026-1M9N8',
                'customer_id'        => 103,
                'customer_name'      => 'Sophia Dubois',
                'customer_email'     => 'sophia.d@example.com',
                'customer_phone'     => '+1 (555) 456-7890',
                'service_id'         => 5,
                'service_name'       => 'Cellular Rejuvenation Facial',
                'staff_id'           => 3,
                'staff_name'         => 'Elena Rostova',
                'staff_role'         => 'Head of Aesthetics & Wellness',
                'appointment_date'   => $pastDate2,
                'appointment_time'   => '15:00:00',
                'duration_minutes'   => 75,
                'total_price'        => 140.00,
                'status'             => 'completed',
                'silent_appointment' => 0,
                'notes'              => 'Peptide infusion and micro-current lifting.',
                'created_at'         => $pastDate2 . ' 09:30:00'
            ]
        ];

        // 3. Demo Services Catalog
        $_SESSION['demo_services'] = [
            ['id' => 1, 'slug' => 'haircut', 'name' => 'Artisanal Precision Haircut', 'price' => 85.00, 'duration' => '60 mins', 'category' => 'Hair Architecture'],
            ['id' => 2, 'slug' => 'balayage', 'name' => 'Couture French Balayage', 'price' => 195.00, 'duration' => '150 mins', 'category' => 'Color & Highlights'],
            ['id' => 3, 'slug' => 'hair-spa', 'name' => 'Japanese Head Spa & Scalp Cure', 'price' => 120.00, 'duration' => '75 mins', 'category' => 'Holistic Spa'],
            ['id' => 4, 'slug' => 'keratin', 'name' => 'Clean Botanical Keratin Treatment', 'price' => 260.00, 'duration' => '150 mins', 'category' => 'Hair Architecture'],
            ['id' => 5, 'slug' => 'facial', 'name' => 'Cellular Rejuvenation Facial', 'price' => 140.00, 'duration' => '75 mins', 'category' => 'Skin Wellness'],
            ['id' => 11, 'slug' => 'executive-package', 'name' => 'The Executive Revival Package', 'price' => 135.00, 'duration' => '90 mins', 'category' => 'Curated Packages'],
            ['id' => 12, 'slug' => 'occasion-package', 'name' => 'The Occasion Suite Package', 'price' => 295.00, 'duration' => '180 mins', 'category' => 'Curated Packages']
        ];

        // 4. Demo Staff
        $_SESSION['demo_staff'] = [
            ['id' => 1, 'slug' => 'clara', 'name' => 'Clara Valmont', 'role' => 'Master Colorist & Spa Director', 'experience' => '12 Years'],
            ['id' => 2, 'slug' => 'julian', 'name' => 'Julian Moreau', 'role' => 'Creative Director of Balayage', 'experience' => '10 Years'],
            ['id' => 3, 'slug' => 'elena', 'name' => 'Elena Rostova', 'role' => 'Head of Aesthetics & Wellness', 'experience' => '14 Years'],
            ['id' => 4, 'slug' => 'marcus', 'name' => 'Marcus Sterling', 'role' => 'Master Barber Director', 'experience' => '9 Years']
        ];

        $_SESSION['demo_store_initialized'] = true;
    }

    public static function getServices(): array {
        self::initSessionStore();
        return $_SESSION['demo_services'] ?? [];
    }

    public static function getStaff(): array {
        self::initSessionStore();
        return $_SESSION['demo_staff'] ?? [];
    }

    public static function getCustomer(string $email): ?array {
        self::initSessionStore();
        if (strtolower($email) === strtolower(DEMO_CUSTOMER_EMAIL)) {
            return $_SESSION['demo_customer_data'] ?? null;
        }
        return null;
    }

    public static function updateCustomer(array $data): array {
        self::initSessionStore();
        if (isset($data['first_name'])) $_SESSION['demo_customer_data']['first_name'] = trim($data['first_name']);
        if (isset($data['last_name']))  $_SESSION['demo_customer_data']['last_name']  = trim($data['last_name']);
        if (isset($data['phone']))      $_SESSION['demo_customer_data']['phone']      = trim($data['phone']);
        if (isset($data['gender_preference'])) $_SESSION['demo_customer_data']['gender_preference'] = $data['gender_preference'];
        if (isset($data['allergies_notes']))   $_SESSION['demo_customer_data']['allergies_notes']   = trim($data['allergies_notes']);

        return ['success' => true, 'customer' => $_SESSION['demo_customer_data']];
    }

    public static function getAppointments(?string $customerEmail = null, ?string $statusFilter = null, ?string $search = null): array {
        self::initSessionStore();
        $list = $_SESSION['demo_appointments'] ?? [];

        if ($customerEmail !== null) {
            $cleanEmail = strtolower(trim($customerEmail));
            $list = array_filter($list, function($apt) use ($cleanEmail) {
                return strtolower($apt['customer_email']) === $cleanEmail;
            });
        }

        if (!empty($statusFilter) && $statusFilter !== 'all') {
            $cleanStatus = strtolower(trim($statusFilter));
            $list = array_filter($list, function($apt) use ($cleanStatus) {
                return strtolower($apt['status']) === $cleanStatus;
            });
        }

        if (!empty($search)) {
            $q = strtolower(trim($search));
            $list = array_filter($list, function($apt) use ($q) {
                return str_contains(strtolower($apt['booking_reference']), $q) ||
                       str_contains(strtolower($apt['customer_name']), $q) ||
                       str_contains(strtolower($apt['service_name']), $q) ||
                       str_contains(strtolower($apt['staff_name']), $q);
            });
        }

        // Sort descending by appointment_date and appointment_time
        usort($list, function($a, $b) {
            $tA = strtotime($a['appointment_date'] . ' ' . $a['appointment_time']);
            $tB = strtotime($b['appointment_date'] . ' ' . $b['appointment_time']);
            return $tB <=> $tA;
        });

        return array_values($list);
    }

    public static function getAppointment(string $refOrId): ?array {
        self::initSessionStore();
        foreach ($_SESSION['demo_appointments'] as $apt) {
            if ($apt['booking_reference'] === $refOrId || (string)$apt['id'] === (string)$refOrId) {
                return $apt;
            }
        }
        return null;
    }

    public static function createAppointment(array $input): array {
        self::initSessionStore();

        $serviceSlug = $input['service_id'] ?? 'haircut';
        $services = $_SESSION['demo_services'] ?? [];
        $selectedService = $services[0];
        foreach ($services as $srv) {
            if ($srv['slug'] === $serviceSlug || (string)$srv['id'] === (string)$serviceSlug) {
                $selectedService = $srv;
                break;
            }
        }

        $staffSlug = $input['stylist_id'] ?? 'any';
        $staffMembers = $_SESSION['demo_staff'] ?? [];
        $selectedStaff = $staffMembers[0];
        foreach ($staffMembers as $stf) {
            if ($stf['slug'] === $staffSlug || (string)$stf['id'] === (string)$staffSlug) {
                $selectedStaff = $stf;
                break;
            }
        }
        if ($staffSlug === 'any' || empty($staffSlug)) {
            $selectedStaff = ['id' => 1, 'name' => 'Clara Valmont', 'role' => 'Master Colorist & Spa Director'];
        }

        $ref = 'DEMO-2026-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
        $newId = 1000 + count($_SESSION['demo_appointments']) + 1;

        $newAppointment = [
            'id'                 => $newId,
            'booking_reference'  => $ref,
            'customer_id'        => 101,
            'customer_name'      => trim($input['guest_name'] ?? 'Demo Client'),
            'customer_email'     => trim($input['guest_email'] ?? DEMO_CUSTOMER_EMAIL),
            'customer_phone'     => trim($input['guest_phone'] ?? '+1 (555) 234-5678'),
            'service_id'         => (int)$selectedService['id'],
            'service_name'       => $selectedService['name'],
            'staff_id'           => (int)$selectedStaff['id'],
            'staff_name'         => $selectedStaff['name'],
            'staff_role'         => $selectedStaff['role'],
            'appointment_date'   => $input['date'] ?? date('Y-m-d', strtotime('+3 days')),
            'appointment_time'   => $input['time'] ?? '14:00:00',
            'duration_minutes'   => (int)filter_var($selectedService['duration'], FILTER_SANITIZE_NUMBER_INT),
            'total_price'        => (float)$selectedService['price'],
            'status'             => 'pending',
            'silent_appointment' => !empty($input['silent_appointment']) ? 1 : 0,
            'notes'              => trim($input['notes'] ?? ''),
            'created_at'         => date('Y-m-d H:i:s')
        ];

        // Prepend new appointment to session
        array_unshift($_SESSION['demo_appointments'], $newAppointment);

        return [
            'success'           => true,
            'appointment'       => $newAppointment,
            'booking_reference' => $ref,
            'message'           => "Demo appointment [{$ref}] has been recorded in session memory."
        ];
    }

    public static function updateAppointmentStatus(string $refOrId, string $newStatus): bool {
        self::initSessionStore();
        $allowed = ['pending', 'confirmed', 'completed', 'cancelled'];
        if (!in_array($newStatus, $allowed, true)) {
            return false;
        }

        foreach ($_SESSION['demo_appointments'] as &$apt) {
            if ($apt['booking_reference'] === $refOrId || (string)$apt['id'] === (string)$refOrId) {
                $apt['status'] = $newStatus;
                $apt['updated_at'] = date('Y-m-d H:i:s');
                return true;
            }
        }
        return false;
    }

    public static function cancelAppointment(string $refOrId, string $reason = 'Cancelled via Demo Portal'): bool {
        self::initSessionStore();
        foreach ($_SESSION['demo_appointments'] as &$apt) {
            if ($apt['booking_reference'] === $refOrId || (string)$apt['id'] === (string)$refOrId) {
                $apt['status'] = 'cancelled';
                $apt['cancellation_reason'] = $reason;
                $apt['updated_at'] = date('Y-m-d H:i:s');
                return true;
            }
        }
        return false;
    }

    public static function getStats(): array {
        self::initSessionStore();
        $apts = $_SESSION['demo_appointments'] ?? [];

        $totalApts = count($apts);
        $pending = 0;
        $confirmed = 0;
        $completed = 0;
        $cancelled = 0;
        $revenue = 14250.00; // Baseline realistic demonstration revenue

        foreach ($apts as $apt) {
            switch ($apt['status']) {
                case 'pending':   $pending++; break;
                case 'confirmed': $confirmed++; break;
                case 'completed': 
                    $completed++; 
                    $revenue += (float)$apt['total_price'];
                    break;
                case 'cancelled': $cancelled++; break;
            }
        }

        return [
            'total_customers'       => 48,
            'total_appointments'    => $totalApts + 119, // realistic demonstration volume
            'upcoming_appointments' => $confirmed + $pending,
            'pending_appointments'  => $pending,
            'completed_appointments'=> $completed + 98,
            'cancelled_appointments'=> $cancelled + 7,
            'total_revenue'         => '$' . number_format($revenue, 2),
            'popular_services'      => [
                ['name' => 'Japanese Head Spa & Scalp Cure', 'count' => 42, 'revenue' => '$5,040.00'],
                ['name' => 'Couture French Balayage',         'count' => 34, 'revenue' => '$6,630.00'],
                ['name' => 'Artisanal Precision Haircut',     'count' => 28, 'revenue' => '$2,380.00'],
                ['name' => 'The Occasion Suite Package',      'count' => 19, 'revenue' => '$5,605.00'],
            ]
        ];
    }
}
