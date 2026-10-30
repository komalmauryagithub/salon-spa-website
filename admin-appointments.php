<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Concierge & Administrative Appointment Management Portal
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 1. Guard check: Must be administrative staff (admin, manager, receptionist)
Auth::requireRole(['admin', 'manager', 'receptionist'], 'admin-login.php');

$currentUser = Auth::user();
$statusMessage = null;
$statusError   = null;

// 2. Handle Server-side Status Change POST (Fallback)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!Csrf::verify($token)) {
        $statusError = "Security token validation failed. Please refresh the page.";
    } else {
        $action = $_POST['action'] ?? 'update_status';
        if ($action === 'update_status') {
            $aptId     = (int)($_POST['appointment_id'] ?? 0);
            $newStatus = trim((string)($_POST['new_status'] ?? ''));
            $reason    = !empty($_POST['cancellation_reason']) ? Validator::sanitizeString((string)$_POST['cancellation_reason']) : null;

            $allowedStatuses = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'];
            if ($aptId > 0 && in_array($newStatus, $allowedStatuses, true)) {
                try {
                    $pdo = Database::getConnection();
                    if ($newStatus === 'cancelled') {
                        $cReason = $reason ?: 'Cancelled by concierge administration';
                        $up = $pdo->prepare("UPDATE appointments SET status = :st, cancellation_reason = :cr, updated_at = NOW() WHERE id = :id");
                        $up->execute(['st' => $newStatus, 'cr' => $cReason, 'id' => $aptId]);
                    } else {
                        $up = $pdo->prepare("UPDATE appointments SET status = :st, updated_at = NOW() WHERE id = :id");
                        $up->execute(['st' => $newStatus, 'id' => $aptId]);
                    }
                    $statusMessage = "Appointment status updated to " . ucfirst(str_replace('_', ' ', $newStatus)) . ".";
                    AppLogger::info("Admin #{$currentUser['id']} updated appointment #{$aptId} status to {$newStatus}");
                } catch (Exception $e) {
                    AppLogger::error("Admin status update error: " . $e->getMessage());
                    $statusError = "An error occurred while updating the appointment.";
                }
            } else {
                $statusError = "Invalid appointment or target status.";
            }
        }
    }
}

// 3. Filters
$filterStatus = trim((string)($_GET['status'] ?? 'all'));
$filterStaff  = trim((string)($_GET['staff_id'] ?? 'all'));
$filterDate   = trim((string)($_GET['date_filter'] ?? 'all'));
$searchQuery  = trim((string)($_GET['q'] ?? ''));

