<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Master Specialists & Staff Roster Administration
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 1. Guard check: Administrator clearance required
Auth::requireRole(['admin', 'manager', 'receptionist'], 'admin-login.php');

$currentUser = Auth::user();
$activeAdminPage = 'staff';
$statusMessage = null;
$statusError   = null;

$allDays = ['Mon' => 'Monday', 'Tue' => 'Tuesday', 'Wed' => 'Wednesday', 'Thu' => 'Thursday', 'Fri' => 'Friday', 'Sat' => 'Saturday', 'Sun' => 'Sunday'];

try {
    $pdo = Database::getConnection();

    // 2. Handle POST Actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? null;
        if (!Csrf::verify($token)) {
            $statusError = "Security token validation failed. Please refresh the page.";
        } else {
            $action = $_POST['action'] ?? '';

            // A. Create New Specialist
            if ($action === 'create_staff') {
                $fullName    = trim((string)($_POST['full_name'] ?? ''));
                $roleTitle   = trim((string)($_POST['role_title'] ?? ''));
                $rawSlug     = trim((string)($_POST['slug'] ?? ''));
                $bio         = trim((string)($_POST['bio'] ?? ''));
                $specialties = trim((string)($_POST['specialties'] ?? ''));
                $expYears    = (int)($_POST['experience_years'] ?? 5);
                $avatarUrl   = trim((string)($_POST['avatar_url'] ?? ''));
                $order       = (int)($_POST['display_order'] ?? 0);
                $isAvail     = !empty($_POST['is_available']) ? 1 : 0;
                $daysSelected = $_POST['working_days'] ?? ['Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                $eligibleServices = $_POST['service_ids'] ?? [];

                $workingDaysStr = implode(',', array_filter((array)$daysSelected, fn($d) => in_array($d, array_keys($allDays), true)));

                if (empty($fullName)) {
                    $statusError = "Specialist full name is required.";
                } elseif (empty($roleTitle)) {
                    $statusError = "Role title is required.";
                } else {
                    if (empty($rawSlug)) {
                        $rawSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $fullName), '-'));
                    } else {
                        $rawSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $rawSlug), '-'));
                    }

                    $chk = $pdo->prepare("SELECT id FROM staff WHERE slug = :slug LIMIT 1");
                    $chk->execute(['slug' => $rawSlug]);
                    if ($chk->fetch()) {
                        $rawSlug .= '-' . time();
                    }

                    $pdo->beginTransaction();
                    $ins = $pdo->prepare("
                        INSERT INTO staff (
                            slug, full_name, role_title, bio, specialties, experience_years,
                            avatar_url, working_days, is_available, display_order, created_at, updated_at
                        ) VALUES (
                            :slug, :name, :role, :bio, :spec, :exp,
                            :avatar, :days, :avail, :ord, NOW(), NOW()
                        )
                    ");
                    $ins->execute([
                        'slug'   => $rawSlug,
                        'name'   => $fullName,
                        'role'   => $roleTitle,
                        'bio'    => !empty($bio) ? $bio : 'Master artisan dedicated to haute coiffure and holistic wellness.',
                        'spec'   => !empty($specialties) ? $specialties : 'Bespoke Styling',
                        'exp'    => max(1, $expYears),
                        'avatar' => !empty($avatarUrl) ? $avatarUrl : 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=600&q=80',
                        'days'   => !empty($workingDaysStr) ? $workingDaysStr : 'Tue,Wed,Thu,Fri,Sat',
                        'avail'  => $isAvail,
                        'ord'    => $order
                    ]);
                    $newStaffId = (int)$pdo->lastInsertId();

                    // Insert service eligibility
                    if (!empty($eligibleServices) && is_array($eligibleServices)) {
                        $insSvc = $pdo->prepare("INSERT IGNORE INTO staff_services (staff_id, service_id, created_at) VALUES (:sid, :srvid, NOW())");
                        foreach ($eligibleServices as $srvId) {
                            $insSvc->execute(['sid' => $newStaffId, 'srvid' => (int)$srvId]);
                        }
                    }

                    $pdo->commit();
                    $statusMessage = "Specialist '{$fullName}' added to atelier roster successfully.";
                    AppLogger::info("Admin #{$currentUser['id']} created staff '{$fullName}' (#{$newStaffId})");
                }

            // B. Edit Specialist Profile & Services
            } elseif ($action === 'edit_staff') {
                $staffId     = (int)($_POST['staff_id'] ?? 0);
                $fullName    = trim((string)($_POST['full_name'] ?? ''));
                $roleTitle   = trim((string)($_POST['role_title'] ?? ''));
                $rawSlug     = trim((string)($_POST['slug'] ?? ''));
                $bio         = trim((string)($_POST['bio'] ?? ''));
                $specialties = trim((string)($_POST['specialties'] ?? ''));
                $expYears    = (int)($_POST['experience_years'] ?? 5);
                $avatarUrl   = trim((string)($_POST['avatar_url'] ?? ''));
                $order       = (int)($_POST['display_order'] ?? 0);
                $isAvail     = !empty($_POST['is_available']) ? 1 : 0;
                $daysSelected = $_POST['working_days'] ?? [];
                $eligibleServices = $_POST['service_ids'] ?? [];

                $workingDaysStr = implode(',', array_filter((array)$daysSelected, fn($d) => in_array($d, array_keys($allDays), true)));

                if ($staffId <= 0) {
                    $statusError = "Invalid specialist target specified.";
                } elseif (empty($fullName)) {
                    $statusError = "Specialist full name is required.";
                } elseif (empty($roleTitle)) {
                    $statusError = "Role title is required.";
                } else {
                    if (empty($rawSlug)) {
                        $rawSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $fullName), '-'));
                    } else {
                        $rawSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $rawSlug), '-'));
                    }

                    // Check slug uniqueness excluding self
                    $chk = $pdo->prepare("SELECT id FROM staff WHERE slug = :slug AND id != :id LIMIT 1");
                    $chk->execute(['slug' => $rawSlug, 'id' => $staffId]);
                    if ($chk->fetch()) {
                        $rawSlug .= '-' . time();
                    }

                    $pdo->beginTransaction();

                    $up = $pdo->prepare("
                        UPDATE staff SET
                            slug             = :slug,
                            full_name        = :name,
                            role_title       = :role,
                            bio              = :bio,
                            specialties      = :spec,
                            experience_years = :exp,
                            avatar_url       = :avatar,
                            working_days     = :days,
                            is_available     = :avail,
                            display_order    = :ord,
                            updated_at       = NOW()
                        WHERE id = :id
                    ");
                    $up->execute([
                        'slug'   => $rawSlug,
                        'name'   => $fullName,
                        'role'   => $roleTitle,
                        'bio'    => $bio,
                        'spec'   => $specialties,
                        'exp'    => max(1, $expYears),
                        'avatar' => $avatarUrl,
                        'days'   => !empty($workingDaysStr) ? $workingDaysStr : 'Tue,Wed,Thu,Fri,Sat',
                        'avail'  => $isAvail,
                        'ord'    => $order,
                        'id'     => $staffId
                    ]);

                    // Update service capabilities
                    $pdo->prepare("DELETE FROM staff_services WHERE staff_id = :sid")->execute(['sid' => $staffId]);
                    if (!empty($eligibleServices) && is_array($eligibleServices)) {
                        $insSvc = $pdo->prepare("INSERT INTO staff_services (staff_id, service_id, created_at) VALUES (:sid, :srvid, NOW())");
                        foreach ($eligibleServices as $srvId) {
                            $insSvc->execute(['sid' => $staffId, 'srvid' => (int)$srvId]);
                        }
                    }

                    $pdo->commit();
                    $statusMessage = "Specialist profile and treatment qualifications updated.";
                    AppLogger::info("Admin #{$currentUser['id']} updated staff #{$staffId}");
                }

            // C. Toggle Availability (Deactivation without corrupting appointments!)
            } elseif ($action === 'toggle_availability') {
                $staffId = (int)($_POST['staff_id'] ?? 0);
                $newAvail = !empty($_POST['is_available']) ? 1 : 0;
                if ($staffId > 0) {
                    $upStmt = $pdo->prepare("UPDATE staff SET is_available = :avail, updated_at = NOW() WHERE id = :id");
                    $upStmt->execute(['avail' => $newAvail, 'id' => $staffId]);
                    $statusMessage = $newAvail 
                        ? "Specialist activated for future appointment assignments." 
                        : "Specialist placed on sabbatical leave. All historical appointments remain linked and preserved.";
                    AppLogger::info("Admin #{$currentUser['id']} set staff #{$staffId} availability to {$newAvail}");
                }
            }
        }
    }

    // 3. Load All Active Services for Eligibility Matrix
    $allServices = $pdo->query("SELECT id, category_id, name, price, duration_minutes FROM services ORDER BY display_order ASC, name ASC")->fetchAll();

    // 4. Load Staff with Appointment Metrics & Assigned Services
    $sql = "
        SELECT 
            s.id,
            s.slug,
            s.full_name,
            s.role_title,
            s.bio,
            s.specialties,
            s.experience_years,
            s.avatar_url,
            s.working_days,
            s.is_available,
            s.display_order,
            COUNT(a.id) AS total_appointments,
            SUM(CASE WHEN a.appointment_date = CURDATE() AND a.status NOT IN ('cancelled', 'no_show') THEN 1 ELSE 0 END) AS today_appointments,
            SUM(CASE WHEN a.appointment_date >= CURDATE() AND a.status IN ('pending', 'confirmed') THEN 1 ELSE 0 END) AS active_upcoming
        FROM staff s
        LEFT JOIN appointments a ON s.id = a.staff_id
        GROUP BY s.id
        ORDER BY s.display_order ASC, s.full_name ASC
    ";
    $staff = $pdo->query($sql)->fetchAll();

    // Fetch staff_services for each specialist
    $staffServicesMap = [];
    $ssRows = $pdo->query("SELECT staff_id, service_id FROM staff_services")->fetchAll();
    foreach ($ssRows as $ssr) {
        $staffServicesMap[$ssr['staff_id']][] = (int)$ssr['service_id'];
    }

    $todayDayAbbr = date('D');
    $onDutyCount = 0;
    foreach ($staff as $stf) {
        $days = array_map('trim', explode(',', (string)$stf['working_days']));
        if ((int)$stf['is_available'] === 1 && in_array($todayDayAbbr, $days, true)) {
            $onDutyCount++;
        }
    }

} catch (Exception $e) {
    AppLogger::error("Staff administration error: " . $e->getMessage());
    $staff = [];
    $allServices = [];
    $staffServicesMap = [];
    $onDutyCount = 0;
    $statusError = "Could not load staff roster from database.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Specialists &amp; Staff Administration — L'ÉTOILE Luxury Hair Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="assets/css/style.css?v=3.5">
  <link rel="stylesheet" href="assets/css/responsive.css?v=3.5">
  <style>
    .kpi-card {
      background: var(--color-white);
      border: 1px solid var(--color-charcoal-border);
      border-radius: var(--radius-xs);
      padding: var(--space-lg);
      transition: border-color var(--transition-fast);
    }
    .kpi-card:hover {
      border-color: var(--color-champagne);
    }
    .kpi-val {
      font-family: var(--font-serif);
      font-size: 2.2rem;
      font-weight: 600;
      color: var(--color-charcoal);
      line-height: 1.1;
      margin-top: 4px;
    }
    .kpi-label {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--color-charcoal-muted);
    }
    .staff-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
      gap: var(--space-xl);
    }
    .staff-card {
      background: var(--color-white);
      border: 1px solid var(--color-charcoal-border);
      border-radius: var(--radius-xs);
      padding: var(--space-xl);
      box-shadow: var(--shadow-subtle);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .badge-duty {
      display: inline-block;
      padding: 3px 10px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      border-radius: 12px;
    }
    .badge-on-duty {
      background: #F4FAF5;
      color: #1E5128;
      border: 1px solid #8FD19E;
    }
    .badge-off-duty {
      background: #FBF9F5;
      color: var(--color-charcoal-muted);
      border: 1px solid var(--color-champagne-border);
    }
    .badge-sabbatical {
      background: #FFF5F5;
      color: #9E2A2B;
      border: 1px solid rgba(201,83,83,0.3);
    }
    .btn-action-sm {
      display: inline-block;
      padding: 5px 12px;
      font-size: 0.8rem;
      border-radius: 2px;
      cursor: pointer;
      border: 1px solid var(--color-champagne);
      background: transparent;
      color: var(--color-champagne-dark);
      text-decoration: none;
      font-weight: 600;
    }
    .btn-action-sm:hover {
      background: var(--color-champagne);
      color: var(--color-white);
    }
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(37,35,33,0.7);
      backdrop-filter: blur(4px);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal-dialog {
      background: #FFFFFF;
      border: 1px solid var(--color-champagne);
      border-radius: var(--radius-xs);
      max-width: 640px;
      width: 100%;
      padding: var(--space-2xl);
      box-shadow: var(--shadow-elevated);
      max-height: 90vh;
      overflow-y: auto;
    }
  </style>
</head>
<body style="background-color: var(--color-ivory);">

  <!-- SHARED ADMIN NAVIGATION -->
  <?php require_once __DIR__ . '/includes/admin-nav.php'; ?>

  <!-- HERO SECTION -->
  <section style="background-color: var(--color-white); border-bottom: 1px solid var(--color-charcoal-border); padding: var(--space-xl) 0;">
    <div class="container">
      <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Atelier Master Roster</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.2rem; color: var(--color-charcoal); margin: 4px 0 6px;">
            Specialists &amp; Stylist Management
          </h1>
          <p style="color: var(--color-charcoal-muted); margin: 0; font-size: 0.95rem;">
            Manage master profiles, weekly schedules, service eligibility, and availability. Historical records remain preserved.
          </p>
        </div>
        <div>
          <button type="button" id="btnOpenNewStaff" class="btn btn-champagne btn-sm">+ Add New Specialist</button>
        </div>
      </div>

      <!-- KPI METRICS -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:var(--space-md); margin-top:var(--space-xl);">
        
        <div class="kpi-card">
          <div class="kpi-label">On Duty Today (<?php echo date('l'); ?>)</div>
          <div class="kpi-val" style="color:var(--color-champagne-dark);"><?php echo $onDutyCount; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Available for walk-in &amp; online appointments</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Total Specialists</div>
          <div class="kpi-val"><?php echo count($staff); ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Master stylists, barbers &amp; spa directors</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Catalog Treatments</div>
          <div class="kpi-val"><?php echo count($allServices); ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Available for specialist qualification</div>
        </div>

      </div>

    </div>
  </section>

  <!-- MAIN STAFF CONTENT -->
  <main style="padding: var(--space-3xl) 0 var(--space-4xl);">
    <div class="container">

      <!-- Notifications -->
      <?php if ($statusMessage): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid #4E9F3D; padding: 14px 18px; margin-bottom: var(--space-xl); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="font-weight: 600; color: #1E5128; font-size: 0.92rem;"><?php echo e($statusMessage); ?></div>
        </div>
      <?php endif; ?>

      <?php if ($statusError): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid #C95353; padding: 14px 18px; margin-bottom: var(--space-xl); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="font-weight: 600; color: #9E2A2B; font-size: 0.92rem;"><?php echo e($statusError); ?></div>
        </div>
      <?php endif; ?>

      <!-- STAFF ROSTER GRID -->
      <div class="staff-grid">
        <?php foreach ($staff as $stf): ?>
          <?php 
            $isAvail   = (int)$stf['is_available'] === 1;
            $workDays  = array_map('trim', explode(',', (string)$stf['working_days']));
            $isOnDuty  = $isAvail && in_array($todayDayAbbr, $workDays, true);
            $specialistServices = $staffServicesMap[$stf['id']] ?? [];

            $staffPayload = [
                'id'               => $stf['id'],
                'slug'             => $stf['slug'],
                'full_name'        => $stf['full_name'],
                'role_title'       => $stf['role_title'],
                'bio'              => $stf['bio'],
                'specialties'      => $stf['specialties'],
                'experience_years' => $stf['experience_years'],
                'avatar_url'       => $stf['avatar_url'],
                'working_days'     => $workDays,
                'is_available'     => $stf['is_available'],
                'display_order'    => $stf['display_order'],
                'services'         => $specialistServices
            ];
            $jsonPayload = htmlspecialchars(json_encode($staffPayload), ENT_QUOTES, 'UTF-8');
          ?>
          <div class="staff-card">
            <div>
              <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:var(--space-md);">
                <div style="display:flex; gap:var(--space-md); align-items:center;">
                  <?php if (!empty($stf['avatar_url'])): ?>
                    <img src="<?php echo e($stf['avatar_url']); ?>" alt="<?php echo e($stf['full_name']); ?>" style="width:56px; height:56px; border-radius:50%; object-fit:cover; border:2px solid var(--color-champagne);">
                  <?php else: ?>
                    <div style="width:56px; height:56px; border-radius:50%; background:var(--color-champagne-subtle); display:flex; align-items:center; justify-content:center; color:var(--color-champagne-dark); font-weight:700;">
                      <?php echo e(substr($stf['full_name'], 0, 1)); ?>
                    </div>
                  <?php endif; ?>
                  <div>
                    <h3 style="font-family:var(--font-serif); font-size:1.35rem; margin:0 0 2px; color:var(--color-charcoal);">
                      <?php echo e($stf['full_name']); ?>
                    </h3>
                    <div style="font-size:0.82rem; color:var(--color-champagne-dark); font-weight:600;">
                      <?php echo e($stf['role_title']); ?>
                    </div>
                  </div>
                </div>

                <div>
                  <?php if (!$isAvail): ?>
                    <span class="badge-duty badge-sabbatical">Sabbatical</span>
                  <?php elseif ($isOnDuty): ?>
                    <span class="badge-duty badge-on-duty">On Duty Today</span>
                  <?php else: ?>
                    <span class="badge-duty badge-off-duty">Off Duty Today</span>
                  <?php endif; ?>
                </div>
              </div>

              <div style="font-size:0.85rem; color:var(--color-charcoal-muted); line-height:1.5; margin-bottom:var(--space-md);">
                <?php echo e($stf['bio']); ?>
              </div>

              <div style="margin-bottom:var(--space-sm); font-size:0.82rem;">
                <strong style="color:var(--color-charcoal);">Specialties:</strong> 
                <span style="color:var(--color-charcoal-muted);"><?php echo e($stf['specialties']); ?></span>
              </div>

              <div style="margin-bottom:var(--space-md); font-size:0.82rem;">
                <strong style="color:var(--color-charcoal);">Qualified Rituals:</strong>
                <span style="color:var(--color-champagne-dark); font-weight:600;">
                  <?php echo count($specialistServices); ?> treatment<?php echo count($specialistServices) === 1 ? '' : 's'; ?>
                </span>
              </div>

              <div style="margin-bottom:var(--space-lg);">
                <div style="font-size:0.75rem; text-transform:uppercase; color:var(--color-charcoal-muted); margin-bottom:6px; letter-spacing:0.04em;">Working Schedule</div>
                <div style="display:flex; gap:4px; flex-wrap:wrap;">
                  <?php foreach ($allDays as $abbr => $dayName): ?>
                    <?php $working = in_array($abbr, $workDays, true); ?>
                    <span style="font-size:0.72rem; padding:2px 6px; border-radius:2px; <?php echo $working ? 'background:var(--color-champagne-subtle); color:var(--color-champagne-dark); font-weight:700; border:1px solid var(--color-champagne-border);' : 'background:#F1F1F1; color:#A0AEC0;'; ?>">
                      <?php echo $abbr; ?>
                    </span>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- CARD FOOTER & ACTIONS -->
            <div style="border-top:1px solid var(--color-charcoal-border); padding-top:var(--space-md); display:flex; justify-content:space-between; align-items:center;">
              <div style="font-size:0.8rem; color:var(--color-charcoal-muted);">
                Today: <strong style="color:var(--color-charcoal);"><?php echo (int)$stf['today_appointments']; ?></strong> &bull; Upcoming: <strong><?php echo (int)$stf['active_upcoming']; ?></strong>
              </div>

              <div style="display:flex; gap:6px;">
                <button type="button" class="btn-action-sm btn-edit-staff" data-staff="<?php echo $jsonPayload; ?>">
                  Edit Profile
                </button>

                <form action="admin-staff.php" method="POST" style="display:inline;">
                  <?php echo csrf_field(); ?>
                  <input type="hidden" name="action" value="toggle_availability">
                  <input type="hidden" name="staff_id" value="<?php echo (int)$stf['id']; ?>">
                  <input type="hidden" name="is_available" value="<?php echo $isAvail ? '0' : '1'; ?>">
                  <button type="submit" class="btn-action-sm" style="color:<?php echo $isAvail ? '#9E2A2B' : '#1E5128'; ?>; border-color:currentColor;">
                    <?php echo $isAvail ? 'Leave' : 'Available'; ?>
                  </button>
                </form>
              </div>
            </div>

          </div>
        <?php endforeach; ?>
      </div>

    </div>
  </main>

  <!-- CREATE SPECIALIST MODAL -->
  <div id="createStaffModal" class="modal-overlay">
    <div class="modal-dialog">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Roster Expansion</span>
          <h3 style="font-family:var(--font-serif); font-size:1.5rem; margin:2px 0 0;">Add Master Specialist</h3>
        </div>
        <button type="button" class="btn-close-modal" data-target="createStaffModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
      </div>

      <form action="admin-staff.php" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="create_staff">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Full Name *</label>
            <input type="text" name="full_name" class="form-control" required placeholder="e.g. Jean-Luc Moreau">
          </div>
          <div>
            <label class="form-label">Role Title *</label>
            <input type="text" name="role_title" class="form-control" required placeholder="e.g. Master Balayage Artisan">
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Specialties (Comma-separated)</label>
            <input type="text" name="specialties" class="form-control" placeholder="French Balayage, Precision Cut">
          </div>
          <div>
            <label class="form-label">Experience (Years)</label>
            <input type="number" min="1" max="50" name="experience_years" class="form-control" value="8">
          </div>
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Editorial Biography</label>
          <textarea name="bio" class="form-control" rows="3" placeholder="Background, training in Paris or Tokyo, styling ethos..."></textarea>
        </div>

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Avatar Image URL</label>
            <input type="url" name="avatar_url" class="form-control" placeholder="https://images.unsplash.com/...">
          </div>
          <div>
            <label class="form-label">Display Order</label>
            <input type="number" name="display_order" class="form-control" value="0">
          </div>
        </div>

        <!-- WORKING DAYS SELECTION -->
        <div style="margin-bottom:14px; background:var(--color-ivory); padding:12px; border-radius:2px; border:1px solid var(--color-champagne-border);">
          <label class="form-label" style="margin-bottom:6px;">Weekly Schedule Days</label>
          <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <?php foreach ($allDays as $abbr => $dayName): ?>
              <label style="display:flex; align-items:center; gap:4px; font-size:0.85rem; cursor:pointer;">
                <input type="checkbox" name="working_days[]" value="<?php echo $abbr; ?>" <?php echo in_array($abbr, ['Tue','Wed','Thu','Fri','Sat'], true) ? 'checked' : ''; ?>>
                <?php echo $abbr; ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- SERVICE ELIGIBILITY -->
        <div style="margin-bottom:16px;">
          <label class="form-label" style="margin-bottom:6px;">Qualified Services (Service Eligibility)</label>
          <div style="max-height:160px; overflow-y:auto; border:1px solid var(--color-charcoal-border); padding:8px 12px; border-radius:2px; display:grid; grid-template-columns:1fr 1fr; gap:6px;">
            <?php foreach ($allServices as $svc): ?>
              <label style="display:flex; align-items:center; gap:6px; font-size:0.82rem; cursor:pointer;">
                <input type="checkbox" name="service_ids[]" value="<?php echo (int)$svc['id']; ?>" checked>
                <span><?php echo e($svc['name']); ?> ($<?php echo number_format((float)$svc['price'], 2); ?>)</span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center;">
          <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
            <input type="checkbox" name="is_available" value="1" checked> Active in Booking Registry
          </label>
          <div style="display:flex; gap:8px;">
            <button type="button" class="btn btn-outline btn-sm btn-close-modal" data-target="createStaffModal">Cancel</button>
            <button type="submit" class="btn btn-champagne btn-sm">Add Specialist</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT SPECIALIST MODAL -->
  <div id="editStaffModal" class="modal-overlay">
    <div class="modal-dialog">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Roster Maintenance</span>
          <h3 style="font-family:var(--font-serif); font-size:1.5rem; margin:2px 0 0;" id="editStaffHeading">Edit Specialist Profile</h3>
        </div>
        <button type="button" class="btn-close-modal" data-target="editStaffModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
      </div>

      <form action="admin-staff.php" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="edit_staff">
        <input type="hidden" name="staff_id" id="edit_staff_id" value="">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Full Name *</label>
            <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Role Title *</label>
            <input type="text" name="role_title" id="edit_role_title" class="form-control" required>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Slug</label>
            <input type="text" name="slug" id="edit_slug" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Experience (Years)</label>
            <input type="number" min="1" max="50" name="experience_years" id="edit_experience_years" class="form-control">
          </div>
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Specialties</label>
          <input type="text" name="specialties" id="edit_specialties" class="form-control">
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Editorial Biography</label>
          <textarea name="bio" id="edit_bio" class="form-control" rows="3"></textarea>
        </div>

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Avatar URL</label>
            <input type="url" name="avatar_url" id="edit_avatar_url" class="form-control">
          </div>
          <div>
            <label class="form-label">Display Order</label>
            <input type="number" name="display_order" id="edit_display_order" class="form-control">
          </div>
        </div>

        <!-- WORKING DAYS SELECTION -->
        <div style="margin-bottom:14px; background:var(--color-ivory); padding:12px; border-radius:2px; border:1px solid var(--color-champagne-border);">
          <label class="form-label" style="margin-bottom:6px;">Weekly Schedule Days</label>
          <div style="display:flex; gap:12px; flex-wrap:wrap;">
            <?php foreach ($allDays as $abbr => $dayName): ?>
              <label style="display:flex; align-items:center; gap:4px; font-size:0.85rem; cursor:pointer;">
                <input type="checkbox" name="working_days[]" value="<?php echo $abbr; ?>" id="edit_day_<?php echo $abbr; ?>">
                <?php echo $abbr; ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- SERVICE ELIGIBILITY -->
        <div style="margin-bottom:16px;">
          <label class="form-label" style="margin-bottom:6px;">Qualified Services (Service Eligibility)</label>
          <div style="max-height:160px; overflow-y:auto; border:1px solid var(--color-charcoal-border); padding:8px 12px; border-radius:2px; display:grid; grid-template-columns:1fr 1fr; gap:6px;">
            <?php foreach ($allServices as $svc): ?>
              <label style="display:flex; align-items:center; gap:6px; font-size:0.82rem; cursor:pointer;">
                <input type="checkbox" name="service_ids[]" value="<?php echo (int)$svc['id']; ?>" class="edit-svc-cb" id="edit_svc_<?php echo (int)$svc['id']; ?>">
                <span><?php echo e($svc['name']); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center;">
          <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
            <input type="checkbox" name="is_available" id="edit_is_available" value="1"> Active in Booking Registry
          </label>
          <div style="display:flex; gap:8px;">
            <button type="button" class="btn btn-outline btn-sm btn-close-modal" data-target="editStaffModal">Cancel</button>
            <button type="submit" class="btn btn-champagne btn-sm">Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- FOOTER -->
  <footer class="site-footer" role="contentinfo">
    <div class="container footer-bottom-inner">
      <p>&copy; <?php echo date('Y'); ?> L'ÉTOILE Luxury Unisex Hair Salon &amp; Spa &bull; Concierge Operations Panel.</p>
    </div>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Create Modal
      const btnNewStaff = document.getElementById('btnOpenNewStaff');
      const modalCreateStaff = document.getElementById('createStaffModal');
      if (btnNewStaff && modalCreateStaff) {
        btnNewStaff.addEventListener('click', () => {
          modalCreateStaff.style.display = 'flex';
        });
      }

      // Close Modals
      document.querySelectorAll('.btn-close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
          const targetId = btn.getAttribute('data-target');
          const targetModal = document.getElementById(targetId);
          if (targetModal) targetModal.style.display = 'none';
        });
      });

      document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
          if (e.target === modal) modal.style.display = 'none';
        });
      });

      // Edit Modal Population
      const editModal = document.getElementById('editStaffModal');
      const allDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

      document.querySelectorAll('.btn-edit-staff').forEach(btn => {
        btn.addEventListener('click', () => {
          try {
            const data = JSON.parse(btn.getAttribute('data-staff'));
            document.getElementById('edit_staff_id').value = data.id;
            document.getElementById('edit_full_name').value = data.full_name || '';
            document.getElementById('edit_role_title').value = data.role_title || '';
            document.getElementById('edit_slug').value = data.slug || '';
            document.getElementById('edit_specialties').value = data.specialties || '';
            document.getElementById('edit_experience_years').value = data.experience_years || 5;
            document.getElementById('edit_bio').value = data.bio || '';
            document.getElementById('edit_avatar_url').value = data.avatar_url || '';
            document.getElementById('edit_display_order').value = data.display_order || 0;
            document.getElementById('edit_is_available').checked = parseInt(data.is_available) === 1;
            document.getElementById('editStaffHeading').textContent = 'Edit ' + data.full_name;

            // Check working days
            allDays.forEach(d => {
              const cb = document.getElementById('edit_day_' + d);
              if (cb) {
                cb.checked = Array.isArray(data.working_days) && data.working_days.includes(d);
              }
            });

            // Check services
            document.querySelectorAll('.edit-svc-cb').forEach(scb => {
              const svcId = parseInt(scb.value);
              scb.checked = Array.isArray(data.services) && data.services.includes(svcId);
            });

            editModal.style.display = 'flex';
          } catch (e) {
            alert('Could not parse specialist profile data.');
          }
        });
      });
    });
  </script>

</body>
</html>
