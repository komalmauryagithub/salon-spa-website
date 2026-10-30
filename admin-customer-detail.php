<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Patron Detailed Dossier & Treatment History
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 1. Guard check: Administrator clearance required
Auth::requireRole(['admin', 'manager', 'receptionist'], 'admin-login.php');

$currentUser = Auth::user();
$activeAdminPage = 'customers';
$statusMessage = null;
$statusError   = null;

$customerId = (int)($_GET['id'] ?? 0);
if ($customerId <= 0) {
    Response::redirect('admin-customers.php', 'Please select a valid patron dossier.', 'error');
}

try {
    $pdo = Database::getConnection();

    // 2. Handle POST Updates (VIP Toggle, Note update)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? null;
        if (!Csrf::verify($token)) {
            $statusError = "Security token validation failed. Please refresh the page.";
        } else {
            $action = $_POST['action'] ?? '';
            if ($action === 'toggle_vip') {
                $newVip = !empty($_POST['is_vip']) ? 1 : 0;
                $upStmt = $pdo->prepare("UPDATE customers SET is_vip = :vip, updated_at = NOW() WHERE id = :id");
                $upStmt->execute(['vip' => $newVip, 'id' => $customerId]);
                $statusMessage = $newVip ? "Patron elevated to VIP Patron Circle." : "Patron VIP status removed.";
                AppLogger::info("Admin #{$currentUser['id']} toggled VIP status for Customer #{$customerId} to {$newVip}");
            } elseif ($action === 'update_notes') {
                $notes = Validator::sanitizeString((string)($_POST['allergies_notes'] ?? ''));
                $upStmt = $pdo->prepare("UPDATE customers SET allergies_notes = :notes, updated_at = NOW() WHERE id = :id");
                $upStmt->execute(['notes' => $notes, 'id' => $customerId]);
                $statusMessage = "Patron sensory & allergy preferences updated.";
                AppLogger::info("Admin #{$currentUser['id']} updated notes for Customer #{$customerId}");
            }
        }
    }

    // 3. Load Customer Dossier
    $stmt = $pdo->prepare("
        SELECT 
            c.id,
            c.user_id,
            c.first_name,
            c.last_name,
            c.email,
            c.phone,
            c.gender_preference,
            c.allergies_notes,
            c.is_vip,
            c.created_at,
            c.updated_at,
            u.username AS linked_username,
            u.role AS user_role,
            u.is_active AS user_active
        FROM customers c
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $customerId]);
    $customer = $stmt->fetch();

    if (!$customer) {
        Response::redirect('admin-customers.php', "Patron #{$customerId} not found in atelier registry.", 'error');
    }

    // 4. Patron Lifetime Statistics
    $statsStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_bookings,
            SUM(CASE WHEN status IN ('confirmed', 'completed') THEN 1 ELSE 0 END) AS active_bookings,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_bookings,
            COALESCE(SUM(CASE WHEN status IN ('confirmed', 'completed') THEN total_price ELSE 0 END), 0) AS lifetime_spend
        FROM appointments
        WHERE customer_id = :id
    ");
    $statsStmt->execute(['id' => $customerId]);
    $patronStats = $statsStmt->fetch();

    // 5. Complete Appointment History
    $historyStmt = $pdo->prepare("
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
            COALESCE(s.name, o.title, 'Bespoke Ritual') AS service_name,
            st.full_name AS specialist_name
        FROM appointments a
        LEFT JOIN services s ON a.service_id = s.id
        LEFT JOIN offers o ON a.offer_id = o.id
        LEFT JOIN staff st ON a.staff_id = st.id
        WHERE a.customer_id = :id
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
    ");
    $historyStmt->execute(['id' => $customerId]);
    $appointments = $historyStmt->fetchAll();

} catch (Exception $e) {
    AppLogger::error("Customer detail query error: " . $e->getMessage());
    Response::redirect('admin-customers.php', "Database error loading patron dossier.", 'error');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patron Dossier: <?php echo e($customer['first_name'] . ' ' . $customer['last_name']); ?> — L'ÉTOILE</title>
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <style>
    .status-badge {
      display: inline-block;
      padding: 3px 10px;
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
    .dossier-card {
      background: var(--color-white);
      border: 1px solid var(--color-charcoal-border);
      border-radius: var(--radius-xs);
      padding: var(--space-xl);
      box-shadow: var(--shadow-subtle);
      margin-bottom: var(--space-xl);
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
    .kpi-stat-box {
      background: var(--color-ivory);
      border: 1px solid var(--color-champagne-border);
      border-radius: var(--radius-xs);
      padding: 14px;
      text-align: center;
    }
    .kpi-stat-val {
      font-family: var(--font-serif);
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--color-charcoal);
      line-height: 1;
      margin-top: 4px;
    }
    .kpi-stat-lbl {
      font-size: 0.72rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--color-charcoal-muted);
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
          <a href="admin-customers.php" style="font-size:0.82rem; color:var(--color-champagne-dark); font-weight:600; display:inline-flex; align-items:center; gap:4px; margin-bottom:6px;">
            &larr; Return to Patron Directory
          </a>
          <span class="eyebrow eyebrow-no-line">Patron Dossier &bull; Client ID #<?php echo str_pad((string)$customer['id'], 4, '0', STR_PAD_LEFT); ?></span>
          <h1 style="font-family: var(--font-serif); font-size: 2.3rem; color: var(--color-charcoal); margin: 4px 0 6px;">
            <?php echo e($customer['first_name'] . ' ' . $customer['last_name']); ?>
            <?php if (!empty($customer['is_vip'])): ?>
              <span style="font-size:1.1rem; color:var(--color-champagne-dark); font-weight:600; vertical-align:middle;">
                &#9733; VIP Patron Circle
              </span>
            <?php endif; ?>
          </h1>
          <p style="color: var(--color-charcoal-muted); margin: 0; font-size: 0.95rem;">
            Enrolled since <?php echo date('F j, Y', strtotime($customer['created_at'])); ?>
            <?php if (!empty($customer['linked_username'])): ?>
              &bull; Linked Self-Service User: <code>@<?php echo e($customer['linked_username']); ?></code>
            <?php endif; ?>
          </p>
        </div>
        <div style="display:flex; gap:8px;">
          <!-- VIP Status Toggle Form -->
          <form method="POST" action="admin-customer-detail.php?id=<?php echo $customerId; ?>" style="display:inline;">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="action" value="toggle_vip">
            <input type="hidden" name="is_vip" value="<?php echo empty($customer['is_vip']) ? '1' : '0'; ?>">
            <button type="submit" class="btn <?php echo empty($customer['is_vip']) ? 'btn-champagne' : 'btn-outline'; ?> btn-sm">
              <?php echo empty($customer['is_vip']) ? '★ Elevate to VIP Patron' : 'Remove VIP Status'; ?>
            </button>
          </form>
          <a href="book-appointment.html" target="_blank" class="btn btn-primary btn-sm">+ Book New Ritual</a>
        </div>
      </div>
    </div>
  </section>

  <!-- MAIN DOSSIER CONTENT -->
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

      <!-- 2-COLUMN LAYOUT -->
      <div style="display:grid; grid-template-columns: 1fr 2fr; gap:var(--space-2xl); align-items:start;" class="profile-layout-grid">
        
        <!-- LEFT: Patron Profile Card & Statistics -->
        <div>
          
          <!-- Patron Details Card -->
          <div class="dossier-card">
            <h3 style="font-family:var(--font-serif); font-size:1.4rem; margin:0 0 var(--space-md); border-bottom:1px solid var(--color-charcoal-border); padding-bottom:8px;">
              Profile Information
            </h3>

            <div style="display:flex; flex-direction:column; gap:12px; font-size:0.9rem;">
              <div>
                <span style="font-size:0.75rem; text-transform:uppercase; color:var(--color-charcoal-muted); display:block;">Full Name</span>
                <strong><?php echo e($customer['first_name'] . ' ' . $customer['last_name']); ?></strong>
              </div>

              <div>
                <span style="font-size:0.75rem; text-transform:uppercase; color:var(--color-charcoal-muted); display:block;">Telephone</span>
                <a href="tel:<?php echo e($customer['phone']); ?>" style="color:var(--color-champagne-dark); font-weight:600;"><?php echo e($customer['phone']); ?></a>
              </div>

              <div>
                <span style="font-size:0.75rem; text-transform:uppercase; color:var(--color-charcoal-muted); display:block;">Email Address</span>
                <a href="mailto:<?php echo e($customer['email']); ?>" style="color:inherit;"><?php echo e($customer['email']); ?></a>
              </div>

              <div>
                <span style="font-size:0.75rem; text-transform:uppercase; color:var(--color-charcoal-muted); display:block;">Styling Focus</span>
                <span style="padding:2px 8px; background:var(--color-ivory); border:1px solid var(--color-champagne-border); border-radius:2px; font-size:0.82rem; display:inline-block; margin-top:2px;">
                  <?php echo e($customer['gender_preference'] ?: 'Unspecified'); ?>
                </span>
              </div>

              <div>
                <span style="font-size:0.75rem; text-transform:uppercase; color:var(--color-charcoal-muted); display:block;">Atelier VIP Status</span>
                <?php if (!empty($customer['is_vip'])): ?>
                  <strong style="color:var(--color-champagne-dark);">&#9733; VIP Patron Circle Active</strong>
                <?php else: ?>
                  <span style="color:var(--color-charcoal-muted);">Standard Guest</span>
                <?php endif; ?>
              </div>
            </div>

            <!-- Notes Edit Form -->
            <div style="margin-top:var(--space-lg); padding-top:var(--space-md); border-top:1px dashed var(--color-charcoal-border);">
              <h4 style="font-family:var(--font-serif); font-size:1.15rem; margin-bottom:6px;">Allergies &amp; Sensory Notes</h4>
              <form method="POST" action="admin-customer-detail.php?id=<?php echo $customerId; ?>">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="action" value="update_notes">
                <textarea name="allergies_notes" class="form-control" style="min-height:90px; font-size:0.85rem; margin-bottom:8px;"><?php echo e($customer['allergies_notes'] ?? ''); ?></textarea>
                <button type="submit" class="btn btn-outline btn-sm" style="width:100%; justify-content:center;">Save Notes</button>
              </form>
            </div>

          </div>

          <!-- Lifetime Value KPI Cards -->
          <div class="dossier-card">
            <h3 style="font-family:var(--font-serif); font-size:1.25rem; margin:0 0 var(--space-md); border-bottom:1px solid var(--color-charcoal-border); padding-bottom:6px;">
              Patron Value Metrics
            </h3>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px;">
              <div class="kpi-stat-box">
                <div class="kpi-stat-lbl">Lifetime Spend</div>
                <div class="kpi-stat-val" style="color:#1E5128;">
                  $<?php echo number_format((float)($patronStats['lifetime_spend'] ?? 0), 0); ?>
                </div>
              </div>

              <div class="kpi-stat-box">
                <div class="kpi-stat-lbl">Total Bookings</div>
                <div class="kpi-stat-val">
                  <?php echo (int)($patronStats['total_bookings'] ?? 0); ?>
                </div>
              </div>

              <div class="kpi-stat-box">
                <div class="kpi-stat-lbl">Active Rituals</div>
                <div class="kpi-stat-val" style="color:var(--color-champagne-dark);">
                  <?php echo (int)($patronStats['active_bookings'] ?? 0); ?>
                </div>
              </div>

              <div class="kpi-stat-box">
                <div class="kpi-stat-lbl">Cancelled</div>
                <div class="kpi-stat-val" style="color:#9E2A2B;">
                  <?php echo (int)($patronStats['cancelled_bookings'] ?? 0); ?>
                </div>
              </div>
            </div>

          </div>

        </div>

        <!-- RIGHT: Complete Treatment History -->
        <div>
          <div class="dossier-card">
            <div style="display:flex; justify-content:space-between; align-items:baseline; border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-lg);">
              <div>
                <span class="eyebrow eyebrow-no-line">Appointment Ledger</span>
                <h3 style="font-family:var(--font-serif); font-size:1.55rem; margin:4px 0 0;">
                  Personal Treatment Timeline (<?php echo count($appointments); ?>)
                </h3>
              </div>
              <span style="font-size:0.85rem; color:var(--color-charcoal-muted);">
                Chronological order
              </span>
            </div>

            <?php if (empty($appointments)): ?>
              <div style="text-align:center; padding:var(--space-2xl) var(--space-md); color:var(--color-charcoal-muted);">
                <p>No appointments recorded for this patron yet.</p>
                <a href="book-appointment.html" target="_blank" class="btn btn-champagne btn-sm">+ Schedule First Appointment</a>
              </div>
            <?php else: ?>
              <div style="overflow-x:auto;">
                <table class="admin-table">
                  <thead>
                    <tr>
                      <th>Ref &amp; Date</th>
                      <th>Ritual Details</th>
                      <th>Specialist</th>
                      <th>Price</th>
                      <th>Status</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($appointments as $apt): ?>
                      <?php 
                        $aptStatus = strtolower((string)$apt['status']);
                        $aptDate   = date('D, M j, Y', strtotime($apt['appointment_date']));
                        $aptTime   = date('g:i A', strtotime($apt['appointment_time']));
                      ?>
                      <tr>
                        <td>
                          <strong style="font-family:monospace;"><?php echo e($apt['booking_reference']); ?></strong>
                          <div style="font-size:0.78rem; color:var(--color-champagne-dark); font-weight:600; margin-top:2px;">
                            <?php echo e($aptDate); ?> &bull; <?php echo e($aptTime); ?>
                          </div>
                        </td>
                        <td>
                          <div style="font-weight:600;"><?php echo e($apt['service_name']); ?></div>
                          <div style="font-size:0.75rem; color:var(--color-charcoal-muted);">
                            <?php echo (int)$apt['duration_minutes']; ?> mins duration
                            <?php if (!empty($apt['silent_appointment'])): ?>
                              &bull; <span style="color:var(--color-champagne-dark); font-weight:600;">Quiet Sanctuary</span>
                            <?php endif; ?>
                          </div>
                          <?php if (!empty($apt['cancellation_reason'])): ?>
                            <div style="font-size:0.72rem; color:#9E2A2B; margin-top:3px;">
                              Reason: <?php echo e($apt['cancellation_reason']); ?>
                            </div>
                          <?php endif; ?>
                        </td>
                        <td>
                          <?php echo e($apt['specialist_name'] ?: 'Any Specialist'); ?>
                        </td>
                        <td>
                          <strong style="color:var(--color-champagne-dark);">$<?php echo number_format((float)$apt['total_price'], 2); ?></strong>
                        </td>
                        <td>
                          <span class="status-badge status-<?php echo $aptStatus; ?>">
                            <?php echo e(ucfirst(str_replace('_', ' ', $aptStatus))); ?>
                          </span>
                        </td>
                        <td>
                          <button type="button" class="btn btn-outline btn-sm btn-inspect-appointment" data-id="<?php echo (int)$apt['id']; ?>" style="padding:3px 8px; font-size:0.72rem;">
                            Inspect
                          </button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            <?php endif; ?>

          </div>
        </div>

      </div>

    </div>
  </main>

  <!-- INSPECTION MODAL -->
  <div id="appointmentInspectionModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(37,35,33,0.7); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#FFFFFF; border:1px solid var(--color-champagne); border-radius:var(--radius-xs); max-width:540px; width:100%; padding:var(--space-2xl); box-shadow:var(--shadow-elevated); max-height:90vh; overflow-y:auto; position:relative;">
      
      <div style="display:flex; justify-content:space-between; align-items:flex-start; border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-lg);">
        <div>
          <span class="eyebrow eyebrow-no-line">Reservation Dossier</span>
          <h3 style="font-family:var(--font-serif); font-size:1.6rem; margin:4px 0 0;" id="modalDetailRef">Loading...</h3>
        </div>
        <button type="button" id="closeInspectModalBtn" style="background:none; border:none; font-size:1.5rem; cursor:pointer; color:var(--color-charcoal);">&times;</button>
      </div>

      <div id="modalDetailContent" style="font-size:0.88rem; color:var(--color-charcoal); line-height:1.6;">
        <div style="text-align:center; padding:30px 0;">Loading appointment dossier...</div>
      </div>

      <div style="margin-top:var(--space-xl); padding-top:var(--space-md); border-top:1px solid var(--color-charcoal-border); display:flex; justify-content:flex-end;">
        <button type="button" class="btn btn-outline btn-sm" id="closeInspectModalBtn2">Close</button>
      </div>

    </div>
  </div>

  <!-- FOOTER -->
  <footer class="site-footer" role="contentinfo">
    <div class="container footer-bottom-inner">
      <p>&copy; <?php echo date('Y'); ?> L'Étoile Luxury Unisex Hair Salon &amp; Spa &bull; Patron Management System.</p>
    </div>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
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
      inspectModal.addEventListener('click', (e) => {
        if (e.target === inspectModal) closeInspect();
      });

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
                  <div style="background:var(--color-ivory); padding:12px; border-radius:2px; border:1px solid var(--color-champagne-border); margin-bottom:14px; display:flex; justify-content:space-between; align-items:center;">
                    <div>Status: <span class="status-badge status-${d.status}">${d.status_label}</span></div>
                    <div>Fee: <strong style="color:var(--color-champagne-dark); font-size:1.1rem;">${d.formatted_price}</strong></div>
                  </div>
                  <div><strong>Ritual:</strong> ${d.service.name} (${d.service.category})</div>
                  <div><strong>Specialist:</strong> ${d.specialist.name} ${d.specialist.role ? '&bull; ' + d.specialist.role : ''}</div>
                  <div><strong>Date &amp; Time:</strong> ${d.formatted_date} at ${d.formatted_time}</div>
                  <div><strong>Duration:</strong> ${d.duration_minutes} minutes</div>
                  ${d.silent_appointment ? '<div style="color:var(--color-champagne-dark); font-weight:600; margin-top:4px;">Quiet Sanctuary Service Requested</div>' : ''}
                  ${d.notes ? `<div style="margin-top:8px; font-style:italic;"><strong>Guest Note:</strong> ${d.notes}</div>` : ''}
                  ${d.cancellation_reason ? `<div style="margin-top:8px; color:#9E2A2B;"><strong>Cancellation Reason:</strong> ${d.cancellation_reason}</div>` : ''}
                  <div style="margin-top:12px; font-size:0.75rem; color:var(--color-charcoal-muted); border-top:1px solid rgba(0,0,0,0.06); padding-top:8px;">
                    Created: ${d.created_at} &bull; Appointment ID #${d.id}
                  </div>
                `;
                inspectContent.innerHTML = html;
              } else {
                inspectContent.innerHTML = '<div style="color:#9E2A2B; text-align:center;">Could not load details.</div>';
              }
            })
            .catch(() => {
              inspectContent.innerHTML = '<div style="color:#9E2A2B; text-align:center;">Network error.</div>';
            });
        });
      });
    });
  </script>

</body>
</html>
