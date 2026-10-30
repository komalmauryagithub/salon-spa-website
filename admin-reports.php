<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Executive Business Analytics, Appointment Volume & Financial Reports
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 1. Guard check: Administrator & Manager role required
Auth::requireRole(['admin', 'manager'], 'admin-login.php');

$currentUser = Auth::user();
$activeAdminPage = 'reports';

try {
    $pdo = Database::getConnection();

    // 2. Date Range Parsing
    $range = trim((string)($_GET['range'] ?? 'all_time'));
    $fromDate = trim((string)($_GET['from_date'] ?? ''));
    $toDate   = trim((string)($_GET['to_date'] ?? ''));

    $todayStr = date('Y-m-d');

    switch ($range) {
        case 'today':
            $fromDate = $todayStr;
            $toDate   = $todayStr;
            break;
        case 'last_7_days':
            $fromDate = date('Y-m-d', strtotime('-6 days'));
            $toDate   = $todayStr;
            break;
        case 'this_month':
            $fromDate = date('Y-m-01');
            $toDate   = date('Y-m-t');
            break;
        case 'last_month':
            $fromDate = date('Y-m-01', strtotime('first day of last month'));
            $toDate   = date('Y-m-t', strtotime('last day of last month'));
            break;
        case 'this_year':
            $fromDate = date('Y-01-01');
            $toDate   = date('Y-12-31');
            break;
        case 'custom':
            if (empty($fromDate)) $fromDate = '2026-01-01';
            if (empty($toDate))   $toDate   = date('Y-12-31');
            break;
        case 'all_time':
        default:
            $range    = 'all_time';
            $fromDate = '2020-01-01';
            $toDate   = date('Y-12-31', strtotime('+1 year'));
            break;
    }

    $where = ["appointment_date >= :from_dt AND appointment_date <= :to_dt"];
    $params = [
        'from_dt' => $fromDate,
        'to_dt'   => $toDate
    ];

    $whereSql = implode(' AND ', $where);

    // 3. Export to CSV Handler
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        $exportStmt = $pdo->prepare("
            SELECT 
                a.booking_reference,
                a.appointment_date,
                a.appointment_time,
                CONCAT(c.first_name, ' ', c.last_name) AS guest_name,
                c.email AS guest_email,
                c.phone AS guest_phone,
                COALESCE(s.name, o.title, 'Custom Ritual') AS ritual_name,
                COALESCE(stf.full_name, 'Unassigned / Any Specialist') AS specialist_name,
                a.duration_minutes,
                a.total_price,
                a.status,
                a.cancellation_reason,
                a.created_at
            FROM appointments a
            JOIN customers c ON a.customer_id = c.id
            LEFT JOIN services s ON a.service_id = s.id
            LEFT JOIN offers o ON a.offer_id = o.id
            LEFT JOIN staff stf ON a.staff_id = stf.id
            WHERE {$whereSql}
            ORDER BY a.appointment_date DESC, a.appointment_time DESC
        ");
        $exportStmt->execute($params);
        $exportRows = $exportStmt->fetchAll();

        $filename = "letoile_salon_report_" . date('Ymd_His') . ".csv";
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        // Add UTF-8 BOM for Excel
        fputs($out, "\xEF\xBB\xBF");
        fputcsv($out, [
            'Booking Ref', 'Date', 'Time', 'Guest Name', 'Guest Email', 'Guest Phone',
            'Service / Ritual', 'Specialist', 'Duration (Mins)', 'Price ($)',
            'Status', 'Cancellation Reason', 'Booked At'
        ]);

        foreach ($exportRows as $r) {
            fputcsv($out, [
                $r['booking_reference'],
                $r['appointment_date'],
                $r['appointment_time'],
                $r['guest_name'],
                $r['guest_email'],
                $r['guest_phone'],
                $r['ritual_name'],
                $r['specialist_name'],
                $r['duration_minutes'],
                number_format((float)$r['total_price'], 2, '.', ''),
                $r['status'],
                $r['cancellation_reason'] ?: '',
                $r['created_at']
            ]);
        }
        fclose($out);
        exit;
    }

    // 4. Financial & Volume KPI Metrics
    $kpiStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count,
            SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) AS no_show_count,
            
            -- Strict Realized Revenue: completed + confirmed only
            SUM(CASE WHEN status = 'completed' THEN total_price ELSE 0 END) AS completed_revenue,
            SUM(CASE WHEN status = 'confirmed' THEN total_price ELSE 0 END) AS confirmed_revenue,
            SUM(CASE WHEN status IN ('completed', 'confirmed') THEN total_price ELSE 0 END) AS total_realized_revenue,
            
            -- Cancelled value for lost revenue analysis
            SUM(CASE WHEN status IN ('cancelled', 'no_show') THEN total_price ELSE 0 END) AS lost_revenue
        FROM appointments
        WHERE {$whereSql}
    ");
    $kpiStmt->execute($params);
    $kpis = $kpiStmt->fetch();

    $totApts      = (int)($kpis['total_appointments'] ?? 0);
    $completedApt = (int)($kpis['completed_count'] ?? 0);
    $confirmedApt = (int)($kpis['confirmed_count'] ?? 0);
    $pendingApt   = (int)($kpis['pending_count'] ?? 0);
    $cancelledApt = (int)($kpis['cancelled_count'] ?? 0);
    $noShowApt    = (int)($kpis['no_show_count'] ?? 0);

    $completedRev = (float)($kpis['completed_revenue'] ?? 0.00);
    $confirmedRev = (float)($kpis['confirmed_revenue'] ?? 0.00);
    $realizedRev  = (float)($kpis['total_realized_revenue'] ?? 0.00);
    $lostRev      = (float)($kpis['lost_revenue'] ?? 0.00);

    $completedOrConfirmedCount = $completedApt + $confirmedApt;
    $avgTicket = $completedOrConfirmedCount > 0 ? ($realizedRev / $completedOrConfirmedCount) : 0.00;
    $completionRate = $totApts > 0 ? round(($completedApt / $totApts) * 100, 1) : 0;
    $cancellationRate = $totApts > 0 ? round((($cancelledApt + $noShowApt) / $totApts) * 100, 1) : 0;

    // 5. Popular Services Ranking (Based on actual bookings)
    $popStmt = $pdo->prepare("
        SELECT 
            COALESCE(s.name, o.title, 'Bespoke Atelier Ritual') AS service_name,
            COALESCE(sc.name, 'Seasonal Package') AS category_name,
            COUNT(a.id) AS booking_count,
            SUM(CASE WHEN a.status IN ('completed', 'confirmed') THEN a.total_price ELSE 0 END) AS generated_revenue,
            ROUND(AVG(a.duration_minutes), 0) AS avg_duration
        FROM appointments a
        LEFT JOIN services s ON a.service_id = s.id
        LEFT JOIN service_categories sc ON s.category_id = sc.id
        LEFT JOIN offers o ON a.offer_id = o.id
        WHERE {$whereSql}
        GROUP BY a.service_id, a.offer_id
        ORDER BY booking_count DESC, generated_revenue DESC
        LIMIT 10
    ");
    $popStmt->execute($params);
    $popularServices = $popStmt->fetchAll();

    // 6. Specialist Productivity
    $specStmt = $pdo->prepare("
        SELECT 
            COALESCE(stf.full_name, 'Any Available Specialist') AS specialist_name,
            stf.role_title,
            COUNT(a.id) AS total_served,
            SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
            SUM(CASE WHEN a.status IN ('completed', 'confirmed') THEN a.total_price ELSE 0 END) AS specialist_revenue
        FROM appointments a
        LEFT JOIN staff stf ON a.staff_id = stf.id
        WHERE {$whereSql}
        GROUP BY a.staff_id
        ORDER BY specialist_revenue DESC, total_served DESC
    ");
    $specStmt->execute($params);
    $specialistsReport = $specStmt->fetchAll();

    // 7. Day-by-Day Historical Timeline
    $timelineStmt = $pdo->prepare("
        SELECT 
            appointment_date,
            COUNT(*) AS daily_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS daily_completed,
            SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS daily_confirmed,
            SUM(CASE WHEN status IN ('cancelled', 'no_show') THEN 1 ELSE 0 END) AS daily_cancelled,
            SUM(CASE WHEN status IN ('completed', 'confirmed') THEN total_price ELSE 0 END) AS daily_realized_revenue
        FROM appointments
        WHERE {$whereSql}
        GROUP BY appointment_date
        ORDER BY appointment_date DESC
        LIMIT 30
    ");
    $timelineStmt->execute($params);
    $dailyTimeline = $timelineStmt->fetchAll();

} catch (Exception $e) {
    AppLogger::error("Reports error: " . $e->getMessage());
    $totApts = 0; $completedApt = 0; $confirmedApt = 0; $pendingApt = 0; $cancelledApt = 0; $noShowApt = 0;
    $completedRev = 0; $confirmedRev = 0; $realizedRev = 0; $lostRev = 0; $avgTicket = 0;
    $completionRate = 0; $cancellationRate = 0;
    $popularServices = []; $specialistsReport = []; $dailyTimeline = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports &amp; Financial Analytics — L'ÉTOILE Luxury Hair Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
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
      padding: 12px 16px;
      border-bottom: 1px solid var(--color-charcoal-border);
      vertical-align: middle;
    }
    .admin-table tr:hover {
      background-color: rgba(245, 239, 230, 0.4);
    }
    .report-panel {
      background: var(--color-white);
      border: 1px solid var(--color-charcoal-border);
      border-radius: var(--radius-xs);
      padding: var(--space-xl);
      box-shadow: var(--shadow-subtle);
      margin-bottom: var(--space-2xl);
    }
    .panel-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: var(--space-lg);
      border-bottom: 1px solid var(--color-charcoal-border);
      padding-bottom: var(--space-sm);
    }
    .panel-title {
      font-family: var(--font-serif);
      font-size: 1.45rem;
      color: var(--color-charcoal);
      margin: 0;
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
          <span class="eyebrow eyebrow-no-line">Atelier Financial Intelligence</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.2rem; color: var(--color-charcoal); margin: 4px 0 6px;">
            Analytics &amp; Performance Reports
          </h1>
          <p style="color: var(--color-charcoal-muted); margin: 0; font-size: 0.95rem;">
            Realized revenue from completed/confirmed rituals, popular treatments, and stylist productivity.
          </p>
        </div>
        <div style="display:flex; gap:8px;">
          <a href="admin-reports.php?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-outline btn-sm" title="Download spreadsheet of selected report">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle; margin-right:4px;"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
            Export CSV
          </a>
        </div>
      </div>

      <!-- DATE RANGE SELECTOR BAR -->
      <div style="margin-top:var(--space-xl); background:var(--color-ivory); padding:var(--space-md) var(--space-lg); border-radius:var(--radius-xs); border:1px solid var(--color-champagne-border); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div style="display:flex; gap:6px; flex-wrap:wrap;">
          <a href="admin-reports.php?range=all_time" class="btn btn-sm <?php echo $range === 'all_time' ? 'btn-champagne' : 'btn-outline'; ?>">All Time</a>
          <a href="admin-reports.php?range=today" class="btn btn-sm <?php echo $range === 'today' ? 'btn-champagne' : 'btn-outline'; ?>">Today</a>
          <a href="admin-reports.php?range=last_7_days" class="btn btn-sm <?php echo $range === 'last_7_days' ? 'btn-champagne' : 'btn-outline'; ?>">Last 7 Days</a>
          <a href="admin-reports.php?range=this_month" class="btn btn-sm <?php echo $range === 'this_month' ? 'btn-champagne' : 'btn-outline'; ?>">This Month</a>
          <a href="admin-reports.php?range=last_month" class="btn btn-sm <?php echo $range === 'last_month' ? 'btn-champagne' : 'btn-outline'; ?>">Last Month</a>
          <a href="admin-reports.php?range=this_year" class="btn btn-sm <?php echo $range === 'this_year' ? 'btn-champagne' : 'btn-outline'; ?>">This Year</a>
        </div>

        <form action="admin-reports.php" method="GET" style="display:flex; gap:8px; align-items:center;">
          <input type="hidden" name="range" value="custom">
          <input type="date" name="from_date" value="<?php echo e($fromDate); ?>" class="form-control" style="font-size:0.82rem; padding:4px 8px;">
          <span style="font-size:0.8rem; color:var(--color-charcoal-muted);">&rarr;</span>
          <input type="date" name="to_date" value="<?php echo e($toDate); ?>" class="form-control" style="font-size:0.82rem; padding:4px 8px;">
          <button type="submit" class="btn btn-outline btn-sm">Apply</button>
        </form>
      </div>

      <!-- KPI FINANCIAL METRICS -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:var(--space-md); margin-top:var(--space-lg);">
        
        <div class="kpi-card">
          <div class="kpi-label">Total Realized Revenue</div>
          <div class="kpi-val" style="color:#1E5128;">
            $<?php echo number_format($realizedRev, 2); ?>
          </div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">
            Completed: <strong>$<?php echo number_format($completedRev, 2); ?></strong> &bull; Confirmed: <strong>$<?php echo number_format($confirmedRev, 2); ?></strong>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Appointment Volume</div>
          <div class="kpi-val"><?php echo $totApts; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">
            Completed: <strong><?php echo $completedApt; ?></strong> &bull; Confirmed: <strong><?php echo $confirmedApt; ?></strong>
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Average Ticket Size</div>
          <div class="kpi-val" style="color:var(--color-champagne-dark);">
            $<?php echo number_format($avgTicket, 2); ?>
          </div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">
            Average spend per realized treatment
          </div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Completion vs Cancellation</div>
          <div class="kpi-val" style="font-size:1.8rem;">
            <?php echo $completionRate; ?>% <span style="font-size:1rem; color:var(--color-charcoal-muted); font-weight:400;">/ <?php echo $cancellationRate; ?>%</span>
          </div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">
            Completed rate vs cancelled / no-show
          </div>
        </div>

      </div>

    </div>
  </section>

  <!-- MAIN REPORT DETAIL CONTENT -->
  <main style="padding: var(--space-3xl) 0 var(--space-4xl);">
    <div class="container">

      <!-- 2-COLUMN LAYOUT: Popular Services & Stylist Productivity -->
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:var(--space-2xl); align-items:start; margin-bottom:var(--space-2xl);" class="dashboard-layout-grid">
        
        <!-- POPULAR SERVICES RANKING -->
        <div class="report-panel">
          <div class="panel-header">
            <div>
              <span class="eyebrow eyebrow-no-line">Booking Demand</span>
              <h3 class="panel-title">Most Requested Rituals</h3>
            </div>
            <span style="font-size:0.8rem; color:var(--color-charcoal-muted);">By actual bookings</span>
          </div>

          <?php if (empty($popularServices)): ?>
            <p style="color:var(--color-charcoal-muted); font-size:0.88rem; text-align:center; padding:20px 0;">No services booked in this date range.</p>
          <?php else: ?>
            <div style="overflow-x:auto;">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Ritual Name</th>
                    <th>Bookings</th>
                    <th>Avg Duration</th>
                    <th>Realized Revenue</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($popularServices as $ps): ?>
                    <tr>
                      <td>
                        <strong style="color:var(--color-charcoal);"><?php echo e($ps['service_name']); ?></strong>
                        <div style="font-size:0.75rem; color:var(--color-charcoal-muted);"><?php echo e($ps['category_name']); ?></div>
                      </td>
                      <td><strong><?php echo (int)$ps['booking_count']; ?></strong></td>
                      <td><?php echo (int)$ps['avg_duration']; ?>m</td>
                      <td>
                        <strong style="color:#1E5128;">$<?php echo number_format((float)$ps['generated_revenue'], 2); ?></strong>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

        <!-- SPECIALIST PRODUCTIVITY -->
        <div class="report-panel">
          <div class="panel-header">
            <div>
              <span class="eyebrow eyebrow-no-line">Atelier Team</span>
              <h3 class="panel-title">Specialist Productivity</h3>
            </div>
            <span style="font-size:0.8rem; color:var(--color-charcoal-muted);">Revenue by artisan</span>
          </div>

          <?php if (empty($specialistsReport)): ?>
            <p style="color:var(--color-charcoal-muted); font-size:0.88rem; text-align:center; padding:20px 0;">No specialist activity in this date range.</p>
          <?php else: ?>
            <div style="overflow-x:auto;">
              <table class="admin-table">
                <thead>
                  <tr>
                    <th>Specialist</th>
                    <th>Treatments</th>
                    <th>Completed</th>
                    <th>Revenue</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($specialistsReport as $sr): ?>
                    <tr>
                      <td>
                        <strong style="color:var(--color-charcoal);"><?php echo e($sr['specialist_name']); ?></strong>
                        <div style="font-size:0.75rem; color:var(--color-charcoal-muted);"><?php echo e($sr['role_title'] ?? 'Artisan'); ?></div>
                      </td>
                      <td><strong><?php echo (int)$sr['total_served']; ?></strong></td>
                      <td><?php echo (int)$sr['completed_count']; ?></td>
                      <td>
                        <strong style="color:var(--color-champagne-dark);">$<?php echo number_format((float)$sr['specialist_revenue'], 2); ?></strong>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>

      </div>

      <!-- DAILY ACTIVITY & FINANCIAL TIMELINE -->
      <div class="report-panel">
        <div class="panel-header">
          <div>
            <span class="eyebrow eyebrow-no-line">Historical Ledger</span>
            <h3 class="panel-title">Daily Appointment &amp; Revenue Breakdown</h3>
          </div>
          <span style="font-size:0.8rem; color:var(--color-charcoal-muted);">Recent 30 active appointment days</span>
        </div>

        <?php if (empty($dailyTimeline)): ?>
          <p style="color:var(--color-charcoal-muted); font-size:0.88rem; text-align:center; padding:20px 0;">No daily activity records for this date window.</p>
        <?php else: ?>
          <div style="overflow-x:auto;">
            <table class="admin-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Day</th>
                  <th>Total Appointments</th>
                  <th>Completed</th>
                  <th>Confirmed</th>
                  <th>Cancelled</th>
                  <th>Realized Daily Revenue</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($dailyTimeline as $dt): ?>
                  <tr>
                    <td>
                      <strong style="color:var(--color-charcoal);"><?php echo date('M j, Y', strtotime($dt['appointment_date'])); ?></strong>
                    </td>
                    <td>
                      <span style="color:var(--color-charcoal-muted); font-size:0.82rem;"><?php echo date('l', strtotime($dt['appointment_date'])); ?></span>
                    </td>
                    <td><strong><?php echo (int)$dt['daily_appointments']; ?></strong></td>
                    <td><span style="color:#1E5128; font-weight:600;"><?php echo (int)$dt['daily_completed']; ?></span></td>
                    <td><span style="color:var(--color-champagne-dark); font-weight:600;"><?php echo (int)$dt['daily_confirmed']; ?></span></td>
                    <td><span style="color:#9E2A2B;"><?php echo (int)$dt['daily_cancelled']; ?></span></td>
                    <td>
                      <strong style="color:#1E5128; font-size:0.95rem;">
                        $<?php echo number_format((float)$dt['daily_realized_revenue'], 2); ?>
                      </strong>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </main>

  <!-- FOOTER -->
  <footer class="site-footer" role="contentinfo">
    <div class="container footer-bottom-inner">
      <p>&copy; <?php echo date('Y'); ?> L'ÉTOILE Luxury Unisex Hair Salon &amp; Spa &bull; Concierge Operations Panel.</p>
    </div>
  </footer>

</body>
</html>
