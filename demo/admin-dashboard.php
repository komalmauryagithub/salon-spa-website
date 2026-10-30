<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Demo Executive Admin Management Dashboard
 * 
 * Strict Brand Theme: Obsidian Charcoal #161413, Champagne Gold #D4AF37, Ivory #FAF7F2, White #FFFFFF
 */

require_once __DIR__ . '/../includes/bootstrap.php';
Demo::requireEnabled();
DemoStore::initSessionStore();

// 1. Guard Check: Must be authenticated Demo Admin (prevents customer access)
$admin = DemoAuth::requireAdmin('admin-login.php');

$notice = null;
$error = null;

// 2. Handle Status Transitions via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['demo_csrf_token'] ?? null;
    if (!DemoCsrf::verify($token)) {
        $error = 'Security session expired. Please refresh the page.';
    } else {
        $action = $_POST['action'] ?? '';
        $ref = trim((string)($_POST['booking_reference'] ?? ''));

        if ($action === 'update_status') {
            $newStatus = trim((string)($_POST['new_status'] ?? ''));
            if (DemoStore::updateAppointmentStatus($ref, $newStatus)) {
                $notice = "Appointment [{$ref}] status successfully updated to " . strtoupper($newStatus) . ".";
            } else {
                $error = "Appointment record could not be updated.";
            }
        }
    }
}

// 3. Filters
$statusFilter = $_GET['status'] ?? 'all';
$searchQuery  = $_GET['search'] ?? '';

// 4. Load Dynamic Stats and Filtered Appointments from Session Store
$stats = DemoStore::getStats();
$appointments = DemoStore::getAppointments(null, $statusFilter, $searchQuery);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Concierge Dashboard (Demo Mode) — L'Étoile Luxury Unisex Hair Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  
  <style>
    .stat-card {
      background: var(--color-white);
      border: 1px solid var(--color-charcoal-border);
      border-radius: var(--radius-xs);
      padding: var(--space-xl);
      box-shadow: var(--shadow-subtle);
      transition: transform var(--transition-fast), border-color var(--transition-fast);
      position: relative;
    }
    .stat-card:hover {
      transform: translateY(-2px);
      border-color: var(--color-champagne);
    }
    .stat-label {
      font-size: 0.76rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--color-charcoal-muted);
      margin-bottom: 6px;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .stat-val {
      font-family: var(--font-serif);
      font-size: 2.1rem;
      color: var(--color-charcoal);
      line-height: 1.1;
      margin-bottom: 4px;
    }
    .stat-desc {
      font-size: 0.78rem;
      color: var(--color-charcoal-muted);
    }
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
      background-color: rgba(212,175,55,0.15);
      color: var(--color-champagne-dark);
      border: 1px solid var(--color-champagne);
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
      background-color: rgba(201, 83, 83, 0.1);
      color: #9E2A2B;
      border: 1px solid rgba(201, 83, 83, 0.3);
    }
    .admin-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.88rem;
      background: var(--color-white);
      border-radius: var(--radius-xs);
      overflow: hidden;
    }
    .admin-table th {
      background: var(--color-ivory);
      padding: 12px 16px;
      text-align: left;
      font-size: 0.74rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--color-charcoal);
      border-bottom: 1px solid var(--color-charcoal-border);
    }
    .admin-table td {
      padding: 14px 16px;
      border-bottom: 1px solid var(--color-charcoal-border);
      vertical-align: middle;
    }
    .admin-table tr:hover td {
      background-color: rgba(212,175,55,0.03);
    }
    .btn-action {
      padding: 4px 10px;
      font-size: 0.72rem;
      font-weight: 600;
      border-radius: 2px;
      cursor: pointer;
      text-decoration: none;
      border: 1px solid transparent;
      display: inline-flex;
      align-items: center;
      gap: 3px;
    }
  </style>
