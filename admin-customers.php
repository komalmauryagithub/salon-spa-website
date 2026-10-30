<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Customer & Patron Directory Administration
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 1. Guard check: Administrator clearance required
Auth::requireRole(['admin', 'manager', 'receptionist'], 'admin-login.php');

$currentUser = Auth::user();
$activeAdminPage = 'customers';

$searchQuery = trim((string)($_GET['q'] ?? ''));
$filterVip   = trim((string)($_GET['vip'] ?? 'all'));
$filterFocus = trim((string)($_GET['focus'] ?? 'all'));

try {
    $pdo = Database::getConnection();

    // 1. Patron Aggregate KPIs
    $kpiStmt = $pdo->query("
        SELECT 
            COUNT(DISTINCT c.id) AS total_customers,
            SUM(CASE WHEN c.is_vip = 1 THEN 1 ELSE 0 END) AS vip_customers,
            COUNT(a.id) AS total_appointments,
            COALESCE(SUM(CASE WHEN a.status IN ('confirmed', 'completed') THEN a.total_price ELSE 0 END), 0) AS total_spend
        FROM customers c
        LEFT JOIN appointments a ON c.id = a.customer_id
    ");
    $kpiData = $kpiStmt->fetch();

    // 2. Build Filtered Query
    $where = ["1=1"];
    $params = [];

    if (!empty($searchQuery)) {
        $where[] = "(c.first_name LIKE :q OR c.last_name LIKE :q OR c.email LIKE :q OR c.phone LIKE :q)";
        $params['q'] = "%{$searchQuery}%";
    }

    if ($filterVip === 'vip') {
        $where[] = "c.is_vip = 1";
    } elseif ($filterVip === 'standard') {
        $where[] = "c.is_vip = 0";
    }

    if ($filterFocus !== 'all' && in_array($filterFocus, ['Unisex / Flexible', "Women's Styling Focus", "Men's Barbering Focus"], true)) {
        $where[] = "c.gender_preference = :focus";
        $params['focus'] = $filterFocus;
    }

    $whereSql = implode(' AND ', $where);

    $sql = "
        SELECT 
            c.id,
            c.first_name,
            c.last_name,
            c.email,
            c.phone,
            c.gender_preference,
            c.allergies_notes,
            c.is_vip,
            c.created_at,
            COUNT(a.id) AS booking_count,
            COALESCE(SUM(CASE WHEN a.status IN ('confirmed', 'completed') THEN a.total_price ELSE 0 END), 0) AS total_spend,
            MAX(a.appointment_date) AS latest_appointment
        FROM customers c
        LEFT JOIN appointments a ON c.id = a.customer_id
        WHERE {$whereSql}
        GROUP BY c.id
        ORDER BY c.created_at DESC
        LIMIT 100
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();

} catch (Exception $e) {
    AppLogger::error("Customer directory error: " . $e->getMessage());
    $customers = [];
    $kpiData = ['total_customers' => 0, 'vip_customers' => 0, 'total_appointments' => 0, 'total_spend' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Patron Directory — L'ÉTOILE Luxury Hair Salon &amp; Spa</title>
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
    .btn-action-sm {
      padding: 5px 12px;
      font-size: 0.75rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      border-radius: 2px;
      cursor: pointer;
      border: 1px solid var(--color-charcoal-border);
      background: #FFFFFF;
      transition: all var(--transition-fast);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 4px;
      color: var(--color-charcoal);
    }
    .btn-action-sm:hover {
      border-color: var(--color-champagne);
      color: var(--color-champagne-dark);
      background: var(--color-ivory);
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
          <span class="eyebrow eyebrow-no-line">Patron Management</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.2rem; color: var(--color-charcoal); margin: 4px 0 6px;">
            Guest &amp; Patron Directory
          </h1>
          <p style="color: var(--color-charcoal-muted); margin: 0; font-size: 0.95rem;">
            Registered atelier clientele, VIP memberships, treatment history, and preference dossiers.
          </p>
        </div>
        <div>
          <a href="book-appointment.html" target="_blank" class="btn btn-champagne btn-sm">+ New Client Walk-In</a>
        </div>
      </div>

      <!-- KPI METRICS -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:var(--space-md); margin-top:var(--space-xl);">
        
        <div class="kpi-card">
          <div class="kpi-label">Total Patrons</div>
          <div class="kpi-val"><?php echo (int)($kpiData['total_customers'] ?? 0); ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Enrolled guest profiles</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">VIP Patron Circle</div>
          <div class="kpi-val" style="color:var(--color-champagne-dark);">
            <?php echo (int)($kpiData['vip_customers'] ?? 0); ?>
          </div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Special concierge privileges</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Lifetime Bookings</div>
          <div class="kpi-val"><?php echo (int)($kpiData['total_appointments'] ?? 0); ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Recorded reservations</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Total Patron Spend</div>
          <div class="kpi-val" style="color:#1E5128;">
            $<?php echo number_format((float)($kpiData['total_spend'] ?? 0), 2); ?>
          </div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Confirmed &amp; Completed</div>
        </div>

      </div>

    </div>
  </section>

  <!-- MAIN CONTENT -->
  <main style="padding: var(--space-2xl) 0 var(--space-4xl);">
    <div class="container">

      <!-- FILTERS BAR -->
      <div style="background:var(--color-white); border:1px solid var(--color-charcoal-border); border-radius:var(--radius-xs); padding:var(--space-lg); margin-bottom:var(--space-xl); box-shadow:var(--shadow-subtle);">
        <form method="GET" action="admin-customers.php" style="display:flex; flex-wrap:wrap; gap:var(--space-md); align-items:flex-end;">
          
          <div style="flex:1; min-width:220px;">
            <label class="form-label" style="font-size:0.8rem;">Search Patron Name, Email, or Telephone</label>
            <input type="text" name="q" class="form-control" style="font-size:0.88rem; padding:8px 12px;" placeholder="e.g. Sophie, 555-0192..." value="<?php echo e($searchQuery); ?>">
          </div>

          <div style="min-width:160px;">
            <label class="form-label" style="font-size:0.8rem;">VIP Status</label>
            <select name="vip" class="form-control" style="font-size:0.88rem; padding:8px 12px;">
              <option value="all" <?php echo $filterVip === 'all' ? 'selected' : ''; ?>>All Patrons</option>
              <option value="vip" <?php echo $filterVip === 'vip' ? 'selected' : ''; ?>>VIP Patrons Only</option>
              <option value="standard" <?php echo $filterVip === 'standard' ? 'selected' : ''; ?>>Standard Clients</option>
            </select>
          </div>

          <div style="min-width:180px;">
            <label class="form-label" style="font-size:0.8rem;">Styling Focus</label>
            <select name="focus" class="form-control" style="font-size:0.88rem; padding:8px 12px;">
              <option value="all">All Preferences</option>
              <option value="Unisex / Flexible" <?php echo $filterFocus === 'Unisex / Flexible' ? 'selected' : ''; ?>>Unisex / Flexible</option>
              <option value="Women's Styling Focus" <?php echo $filterFocus === "Women's Styling Focus" ? 'selected' : ''; ?>>Women's Styling</option>
              <option value="Men's Barbering Focus" <?php echo $filterFocus === "Men's Barbering Focus" ? 'selected' : ''; ?>>Men's Barbering</option>
            </select>
          </div>

          <div style="display:flex; gap:8px;">
            <button type="submit" class="btn btn-primary btn-sm" style="padding:10px 18px;">
              <span>Search</span>
            </button>
            <?php if (!empty($searchQuery) || $filterVip !== 'all' || $filterFocus !== 'all'): ?>
              <a href="admin-customers.php" class="btn btn-outline btn-sm" style="padding:10px 14px;">Reset</a>
            <?php endif; ?>
          </div>

        </form>
      </div>

      <!-- CUSTOMERS TABLE -->
      <div style="background:var(--color-white); border:1px solid var(--color-charcoal-border); border-radius:var(--radius-xs); overflow-x:auto; box-shadow:var(--shadow-subtle);">
        
        <div style="padding:var(--space-md) var(--space-lg); border-bottom:1px solid var(--color-charcoal-border); display:flex; justify-content:space-between; align-items:center;">
          <h3 style="font-family:var(--font-serif); font-size:1.35rem; margin:0;">
            Patron Roster (<?php echo count($customers); ?>)
          </h3>
          <span style="font-size:0.82rem; color:var(--color-charcoal-muted);">
            Ranked by registration date
          </span>
        </div>

        <?php if (empty($customers)): ?>
          <div style="text-align:center; padding:var(--space-3xl) var(--space-md);">
            <p style="color:var(--color-charcoal-muted); font-size:0.95rem; margin-bottom:var(--space-md);">
              No patrons match your search criteria.
            </p>
            <a href="admin-customers.php" class="btn btn-outline btn-sm">Clear Filters</a>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="admin-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Patron Name</th>
                <th>Contact Details</th>
                <th>Styling Focus</th>
                <th>Bookings</th>
                <th>Total Spend</th>
                <th>Enrolled Date</th>
                <th>Dossier</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($customers as $c): ?>
                <tr>
                  <td>
                    <span style="font-family:monospace; color:var(--color-charcoal-muted);">
                      #<?php echo str_pad((string)$c['id'], 4, '0', STR_PAD_LEFT); ?>
                    </span>
                  </td>
                  <td>
                    <div style="font-weight:600; color:var(--color-charcoal);">
                      <?php echo e($c['first_name'] . ' ' . $c['last_name']); ?>
                      <?php if (!empty($c['is_vip'])): ?>
                        <span style="color:var(--color-champagne-dark); font-size:0.75rem; font-weight:700; margin-left:4px;" title="VIP Patron Circle">&#9733; VIP</span>
                      <?php endif; ?>
                    </div>
                    <?php if (!empty($c['allergies_notes'])): ?>
                      <div style="font-size:0.72rem; color:var(--color-charcoal-muted); font-style:italic; max-width:220px; text-overflow:ellipsis; overflow:hidden; white-space:nowrap;">
                        Note: <?php echo e($c['allergies_notes']); ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="font-size:0.82rem;">
                      <a href="tel:<?php echo e($c['phone']); ?>" style="color:inherit; font-weight:500;"><?php echo e($c['phone']); ?></a>
                    </div>
                    <div style="font-size:0.76rem; color:var(--color-charcoal-muted);">
                      <?php echo e($c['email']); ?>
                    </div>
                  </td>
                  <td>
                    <span style="font-size:0.78rem; padding:2px 8px; background:var(--color-ivory); border:1px solid var(--color-champagne-border); border-radius:2px;">
                      <?php echo e($c['gender_preference'] ?: 'Unisex / Flexible'); ?>
                    </span>
                  </td>
                  <td>
                    <strong><?php echo (int)$c['booking_count']; ?></strong>
                    <span style="font-size:0.75rem; color:var(--color-charcoal-muted);">reservations</span>
                  </td>
                  <td>
                    <strong style="color:var(--color-champagne-dark); font-size:0.95rem;">
                      $<?php echo number_format((float)$c['total_spend'], 2); ?>
                    </strong>
                  </td>
                  <td>
                    <span style="font-size:0.8rem; color:var(--color-charcoal-muted);">
                      <?php echo date('M j, Y', strtotime($c['created_at'])); ?>
                    </span>
                  </td>
                  <td>
                    <a href="admin-customer-detail.php?id=<?php echo (int)$c['id']; ?>" class="btn-action-sm">
                      <span>View Dossier</span>
                      <span>&rarr;</span>
                    </a>
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
      <p>&copy; <?php echo date('Y'); ?> L'Étoile Luxury Unisex Hair Salon &amp; Spa &bull; Patron Management System.</p>
    </div>
  </footer>

</body>
</html>