try {
    $pdo = Database::getConnection();

    // Load active staff for filter dropdown
    $staffList = $pdo->query("SELECT id, full_name, role_title FROM staff ORDER BY display_order ASC")->fetchAll();

    // KPI Counters
    $kpis = [
        'total'     => 0,
        'pending'   => 0,
        'confirmed' => 0,
        'today'     => 0,
        'revenue'   => 0.00
    ];

    $kpiStmt = $pdo->query("
        SELECT 
            COUNT(*) AS total_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_count,
            SUM(CASE WHEN appointment_date = CURDATE() AND status NOT IN ('cancelled', 'no_show') THEN 1 ELSE 0 END) AS today_count,
            SUM(CASE WHEN status IN ('confirmed', 'completed') THEN total_price ELSE 0 END) AS total_revenue
        FROM appointments
    ");
    $kpiRow = $kpiStmt->fetch();
    if ($kpiRow) {
        $kpis['total']     = (int)$kpiRow['total_count'];
        $kpis['pending']   = (int)$kpiRow['pending_count'];
        $kpis['confirmed'] = (int)$kpiRow['confirmed_count'];
        $kpis['today']     = (int)$kpiRow['today_count'];
        $kpis['revenue']   = (float)$kpiRow['total_revenue'];
    }

    // Build Appointments Query
    $where = ["1=1"];
    $params = [];

    if ($filterStatus !== 'all' && in_array($filterStatus, ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'], true)) {
        $where[] = "a.status = :status";
        $params['status'] = $filterStatus;
    }

    if ($filterStaff !== 'all' && is_numeric($filterStaff)) {
        $where[] = "a.staff_id = :staff_id";
        $params['staff_id'] = (int)$filterStaff;
    }

    if ($filterDate === 'today') {
        $where[] = "a.appointment_date = CURDATE()";
    } elseif ($filterDate === 'upcoming') {
        $where[] = "a.appointment_date >= CURDATE()";
    } elseif ($filterDate === 'past') {
        $where[] = "a.appointment_date < CURDATE()";
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) {
        $where[] = "a.appointment_date = :specific_date";
        $params['specific_date'] = $filterDate;
    }

    if (!empty($searchQuery)) {
        $where[] = "(a.booking_reference LIKE :q OR c.first_name LIKE :q OR c.last_name LIKE :q OR c.email LIKE :q OR c.phone LIKE :q)";
        $params['q'] = "%{$searchQuery}%";
    }

    $whereSql = implode(' AND ', $where);

    $sql = "
        SELECT 
            a.id,
            a.booking_reference,
            a.appointment_date,
            a.appointment_time,
            a.duration_minutes,
            a.total_price,
            a.status,
            a.silent_appointment,
            a.notes,
            a.cancellation_reason,
            a.created_at,
            c.id AS customer_id,
            c.first_name,
            c.last_name,
            c.email AS customer_email,
            c.phone AS customer_phone,
            c.gender_preference,
            c.is_vip,
            COALESCE(s.name, o.title, 'Bespoke Ritual') AS service_name,
            st.id AS staff_id,
            st.full_name AS specialist_name,
            st.role_title AS specialist_role
        FROM appointments a
        JOIN customers c ON a.customer_id = c.id
        LEFT JOIN services s ON a.service_id = s.id
        LEFT JOIN offers o ON a.offer_id = o.id
        LEFT JOIN staff st ON a.staff_id = st.id
        WHERE {$whereSql}
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 100
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll();

} catch (Exception $e) {
    AppLogger::error("Admin appointments query error: " . $e->getMessage());
    $appointments = [];
    $staffList = [];
    $statusError = "Could not load appointments. Please ensure MySQL is running.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Concierge Desk &amp; Schedule — L'Étoile Luxury Unisex Hair Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Styles -->
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <style>
    .status-badge {
      display: inline-block;
      padding: 4px 10px;
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      border-radius: 20px;
    }
    .status-pending {
      background-color: var(--color-champagne-subtle);
      color: var(--color-champagne-dark);
      border: 1px solid var(--color-champagne-border);
    }
    .status-confirmed {
      background-color: var(--color-champagne);
      color: var(--color-charcoal);
    }
    .status-in_progress {
      background-color: #E2E8F0;
      color: #2D3748;
      border: 1px solid #CBD5E0;
    }
    .status-completed {
      background-color: var(--color-charcoal);
      color: var(--color-ivory);
    }
    .status-cancelled {
      background-color: rgba(201, 83, 83, 0.12);
      color: #9E2A2B;
      border: 1px solid rgba(201, 83, 83, 0.3);
    }
    .status-no_show {
      background-color: #EDF2F7;
      color: #718096;
      border: 1px solid #E2E8F0;
    }
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
    .kpi-value {
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
    .table-container {
      background: var(--color-white);
      border: 1px solid var(--color-charcoal-border);
      border-radius: var(--radius-xs);
      overflow-x: auto;
      box-shadow: var(--shadow-subtle);
    }
    .admin-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.88rem;
    }
    .admin-table th {
      background: var(--color-ivory);
      color: var(--color-charcoal);
      font-family: var(--font-serif);
      font-size: 0.95rem;
      font-weight: 600;
      padding: 12px 16px;
      border-bottom: 1px solid var(--color-charcoal-border);
      white-space: nowrap;
    }
    .admin-table td {
      padding: 14px 16px;
      border-bottom: 1px solid var(--color-charcoal-border);
      vertical-align: middle;
    }
    .admin-table tr:hover {
      background-color: rgba(245, 239, 230, 0.4);
    }
    .action-btn-group {
      display: inline-flex;
      gap: 6px;
      flex-wrap: wrap;
    }
    .action-btn {
      padding: 4px 8px;
      font-size: 0.72rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border-radius: 2px;
      cursor: pointer;
      border: 1px solid var(--color-charcoal-border);
      background: #FFFFFF;
      transition: all var(--transition-fast);
    }
    .action-btn:hover {
      border-color: var(--color-champagne);
      color: var(--color-champagne-dark);
    }
    .action-confirm {
      color: #1E5128;
      border-color: #8FD19E;
      background: #F4FAF5;
    }
    .action-confirm:hover {
      background: #1E5128;
      color: #FFFFFF;
    }
    .action-complete {
      color: var(--color-charcoal);
      border-color: var(--color-charcoal);
      background: #FFFFFF;
    }
    .action-complete:hover {
      background: var(--color-charcoal);
      color: var(--color-ivory);
    }
    .action-cancel {
      color: #9E2A2B;
      border-color: rgba(201,83,83,0.4);
      background: rgba(201,83,83,0.05);
    }
    .action-cancel:hover {
      background: #9E2A2B;
      color: #FFFFFF;
    }
  </style>
</head>
<body style="background-color: var(--color-ivory);">

  <!-- SHARED ADMIN NAVIGATION -->
  <?php 
    $activeAdminPage = 'appointments';
    require_once __DIR__ . '/includes/admin-nav.php'; 
  ?>

  <!-- DASHBOARD HERO -->
  <section style="background-color: var(--color-white); border-bottom: 1px solid var(--color-charcoal-border); padding: var(--space-xl) 0;">
    <div class="container">
      <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Atelier Master Schedule</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.2rem; color: var(--color-charcoal); margin: 4px 0 6px;">
            Concierge Appointment Desk
          </h1>
          <p style="color: var(--color-charcoal-muted); margin: 0; font-size: 0.95rem;">
            Real-time appointment schedule, specialist allocations, and ritual status controls.
          </p>
        </div>
        <div style="display:flex; gap:8px;">
          <a href="admin-appointments.php" class="btn btn-outline btn-sm">Refresh Schedule</a>
          <a href="book-appointment.html" class="btn btn-primary btn-sm" target="_blank">Walk-In Booking</a>
        </div>
      </div>

      <!-- KPI SUMMARY GRID -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:var(--space-md); margin-top:var(--space-xl);">
        
        <div class="kpi-card">
          <div class="kpi-label">Pending Review</div>
          <div class="kpi-value" style="color:var(--color-champagne-dark);"><?php echo $kpis['pending']; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Awaiting staff confirmation</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Today's Schedule</div>
          <div class="kpi-value"><?php echo $kpis['today']; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;"><?php echo date('l, M j'); ?></div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Confirmed Active</div>
          <div class="kpi-value" style="color:#1E5128;"><?php echo $kpis['confirmed']; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Ready for guest arrival</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Total Reservations</div>
          <div class="kpi-value"><?php echo $kpis['total']; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Recorded in atelier database</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Active Bookings Value</div>
          <div class="kpi-value" style="color:var(--color-charcoal);">$<?php echo number_format($kpis['revenue'], 0); ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Confirmed &amp; Completed</div>
        </div>

      </div>
    </div>
  </section>

  <!-- MAIN APPOINTMENTS DESK -->
  <main style="padding: var(--space-2xl) 0 var(--space-4xl);">
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

      <!-- FILTER CONTROLS BAR -->
      <div style="background:var(--color-white); border:1px solid var(--color-charcoal-border); border-radius:var(--radius-xs); padding:var(--space-lg); margin-bottom:var(--space-xl); box-shadow:var(--shadow-subtle);">
        <form method="GET" action="admin-appointments.php" style="display:flex; flex-wrap:wrap; gap:var(--space-md); align-items:flex-end;">
          
          <!-- Search Input -->
          <div style="flex:1; min-width:220px;">
            <label class="form-label" style="font-size:0.8rem;">Search Guest or Reference</label>
            <input type="text" name="q" class="form-control" style="font-size:0.88rem; padding:8px 12px;" placeholder="e.g. LETOILE, Adrienne, phone..." value="<?php echo e($searchQuery); ?>">
          </div>

          <!-- Status Filter -->
          <div style="min-width:160px;">
            <label class="form-label" style="font-size:0.8rem;">Status</label>
            <select name="status" class="form-control" style="font-size:0.88rem; padding:8px 12px;">
              <option value="all" <?php echo $filterStatus === 'all' ? 'selected' : ''; ?>>All Statuses</option>
              <option value="pending" <?php echo $filterStatus === 'pending' ? 'selected' : ''; ?>>Pending Review</option>
              <option value="confirmed" <?php echo $filterStatus === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
              <option value="in_progress" <?php echo $filterStatus === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
              <option value="completed" <?php echo $filterStatus === 'completed' ? 'selected' : ''; ?>>Completed</option>
              <option value="cancelled" <?php echo $filterStatus === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
              <option value="no_show" <?php echo $filterStatus === 'no_show' ? 'selected' : ''; ?>>No Show</option>
            </select>
          </div>

          <!-- Specialist Filter -->
          <div style="min-width:180px;">
            <label class="form-label" style="font-size:0.8rem;">Master Specialist</label>
            <select name="staff_id" class="form-control" style="font-size:0.88rem; padding:8px 12px;">
              <option value="all">All Specialists</option>
              <?php foreach ($staffList as $stf): ?>
                <option value="<?php echo (int)$stf['id']; ?>" <?php echo $filterStaff === (string)$stf['id'] ? 'selected' : ''; ?>>
                  <?php echo e($stf['full_name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Date Filter -->
          <div style="min-width:150px;">
            <label class="form-label" style="font-size:0.8rem;">Timeline</label>
            <select name="date_filter" class="form-control" style="font-size:0.88rem; padding:8px 12px;">
              <option value="all" <?php echo $filterDate === 'all' ? 'selected' : ''; ?>>All Dates</option>
              <option value="today" <?php echo $filterDate === 'today' ? 'selected' : ''; ?>>Today Only</option>
              <option value="upcoming" <?php echo $filterDate === 'upcoming' ? 'selected' : ''; ?>>Upcoming (>= Today)</option>
              <option value="past" <?php echo $filterDate === 'past' ? 'selected' : ''; ?>>Past History</option>
            </select>
          </div>

          <!-- Buttons -->
          <div style="display:flex; gap:8px;">
            <button type="submit" class="btn btn-primary btn-sm" style="padding:10px 18px;">
              <span>Filter</span>
            </button>
            <?php if ($filterStatus !== 'all' || $filterStaff !== 'all' || $filterDate !== 'all' || !empty($searchQuery)): ?>
              <a href="admin-appointments.php" class="btn btn-outline btn-sm" style="padding:10px 14px;">Reset</a>
            <?php endif; ?>
          </div>

        </form>
      </div>

      <!-- APPOINTMENTS TABLE -->
      <div class="table-container">
        <div style="padding:var(--space-md) var(--space-lg); border-bottom:1px solid var(--color-charcoal-border); display:flex; justify-content:space-between; align-items:center;">
          <h3 style="font-family:var(--font-serif); font-size:1.35rem; margin:0;">
            Showing <?php echo count($appointments); ?> Appointments
          </h3>
          <span style="font-size:0.82rem; color:var(--color-charcoal-muted);">
            Sorted by most recent schedule
          </span>
        </div>

        <?php if (empty($appointments)): ?>
          <div style="text-align:center; padding:var(--space-3xl) var(--space-md);">
            <p style="color:var(--color-charcoal-muted); font-size:0.95rem; margin-bottom:var(--space-md);">
              No appointments match your active filter criteria.
            </p>
            <a href="admin-appointments.php" class="btn btn-outline btn-sm">Clear All Filters</a>
          </div>
        <?php else: ?>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Booking Ref</th>
                <th>Guest Details</th>
                <th>Ritual &amp; Duration</th>
                <th>Specialist</th>
                <th>Date &amp; Time</th>
                <th>Price</th>
                <th>Status</th>
                <th>Concierge Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($appointments as $apt): ?>
                <?php 
                  $status = strtolower((string)$apt['status']);
                  $statusClass = 'status-' . $status;
                  $fDate = date('D, M j, Y', strtotime($apt['appointment_date']));
                  $fTime = date('g:i A', strtotime($apt['appointment_time']));
                  $isToday = ($apt['appointment_date'] === date('Y-m-d'));
                ?>
                <tr id="row-apt-<?php echo (int)$apt['id']; ?>" style="<?php echo $isToday ? 'background:rgba(197,164,109,0.06);' : ''; ?>">
                  
                  <!-- Booking Ref -->
                  <td>
                    <strong style="font-family:monospace; font-size:0.88rem; color:var(--color-charcoal);">
                      <?php echo e($apt['booking_reference']); ?>
                    </strong>
                    <div style="font-size:0.72rem; color:var(--color-charcoal-muted); margin-top:2px;">
                      #<?php echo (int)$apt['id']; ?> &bull; <?php echo date('M j, H:i', strtotime($apt['created_at'])); ?>
                    </div>
                  </td>

                  <!-- Guest -->
                  <td>
                    <div style="font-weight:600; color:var(--color-charcoal);">
                      <a href="admin-customer-detail.php?id=<?php echo (int)$apt['customer_id']; ?>" style="color:var(--color-charcoal); text-decoration:none;" title="View Patron Dossier">
                        <?php echo e($apt['first_name'] . ' ' . $apt['last_name']); ?>
                      </a>
                      <?php if (!empty($apt['is_vip'])): ?>
                        <span style="color:var(--color-champagne-dark); font-size:0.75rem;" title="VIP Patron">&#9733; VIP</span>
                      <?php endif; ?>
                    </div>
                    <div style="font-size:0.78rem; color:var(--color-charcoal-muted); margin-top:2px;">
                      <a href="tel:<?php echo e($apt['customer_phone']); ?>" style="color:inherit;"><?php echo e($apt['customer_phone']); ?></a>
                    </div>
                    <div style="font-size:0.75rem; color:var(--color-charcoal-muted);">
                      <?php echo e($apt['customer_email']); ?>
                    </div>
                  </td>

                  <!-- Ritual -->
                  <td>
                    <div style="font-weight:600;"><?php echo e($apt['service_name']); ?></div>
                    <div style="font-size:0.78rem; color:var(--color-charcoal-muted); margin-top:2px;">
                      <?php echo (int)$apt['duration_minutes']; ?> mins duration
                      <?php if (!empty($apt['silent_appointment'])): ?>
                        &bull; <span style="color:var(--color-champagne-dark); font-weight:600;">Quiet Sanctuary</span>
                      <?php endif; ?>
                    </div>
                    <?php if (!empty($apt['notes'])): ?>
                      <div style="font-size:0.72rem; color:var(--color-charcoal-muted); font-style:italic; max-width:200px; margin-top:2px;">
                        Note: <?php echo e($apt['notes']); ?>
                      </div>
                    <?php endif; ?>
                  </td>

                  <!-- Specialist -->
                  <td>
                    <span style="font-weight:600; color:var(--color-charcoal);">
                      <?php echo e($apt['specialist_name'] ?: 'Unassigned / Any'); ?>
                    </span>
                    <?php if (!empty($apt['specialist_role'])): ?>
                      <div style="font-size:0.72rem; color:var(--color-charcoal-muted);">
                        <?php echo e($apt['specialist_role']); ?>
                      </div>
                    <?php endif; ?>
                  </td>

                  <!-- Date & Time -->
                  <td>
                    <div style="font-weight:600; <?php echo $isToday ? 'color:var(--color-champagne-dark);' : ''; ?>">
                      <?php echo e($fDate); ?>
                    </div>
                    <div style="font-size:0.82rem; color:var(--color-charcoal-muted);">
                      <?php echo e($fTime); ?>
                    </div>
                  </td>

                  <!-- Price -->
                  <td>
                    <strong style="color:var(--color-champagne-dark); font-size:0.95rem;">
                      $<?php echo number_format((float)$apt['total_price'], 2); ?>
                    </strong>
                  </td>

                  <!-- Status -->
                  <td>
                    <span class="status-badge <?php echo $statusClass; ?>" id="badge-apt-<?php echo (int)$apt['id']; ?>">
                      <?php echo e(ucfirst(str_replace('_', ' ', $status))); ?>
                    </span>
                    <?php if ($status === 'cancelled' && !empty($apt['cancellation_reason'])): ?>
                      <div style="font-size:0.72rem; color:#9E2A2B; margin-top:4px; max-width:160px; line-height:1.2;">
                        <?php echo e($apt['cancellation_reason']); ?>
                      </div>
                    <?php endif; ?>
                  </td>

                  <!-- Actions -->
                  <td>
                    <div class="action-btn-group" id="actions-apt-<?php echo (int)$apt['id']; ?>">
                      
                      <button type="button" class="action-btn btn-inspect-appointment" data-id="<?php echo (int)$apt['id']; ?>" title="Inspect reservation details">
                        Inspect
                      </button>

                      <?php if ($status === 'pending'): ?>
                        <button type="button" class="action-btn action-confirm btn-update-status" data-id="<?php echo (int)$apt['id']; ?>" data-status="confirmed" title="Confirm Reservation">
                          Confirm
                        </button>
                      <?php endif; ?>

                      <?php if ($status === 'confirmed'): ?>
                        <button type="button" class="action-btn action-complete btn-update-status" data-id="<?php echo (int)$apt['id']; ?>" data-status="completed" title="Mark Ritual Completed">
                          Complete
                        </button>
                      <?php endif; ?>

                      <?php if (!in_array($status, ['completed', 'cancelled', 'no_show'], true)): ?>
                        <button type="button" class="action-btn action-cancel btn-open-cancel-modal" 
                                data-id="<?php echo (int)$apt['id']; ?>" 
                                data-ref="<?php echo e($apt['booking_reference']); ?>"
                                data-guest="<?php echo e($apt['first_name'] . ' ' . $apt['last_name']); ?>"
                                title="Cancel Reservation">
                          Cancel
                        </button>

                        <button type="button" class="action-btn btn-update-status" data-id="<?php echo (int)$apt['id']; ?>" data-status="no_show" style="color:#718096;" title="Mark No Show">
                          No Show
                        </button>
                      <?php endif; ?>

                    </div>
                  </td>

                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>

      </div>

    </div>
  </main>

  <!-- APPOINTMENT DETAILS INSPECTION MODAL -->
  <div id="appointmentInspectionModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(37,35,33,0.7); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#FFFFFF; border:1px solid var(--color-champagne); border-radius:var(--radius-xs); max-width:560px; width:100%; padding:var(--space-2xl); box-shadow:var(--shadow-elevated); max-height:90vh; overflow-y:auto; position:relative;">
      
      <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-lg);">
        <div>
          <span class="eyebrow eyebrow-no-line" id="modalDetailEyebrow">Reservation Inspection</span>
          <h3 style="font-family:var(--font-serif); font-size:1.6rem; margin:4px 0 0;" id="modalDetailRef">Loading...</h3>
        </div>
        <button type="button" id="closeInspectModalBtn" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--color-charcoal);">&times;</button>
      </div>

      <div id="modalDetailContent" style="font-size:0.88rem; color:var(--color-charcoal); line-height:1.6;">
        <div style="text-align:center; padding:30px 0;">Loading appointment dossier...</div>
      </div>

      <div style="margin-top:var(--space-xl); padding-top:var(--space-md); border-top:1px solid var(--color-charcoal-border); display:flex; justify-content:flex-end; gap:8px;" id="modalDetailActions">
        <button type="button" class="btn btn-outline btn-sm" id="closeInspectModalBtn2">Close</button>
      </div>

    </div>
  </div>

  <!-- ADMINISTRATIVE CANCELLATION MODAL -->
  <div id="adminCancelModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(37,35,33,0.7); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#FFFFFF; border:1px solid var(--color-champagne); border-radius:var(--radius-xs); max-width:460px; width:100%; padding:var(--space-2xl); box-shadow:var(--shadow-elevated);">
      
      <div style="border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-md);">
        <span class="eyebrow eyebrow-no-line" style="color:#9E2A2B;">Concierge Administrative Action</span>
        <h3 style="font-family:var(--font-serif); font-size:1.5rem; margin:4px 0 0;">Cancel Appointment</h3>
      </div>

      <p style="font-size:0.88rem; color:var(--color-charcoal-muted); margin-bottom:var(--space-md);">
        Cancelling appointment for <strong id="adminModalGuestName" style="color:var(--color-charcoal);"></strong> 
        (Ref: <span id="adminModalRef" style="font-family:monospace; font-weight:700;"></span>).
      </p>

      <form id="adminCancelForm" action="admin-appointments.php" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="appointment_id" id="adminCancelAptId" value="">
        <input type="hidden" name="new_status" value="cancelled">

        <div class="form-group" style="margin-bottom:var(--space-lg);">
          <label for="admin_cancel_reason" class="form-label" style="font-size:0.85rem;">Administrative Cancellation Reason</label>
          <select id="admin_cancel_reason" name="cancellation_reason" class="form-control" style="font-size:0.88rem;">
            <option value="Guest requested cancellation by phone">Guest requested cancellation by phone</option>
            <option value="Staff emergency / Master director unavailable">Staff emergency / Master director unavailable</option>
            <option value="Salon closure / Maintenance">Salon closure / Maintenance</option>
            <option value="Duplicate reservation / Guest reschedule">Duplicate reservation / Guest reschedule</option>
            <option value="Administrative reschedule required">Administrative reschedule required</option>
          </select>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:var(--space-sm);">
          <button type="button" id="adminCloseCancelModalBtn" class="btn btn-outline btn-sm">Close</button>
          <button type="submit" id="adminConfirmCancelBtn" class="btn btn-primary btn-sm" style="background:#9E2A2B; border-color:#9E2A2B; color:#FFFFFF;">
            Cancel Appointment
          </button>
        </div>
      </form>

    </div>
  </div>

  <!-- SITE FOOTER -->
  <footer class="site-footer" role="contentinfo">
    <div class="container footer-bottom-inner">
      <p>&copy; <?php echo date('Y'); ?> L'Étoile Luxury Unisex Hair Salon &amp; Spa &bull; Concierge Operations Panel.</p>
    </div>
  </footer>

  <!-- AJAX ACTIONS SCRIPT -->
  <script>
    const CSRF_TOKEN = '<?php echo Csrf::token(); ?>';

    document.addEventListener('DOMContentLoaded', () => {
      // Inspection Modal
      const inspectModal = document.getElementById('appointmentInspectionModal');
      const inspectClose = document.getElementById('closeInspectModalBtn');
      const inspectClose2 = document.getElementById('closeInspectModalBtn2');
      const inspectRef = document.getElementById('modalDetailRef');
      const inspectContent = document.getElementById('modalDetailContent');

      function closeInspect() {
        inspectModal.style.display = 'none';
      }
      if (inspectClose) inspectClose.addEventListener('click', closeInspect);
      if (inspectClose2) inspectClose2.addEventListener('click', closeInspect);
      if (inspectModal) {
        inspectModal.addEventListener('click', (e) => {
          if (e.target === inspectModal) closeInspect();
        });
      }

      document.querySelectorAll('.btn-inspect-appointment').forEach(btn => {
        btn.addEventListener('click', () => {
          const aptId = btn.getAttribute('data-id');
          inspectRef.textContent = 'Loading...';
          inspectContent.innerHTML = '<div style="text-align:center; padding:30px 0;">Loading appointment dossier...</div>';
          inspectModal.style.display = 'flex';

          fetch(`api/admin-appointment-detail.php?id=${aptId}`)
            .then(r => r.json())
            .then(res => {
              if (res && res.success && res.data) {
                const d = res.data;
                inspectRef.textContent = d.booking_reference;

                let html = `
                  <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; background:var(--color-ivory); padding:14px; border-radius:2px; border:1px solid var(--color-champagne-border);">
                    <div>
                      <div style="font-size:0.75rem; text-transform:uppercase; color:var(--color-charcoal-muted);">Status</div>
                      <span class="status-badge status-${d.status}">${d.status_label}</span>
                    </div>
                    <div>
                      <div style="font-size:0.75rem; text-transform:uppercase; color:var(--color-charcoal-muted);">Total Fee</div>
                      <strong style="color:var(--color-champagne-dark); font-size:1.15rem;">${d.formatted_price}</strong>
                    </div>
                  </div>

                  <div style="margin-bottom:14px;">
                    <h5 style="font-family:var(--font-serif); font-size:1.1rem; margin:0 0 6px; color:var(--color-charcoal);">Guest Information</h5>
                    <div><strong>Name:</strong> ${d.customer.full_name} ${d.customer.is_vip ? '<span style="color:var(--color-champagne-dark); font-weight:700;">&#9733; VIP Patron</span>' : ''}</div>
                    <div><strong>Phone:</strong> <a href="tel:${d.customer.phone}" style="color:var(--color-champagne-dark);">${d.customer.phone}</a></div>
                    <div><strong>Email:</strong> ${d.customer.email}</div>
                    <div><strong>Styling Focus:</strong> ${d.customer.gender_preference || 'Unspecified'}</div>
                    ${d.customer.allergies_notes ? `<div style="margin-top:4px; font-style:italic; font-size:0.82rem; color:var(--color-charcoal-muted);">Note: ${d.customer.allergies_notes}</div>` : ''}
                    <div style="margin-top:6px;"><a href="admin-customer-detail.php?id=${d.customer.id}" class="btn-action-sm" target="_blank">Open Full Customer Dossier &rarr;</a></div>
                  </div>

                  <div style="margin-bottom:14px; border-top:1px dashed var(--color-charcoal-border); padding-top:10px;">
                    <h5 style="font-family:var(--font-serif); font-size:1.1rem; margin:0 0 6px; color:var(--color-charcoal);">Treatment &amp; Schedule</h5>
                    <div><strong>Ritual:</strong> ${d.service.name} (${d.service.category})</div>
                    <div><strong>Duration:</strong> ${d.duration_minutes} minutes</div>
                    <div><strong>Specialist:</strong> ${d.specialist.name} ${d.specialist.role ? '&bull; ' + d.specialist.role : ''}</div>
                    <div><strong>Scheduled For:</strong> ${d.formatted_date} at ${d.formatted_time}</div>
                    ${d.silent_appointment ? '<div style="color:var(--color-champagne-dark); font-weight:600; margin-top:4px;">Quiet Sanctuary Service Requested</div>' : ''}
                  </div>

                  ${d.notes ? `
                    <div style="margin-bottom:14px; background:#FBF9F5; padding:10px; border-left:3px solid var(--color-champagne); font-size:0.84rem;">
                      <strong>Booking Notes:</strong> ${d.notes}
                    </div>
                  ` : ''}

                  ${d.cancellation_reason ? `
                    <div style="margin-bottom:14px; background:rgba(201,83,83,0.08); padding:10px; border-left:3px solid #9E2A2B; font-size:0.84rem; color:#9E2A2B;">
                      <strong>Cancellation Reason:</strong> ${d.cancellation_reason}
                    </div>
                  ` : ''}

                  <div style="font-size:0.75rem; color:var(--color-charcoal-muted); border-top:1px solid rgba(0,0,0,0.05); padding-top:8px;">
                    Booked: ${d.created_at} &bull; Appointment ID: #${d.id}
                  </div>
                `;
                inspectContent.innerHTML = html;
              } else {
                inspectContent.innerHTML = '<div style="color:#9E2A2B; text-align:center;">Could not load appointment details.</div>';
              }
            })
            .catch(() => {
              inspectContent.innerHTML = '<div style="color:#9E2A2B; text-align:center;">Network error loading details.</div>';
            });
        });
      });

      const cancelModal = document.getElementById('adminCancelModal');
      const closeCancelBtn = document.getElementById('adminCloseCancelModalBtn');
      const cancelForm = document.getElementById('adminCancelForm');
      const modalId = document.getElementById('adminCancelAptId');
      const modalRef = document.getElementById('adminModalRef');
      const modalGuest = document.getElementById('adminModalGuestName');

      // Open Cancel Modal
      document.querySelectorAll('.btn-open-cancel-modal').forEach(btn => {
        btn.addEventListener('click', () => {
          modalId.value = btn.getAttribute('data-id');
          modalRef.textContent = btn.getAttribute('data-ref');
          modalGuest.textContent = btn.getAttribute('data-guest');
          cancelModal.style.display = 'flex';
        });
      });

      // Close Cancel Modal
      function closeCancel() {
        cancelModal.style.display = 'none';
      }
      if (closeCancelBtn) closeCancelBtn.addEventListener('click', closeCancel);
      cancelModal.addEventListener('click', (e) => {
        if (e.target === cancelModal) closeCancel();
      });

      // Status Change Helper
      function changeStatus(aptId, targetStatus, reason = null) {
        return fetch('api/admin-appointment-status.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': CSRF_TOKEN
          },
          body: JSON.stringify({
            appointment_id: aptId,
            status: targetStatus,
            cancellation_reason: reason,
            csrf_token: CSRF_TOKEN
          })
        }).then(res => res.json());
      }

      // Quick Status Buttons (Confirm, Complete, No Show)
      document.querySelectorAll('.btn-update-status').forEach(btn => {
        btn.addEventListener('click', async () => {
          const aptId = btn.getAttribute('data-id');
          const targetStatus = btn.getAttribute('data-status');
          const originalText = btn.textContent;

          btn.disabled = true;
          btn.textContent = '...';

          try {
            const data = await changeStatus(aptId, targetStatus);
            if (data && data.success) {
              const badge = document.getElementById(`badge-apt-${aptId}`);
              if (badge) {
                badge.className = `status-badge status-${targetStatus}`;
                badge.textContent = data.status_label || targetStatus;
              }
              // Refresh page to re-compute KPIs and actions cleanly
              window.location.reload();
            } else {
              alert(data?.error || 'Could not update appointment status.');
              btn.disabled = false;
              btn.textContent = originalText;
            }
          } catch (e) {
            alert('A network error occurred.');
            btn.disabled = false;
            btn.textContent = originalText;
          }
        });
      });

      // Cancel Form AJAX Handler
      cancelForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const aptId = modalId.value;
        const reason = document.getElementById('admin_cancel_reason').value;
        const submitBtn = document.getElementById('adminConfirmCancelBtn');

        submitBtn.disabled = true;
        submitBtn.textContent = 'Cancelling...';

        try {
          const data = await changeStatus(aptId, 'cancelled', reason);
          if (data && data.success) {
            closeCancel();
            window.location.reload();
          } else {
            alert(data?.error || 'Cancellation could not be completed.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Cancel Appointment';
          }
        } catch (e) {
          cancelForm.submit(); // fallback
        }
      });
    });
  </script>

</body>
</html>