</head>
<body style="background-color: var(--color-ivory);">

  <!-- TOP BAR -->
  <div class="top-bar" style="background-color: #0F0E0D; border-bottom: 1px solid var(--color-champagne-border);">
    <div class="container top-bar-inner">
      <div class="top-bar-left">
        <span class="top-bar-item">
          <span style="color:var(--color-champagne-light);">👑 <strong>EXECUTIVE CONCIERGE DEMO</strong> &bull; Zero Live Database Required</span>
        </span>
      </div>
      <div class="top-bar-right">
        <span class="top-bar-item">
          <span>Signed in as <strong><?php echo htmlspecialchars($admin['full_name'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
        </span>
        <span class="top-bar-item">
          <a href="logout.php" style="color:var(--color-champagne-light); font-weight:600;">Sign Out</a>
        </span>
      </div>
    </div>
  </div>

  <!-- SITE HEADER -->
  <header class="site-header" role="banner" style="background:#FFFFFF; border-bottom:1px solid var(--color-charcoal-border);">
    <div class="container nav-container">
      <a href="index.php" class="brand" aria-label="L'Étoile Home">
        <span class="brand-title">L'ÉTOILE</span>
        <span class="brand-subtitle">EXECUTIVE CONCIERGE &bull; DEMO</span>
      </a>

      <nav class="nav-links" id="primary-navigation" aria-label="Main Navigation">
        <a href="index.php" class="nav-link">Demo Hub</a>
        <a href="admin-dashboard.php" class="nav-link active">Overview</a>
        <a href="book-appointment.php" class="nav-link">Book Ritual</a>
        <a href="customer-profile.php" class="nav-link">Client Portal</a>
      </nav>

      <div class="nav-cta" style="display:flex; gap:8px;">
        <a href="index.php?action=reset" class="btn btn-outline btn-sm" onclick="return confirm('Reset demo store records to default baseline?');">
          ↺ Reset Demo Data
        </a>
        <a href="logout.php" class="btn btn-primary btn-sm">Sign Out</a>
      </div>
    </div>
  </header>

  <!-- DASHBOARD HERO -->
  <section style="background-color: var(--color-white); border-bottom: 1px solid var(--color-charcoal-border); padding: var(--space-2xl) 0;">
    <div class="container">
      <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Executive Management Console</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.3rem; color: var(--color-charcoal); margin: 4px 0 8px;">
            Concierge Atelier <em>Overview</em>
          </h1>
          <p style="color: var(--color-charcoal-muted); margin: 0; font-size: 0.95rem;">
            Real-time demonstration salon revenue, appointment pipeline moderation, and clientele records.
          </p>
        </div>
        <div>
          <span class="luxury-pill" style="font-size:0.75rem;">🎭 Isolated Session Memory &bull; DEMO DATA</span>
        </div>
      </div>
    </div>
  </section>

  <!-- MAIN DASHBOARD CONTENT -->
  <main style="padding: var(--space-3xl) 0 var(--space-4xl);">
    <div class="container">

      <!-- Notifications -->
      <?php if ($notice): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid var(--color-champagne); padding: 14px 18px; margin-bottom: var(--space-xl); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="font-weight: 600; font-size: 0.92rem; color: var(--color-charcoal);"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid #C95353; padding: 14px 18px; margin-bottom: var(--space-xl); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="color: #9E2A2B; font-weight: 600; font-size: 0.92rem;"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endif; ?>

      <!-- 6 METRIC CARDS -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--space-md); margin-bottom: var(--space-3xl);">
        
        <div class="stat-card">
          <div class="stat-label">
            <span>Demo Revenue</span>
            <span style="color:var(--color-champagne-dark); font-size:1.1rem;">💰</span>
          </div>
          <div class="stat-val"><?php echo $stats['total_revenue']; ?></div>
          <div class="stat-desc">Completed demonstration rituals</div>
        </div>

        <div class="stat-card">
          <div class="stat-label">
            <span>Total Volume</span>
            <span style="color:var(--color-champagne-dark); font-size:1.1rem;">📅</span>
          </div>
          <div class="stat-val"><?php echo $stats['total_appointments']; ?></div>
          <div class="stat-desc">All scheduled appointments</div>
        </div>

        <div class="stat-card">
          <div class="stat-label">
            <span>Upcoming / Confirmed</span>
            <span style="color:var(--color-champagne-dark); font-size:1.1rem;">⏳</span>
          </div>
          <div class="stat-val"><?php echo $stats['upcoming_appointments']; ?></div>
          <div class="stat-desc">Scheduled in session itinerary</div>
        </div>

        <div class="stat-card">
          <div class="stat-label">
            <span>Pending Review</span>
            <span style="color:#C5A46D; font-size:1.1rem;">🛎</span>
          </div>
          <div class="stat-val"><?php echo $stats['pending_appointments']; ?></div>
          <div class="stat-desc">Awaiting concierge action</div>
        </div>

        <div class="stat-card">
          <div class="stat-label">
            <span>Completed</span>
            <span style="color:#2D6A4F; font-size:1.1rem;">✓</span>
          </div>
          <div class="stat-val"><?php echo $stats['completed_appointments']; ?></div>
          <div class="stat-desc">Finished salon treatments</div>
        </div>

        <div class="stat-card">
          <div class="stat-label">
            <span>Client Base</span>
            <span style="color:var(--color-champagne-dark); font-size:1.1rem;">👥</span>
          </div>
          <div class="stat-val"><?php echo $stats['total_customers']; ?></div>
          <div class="stat-desc">Registered VIP patrons</div>
        </div>

      </div>

      <!-- APPOINTMENT MODERATION SECTION -->
      <div style="background: var(--color-white); border: 1px solid var(--color-charcoal-border); border-radius: var(--radius-xs); padding: var(--space-2xl); box-shadow: var(--shadow-subtle); margin-bottom: var(--space-3xl);">
        
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-md); margin-bottom: var(--space-xl);">
          <div>
            <span class="eyebrow eyebrow-no-line">Interactive Scheduling Control</span>
            <h2 style="font-family: var(--font-serif); font-size: 1.8rem; color: var(--color-charcoal); margin: 2px 0 4px;">
              Live Appointment Moderation
            </h2>
            <p style="font-size: 0.88rem; color: var(--color-charcoal-muted); margin: 0;">
              Confirm, complete, or cancel demo appointments in real-time. Changes are immediately reflected in the Customer Profile.
            </p>
          </div>

          <!-- Status Filters -->
          <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
            <a href="admin-dashboard.php?status=all" class="btn btn-sm <?php echo $statusFilter === 'all' ? 'btn-primary' : 'btn-outline'; ?>">All</a>
            <a href="admin-dashboard.php?status=pending" class="btn btn-sm <?php echo $statusFilter === 'pending' ? 'btn-primary' : 'btn-outline'; ?>">Pending</a>
            <a href="admin-dashboard.php?status=confirmed" class="btn btn-sm <?php echo $statusFilter === 'confirmed' ? 'btn-primary' : 'btn-outline'; ?>">Confirmed</a>
            <a href="admin-dashboard.php?status=completed" class="btn btn-sm <?php echo $statusFilter === 'completed' ? 'btn-primary' : 'btn-outline'; ?>">Completed</a>
            <a href="admin-dashboard.php?status=cancelled" class="btn btn-sm <?php echo $statusFilter === 'cancelled' ? 'btn-primary' : 'btn-outline'; ?>">Cancelled</a>
          </div>
        </div>

        <!-- Appointments Table -->
        <div style="overflow-x: auto;">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Booking Ref</th>
                <th>Client Name &amp; Contact</th>
                <th>Ritual &amp; Duration</th>
                <th>Specialist</th>
                <th>Schedule</th>
                <th>Status</th>
                <th>Fee</th>
                <th style="text-align: right;">Concierge Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($appointments)): ?>
                <tr>
                  <td colspan="8" style="text-align: center; padding: 30px; color: var(--color-charcoal-muted);">
                    No appointments matching the selected filter in demo session storage.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($appointments as $apt): ?>
                  <?php
                    $statusClass = 'status-' . strtolower($apt['status']);
                    $dateFormatted = date('M j, Y', strtotime($apt['appointment_date']));
                    $timeFormatted = date('g:i A', strtotime($apt['appointment_time']));
                  ?>
                  <tr>
                    <td>
                      <code style="font-weight: 700; color: var(--color-charcoal);"><?php echo htmlspecialchars($apt['booking_reference'], ENT_QUOTES, 'UTF-8'); ?></code>
                      <?php if (!empty($apt['silent_appointment'])): ?>
                        <div style="font-size: 0.7rem; color: var(--color-champagne-dark); font-weight: 600;">✦ Silent Service</div>
                      <?php endif; ?>
                    </td>

                    <td>
                      <strong><?php echo htmlspecialchars($apt['customer_name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                      <div style="font-size: 0.78rem; color: var(--color-charcoal-muted);">
                        <?php echo htmlspecialchars($apt['customer_email'], ENT_QUOTES, 'UTF-8'); ?> &bull; <?php echo htmlspecialchars($apt['customer_phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                      </div>
                    </td>

                    <td>
                      <div><strong><?php echo htmlspecialchars($apt['service_name'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                      <div style="font-size: 0.78rem; color: var(--color-charcoal-muted);"><?php echo (int)$apt['duration_minutes']; ?> mins</div>
                    </td>

                    <td>
                      <div><?php echo htmlspecialchars($apt['staff_name'] ?? 'Master Stylist', ENT_QUOTES, 'UTF-8'); ?></div>
                    </td>

                    <td>
                      <div><strong><?php echo $dateFormatted; ?></strong></div>
                      <div style="font-size: 0.78rem; color: var(--color-charcoal-muted);"><?php echo $timeFormatted; ?></div>
                    </td>

                    <td>
                      <span class="status-badge <?php echo $statusClass; ?>">
                        <?php echo htmlspecialchars($apt['status'], ENT_QUOTES, 'UTF-8'); ?>
                      </span>
                    </td>

                    <td>
                      <strong style="color: var(--color-champagne-dark); font-size: 0.95rem;">
                        $<?php echo number_format((float)$apt['total_price'], 2); ?>
                      </strong>
                    </td>

                    <td style="text-align: right;">
                      <div style="display: inline-flex; gap: 4px; justify-content: flex-end;">
                        <?php if ($apt['status'] !== 'confirmed'): ?>
                          <form action="admin-dashboard.php<?php echo $statusFilter !== 'all' ? '?status=' . urlencode($statusFilter) : ''; ?>" method="POST" style="margin:0;">
                            <?php echo DemoCsrf::field(); ?>
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="booking_reference" value="<?php echo htmlspecialchars($apt['booking_reference'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="new_status" value="confirmed">
                            <button type="submit" class="btn-action" style="background:var(--color-champagne); color:var(--color-charcoal);" title="Confirm Reservation">
                              ✓ Confirm
                            </button>
                          </form>
                        <?php endif; ?>

                        <?php if ($apt['status'] !== 'completed'): ?>
                          <form action="admin-dashboard.php<?php echo $statusFilter !== 'all' ? '?status=' . urlencode($statusFilter) : ''; ?>" method="POST" style="margin:0;">
                            <?php echo DemoCsrf::field(); ?>
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="booking_reference" value="<?php echo htmlspecialchars($apt['booking_reference'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="new_status" value="completed">
                            <button type="submit" class="btn-action" style="background:var(--color-charcoal); color:var(--color-ivory);" title="Mark as Completed">
                              ★ Complete
                            </button>
                          </form>
                        <?php endif; ?>

                        <?php if ($apt['status'] !== 'cancelled'): ?>
                          <form action="admin-dashboard.php<?php echo $statusFilter !== 'all' ? '?status=' . urlencode($statusFilter) : ''; ?>" method="POST" onsubmit="return confirm('Cancel appointment [<?php echo $apt['booking_reference']; ?>]?');" style="margin:0;">
                            <?php echo DemoCsrf::field(); ?>
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="booking_reference" value="<?php echo htmlspecialchars($apt['booking_reference'], ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="new_status" value="cancelled">
                            <button type="submit" class="btn-action" style="background:rgba(201,83,83,0.1); color:#9E2A2B; border-color:rgba(201,83,83,0.3);" title="Cancel Appointment">
                              ✕ Cancel
                            </button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>

      </div>

      <!-- 2-COLUMN SECTION: POPULAR SERVICES & DEMO CLIENTELE -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--space-2xl);">
        
        <!-- POPULAR SERVICES -->
        <div style="background: var(--color-white); border: 1px solid var(--color-charcoal-border); border-radius: var(--radius-xs); padding: var(--space-xl); box-shadow: var(--shadow-subtle);">
          <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--color-charcoal); margin: 0 0 12px;">
            Popular Salon Rituals
          </h3>
          <div style="display: flex; flex-direction: column; gap: 12px;">
            <?php foreach ($stats['popular_services'] as $pop): ?>
              <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px dashed var(--color-charcoal-border);">
                <div>
                  <strong style="font-size: 0.9rem;"><?php echo htmlspecialchars($pop['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                  <div style="font-size: 0.78rem; color: var(--color-charcoal-muted);"><?php echo $pop['count']; ?> reservations booked</div>
                </div>
                <strong style="color: var(--color-champagne-dark); font-size: 0.95rem;"><?php echo $pop['revenue']; ?></strong>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- DEMO CLIENTELE -->
        <div style="background: var(--color-white); border: 1px solid var(--color-charcoal-border); border-radius: var(--radius-xs); padding: var(--space-xl); box-shadow: var(--shadow-subtle);">
          <h3 style="font-family: var(--font-serif); font-size: 1.4rem; color: var(--color-charcoal); margin: 0 0 12px;">
            VIP Demonstration Clientele
          </h3>
          <div style="display: flex; flex-direction: column; gap: 12px;">
            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px dashed var(--color-charcoal-border);">
              <div>
                <strong>Adrienne Laurent</strong> <span class="luxury-pill" style="font-size:0.65rem;">VIP</span>
                <div style="font-size: 0.78rem; color: var(--color-charcoal-muted);"><?php echo DEMO_CUSTOMER_EMAIL; ?> &bull; +1 (555) 234-5678</div>
              </div>
              <span style="font-size:0.78rem; color:var(--color-champagne-dark); font-weight:700;">Demo Account</span>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px dashed var(--color-charcoal-border);">
              <div>
                <strong>Marcus Vance</strong>
                <div style="font-size: 0.78rem; color: var(--color-charcoal-muted);">marcus.vance@example.com &bull; +1 (555) 345-6789</div>
              </div>
              <span style="font-size:0.78rem; color:var(--color-charcoal-muted);">3 Visits</span>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; padding-bottom: 8px; border-bottom: 1px dashed var(--color-charcoal-border);">
              <div>
                <strong>Sophia Dubois</strong>
                <div style="font-size: 0.78rem; color: var(--color-charcoal-muted);">sophia.d@example.com &bull; +1 (555) 456-7890</div>
              </div>
              <span style="font-size:0.78rem; color:var(--color-charcoal-muted);">5 Visits</span>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
              <div>
                <strong>Liam Kensington</strong>
                <div style="font-size: 0.78rem; color: var(--color-charcoal-muted);">liam.k@example.com &bull; +1 (555) 567-8901</div>
              </div>
              <span style="font-size:0.78rem; color:var(--color-charcoal-muted);">2 Visits</span>
            </div>
          </div>
        </div>

      </div>

    </div>
  </main>

  <!-- FOOTER -->
  <footer style="background-color: var(--color-charcoal); color: var(--color-ivory); padding: var(--space-lg) 0; font-size: 0.85rem; text-align: center; border-top: 1px solid var(--color-champagne-border);">
    <div class="container">
      <p style="margin: 0; color: rgba(245,239,230,0.7);">
        &copy; <?php echo date('Y'); ?> L'Étoile Luxury Unisex Hair Salon &amp; Spa &bull; Demo Mode Executive Console
      </p>
    </div>
  </footer>

</body>
</html>
