<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Database & Environment Diagnostic Console
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

$envPath = __DIR__ . '/.env';
$hasEnvFile = file_exists($envPath);

$dbConfig = Database::getConfig()['database'] ?? [];
$dbHost = $dbConfig['host'] ?? '127.0.0.1';
$dbPort = $dbConfig['port'] ?? 3306;
$dbName = $dbConfig['database'] ?? 'letoile_salon_db';
$dbUser = $dbConfig['username'] ?? 'root';

$isConnected = false;
$connectionError = null;
$tableStats = [];
$usersList = [];
$serverVersion = '';

try {
    $pdo = Database::getConnection();
    $isConnected = true;
    $serverVersion = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);

    $tables = [
        'users' => 'System & Staff Accounts',
        'customers' => 'Registered Sanctuary Patrons',
        'services' => 'Salon & Spa Services',
        'service_categories' => 'Service Categories',
        'staff' => 'Master Stylists & Specialists',
        'appointments' => 'Guest Appointments',
        'offers' => 'Curated Seasonal Packages',
        'reviews' => 'Guest Reflections & Reviews',
        'gallery_images' => 'Portfolio Gallery Images',
        'contact_enquiries' => 'Guest Concierge Enquiries',
        'salon_settings' => 'Dynamic Business Settings'
    ];

    foreach ($tables as $tbl => $label) {
        try {
            $cnt = $pdo->query("SELECT COUNT(*) FROM `{$tbl}`")->fetchColumn();
            $tableStats[$tbl] = ['label' => $label, 'count' => (int)$cnt];
        } catch (Exception $e) {
            $tableStats[$tbl] = ['label' => $label, 'count' => 'Table Missing'];
        }
    }

    $usersStmt = $pdo->query("SELECT id, username, email, role, is_active FROM users ORDER BY id ASC");
    $usersList = $usersStmt->fetchAll();
} catch (Exception $e) {
    $isConnected = false;
    $connectionError = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Database Status — L'Étoile Luxury Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <style>
    :root {
      --color-ivory: #F5EFE6;
      --color-champagne: #C5A46D;
      --color-champagne-dark: #9B7E4B;
      --color-charcoal: #252321;
      --color-white: #FFFFFF;
      --color-success: #2D6A4F;
      --color-danger: #9E2A2B;
    }
    body {
      background-color: var(--color-ivory);
      font-family: 'Plus Jakarta Sans', sans-serif;
      color: var(--color-charcoal);
      margin: 0;
      padding: 30px 20px;
    }
    .diag-container {
      max-width: 860px;
      margin: 0 auto;
    }
    .status-card {
      background: #FFFFFF;
      border-radius: 8px;
      padding: 28px;
      box-shadow: 0 10px 30px rgba(37,35,33,0.06);
      margin-bottom: 24px;
      border: 1px solid rgba(197, 164, 109, 0.25);
    }
    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 20px;
      font-weight: 700;
      font-size: 0.82rem;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }
    .badge-success { background: #E8F5E9; color: #2D6A4F; }
    .badge-danger { background: #FFEBEE; color: #9E2A2B; }
    .pulse-dot {
      width: 9px;
      height: 9px;
      border-radius: 50%;
      background: currentColor;
    }
    .grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 16px;
    }
    @media (max-width: 640px) {
      .grid-2 { grid-template-columns: 1fr; }
    }
    .stat-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 14px;
      background: var(--color-ivory);
      border-radius: 6px;
      margin-bottom: 8px;
      font-size: 0.88rem;
    }
    .stat-count {
      font-weight: 700;
      color: var(--color-champagne-dark);
      background: #FFFFFF;
      padding: 3px 10px;
      border-radius: 12px;
      border: 1px solid rgba(197, 164, 109, 0.3);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.85rem;
      margin-top: 10px;
    }
    th, td {
      padding: 10px 12px;
      text-align: left;
      border-bottom: 1px solid #ECECEC;
    }
    th {
      background: var(--color-ivory);
      font-weight: 600;
      color: var(--color-charcoal);
    }
    .code-pill {
      background: #F0ECE4;
      padding: 2px 7px;
      border-radius: 4px;
      font-family: monospace;
      font-size: 0.82rem;
      color: var(--color-charcoal);
    }
  </style>
</head>
<body>

  <div class="diag-container">
    
    <!-- Header -->
    <div style="text-align: center; margin-bottom: 28px;">
      <span style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.15em; color: var(--color-champagne); font-weight: 600;">L'Étoile Concierge Diagnostics</span>
      <h1 style="font-family: 'Cormorant Garamond', serif; font-size: 2.2rem; margin: 6px 0; color: var(--color-charcoal);">
        Database &amp; Environment Status
      </h1>
      <p style="color: #666; font-size: 0.92rem; margin: 0;">Real-time connectivity verification between Core PHP and local MySQL database.</p>
    </div>

    <!-- Main Status Card -->
    <div class="status-card">
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 12px;">
        <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; margin: 0;">MySQL Connection</h2>
        <?php if ($isConnected): ?>
          <span class="badge badge-success"><span class="pulse-dot"></span> CONNECTED &amp; OPERATIONAL</span>
        <?php else: ?>
          <span class="badge badge-danger"><span class="pulse-dot"></span> CONNECTION FAILED</span>
        <?php endif; ?>
      </div>

      <?php if ($isConnected): ?>
        <div style="background: #E8F5E9; border-left: 4px solid #2D6A4F; padding: 14px 18px; border-radius: 4px; margin-bottom: 20px;">
          <div style="font-weight: 600; color: #2D6A4F; font-size: 0.95rem;">Successfully connected to MySQL!</div>
          <div style="font-size: 0.84rem; color: #1B4332; margin-top: 4px;">
            Connected to <strong><?php echo htmlspecialchars($dbName); ?></strong> on <strong><?php echo htmlspecialchars($dbHost); ?>:<?php echo htmlspecialchars($dbPort); ?></strong> (Server: MySQL <?php echo htmlspecialchars($serverVersion); ?>).
          </div>
        </div>
      <?php else: ?>
        <div style="background: #FFEBEE; border-left: 4px solid #9E2A2B; padding: 14px 18px; border-radius: 4px; margin-bottom: 20px;">
          <div style="font-weight: 600; color: #9E2A2B; font-size: 0.95rem;">Database Connection Failure:</div>
          <div style="font-size: 0.84rem; color: #661111; margin-top: 4px; font-family: monospace;">
            <?php echo htmlspecialchars($connectionError); ?>
          </div>
          <div style="font-size: 0.82rem; color: #444; margin-top: 8px;">
            Please ensure XAMPP MySQL is started in your XAMPP Control Panel and the database <code>letoile_salon_db</code> exists.
          </div>
        </div>
      <?php endif; ?>

      <!-- Environment & Config Information -->
      <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.2rem; margin: 20px 0 10px;">Configuration &amp; Environment File</h3>
      <div class="grid-2">
        <div class="stat-row">
          <span>Environment File (<code>.env</code>):</span>
          <span class="stat-count" style="<?php echo $hasEnvFile ? 'color:#2D6A4F;' : 'color:#9E2A2B;'; ?>">
            <?php echo $hasEnvFile ? '✓ Loaded (.env)' : '✗ Not Found'; ?>
          </span>
        </div>
        <div class="stat-row">
          <span>Database Host &amp; Port:</span>
          <span class="stat-count"><?php echo htmlspecialchars($dbHost); ?>:<?php echo htmlspecialchars($dbPort); ?></span>
        </div>
        <div class="stat-row">
          <span>Database Name:</span>
          <span class="stat-count"><?php echo htmlspecialchars($dbName); ?></span>
        </div>
        <div class="stat-row">
          <span>Database User:</span>
          <span class="stat-count"><?php echo htmlspecialchars($dbUser); ?></span>
        </div>
      </div>
    </div>

    <?php if ($isConnected): ?>
    <!-- Database Tables Statistics -->
    <div class="status-card">
      <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 1.4rem; margin: 0 0 16px;">
        Database Tables &amp; Sample Data Record Counts
      </h2>
      <div class="grid-2">
        <?php foreach ($tableStats as $table => $info): ?>
          <div class="stat-row">
            <span><strong><?php echo htmlspecialchars($table); ?></strong> (<?php echo htmlspecialchars($info['label']); ?>)</span>
            <span class="stat-count"><?php echo htmlspecialchars($info['count']); ?> records</span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Active User Accounts -->
    <div class="status-card">
      <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 1.4rem; margin: 0 0 10px;">
        Active Demonstration User Accounts
      </h2>
      <p style="font-size: 0.85rem; color: #666; margin: 0 0 14px;">These credentials can be used directly on the <a href="login.php" style="color: var(--color-champagne-dark); font-weight: 600;">Sign In Portal</a>:</p>
      
      <div style="overflow-x: auto;">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Quick Password</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($usersList as $u): ?>
              <tr>
                <td><?php echo (int)$u['id']; ?></td>
                <td><span class="code-pill"><?php echo htmlspecialchars($u['username']); ?></span></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><strong style="text-transform: capitalize;"><?php echo htmlspecialchars($u['role']); ?></strong></td>
                <td>
                  <?php if ($u['username'] === 'admin'): ?>
                    <span class="code-pill">admin123</span> or <span class="code-pill">admin</span>
                  <?php elseif ($u['username'] === 'user'): ?>
                    <span class="code-pill">user123</span> or <span class="code-pill">user</span>
                  <?php else: ?>
                    <span class="code-pill">Letoile@2026</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Action Buttons -->
    <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; margin-top: 24px;">
      <a href="login.php" class="btn btn-primary">
        <span>Go to Sign In Portal</span> &rarr;
      </a>
      <a href="admin-dashboard.php" class="btn btn-outline">
        <span>Go to Admin Dashboard</span>
      </a>
      <a href="index.html" class="btn btn-outline">
        <span>Back to Website Home</span>
      </a>
    </div>

  </div>

</body>
</html>
