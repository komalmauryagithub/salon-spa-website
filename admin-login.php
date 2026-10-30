<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Executive Concierge & Administrative Sign In Portal
 * 
 * Strict Brand Theme: Ivory #FAF7F2, Champagne #D4AF37, Charcoal #161413, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

// Redirect if already authenticated as staff
if (Auth::check()) {
    if (Auth::hasAnyRole(['admin', 'manager', 'receptionist'])) {
        Response::redirect('admin-dashboard.php');
    } else {
        Response::redirect('customer-profile.php', 'You are currently signed in as a customer.', 'info');
    }
}

$loginError = null;
$identifierValue = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!Csrf::verify($token)) {
        $loginError = 'Security session expired. Please refresh and try again.';
    } else {
        $identifier = trim((string)($_POST['identifier'] ?? ''));
        $password   = (string)($_POST['password'] ?? '');
        $identifierValue = $identifier;

        if (empty($identifier) || empty($password)) {
            $loginError = 'Please provide both your administrative username/email and password.';
        } else {
            $result = Auth::attempt($identifier, $password);
            if ($result['success']) {
                $user = $result['user'];
                if (in_array($user['role'], ['admin', 'manager', 'receptionist'], true)) {
                    Response::redirect('admin-dashboard.php', "Welcome to Concierge Management, " . ucfirst($user['username']) . ".", 'success');
                } else {
                    // Log out customer trying to access admin portal
                    Auth::logout();
                    $loginError = 'Access restricted. This portal is strictly for salon management and staff.';
                }
            } else {
                $loginError = $result['error'] ?? 'Invalid administrative credentials.';
            }
        }
    }
}

$flash = get_flash();
$dbConnected = false;
try {
    $dbConnected = Database::isConnected();
} catch (Exception $e) {
    $dbConnected = false;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Executive Portal Sign In — L'Étoile Luxury Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=3.5">
  <link rel="stylesheet" href="assets/css/responsive.css?v=3.5">
  <style>
    body {
      background: linear-gradient(135deg, #181513 0%, #100E0D 100%);
      color: #FAF7F2;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .admin-card {
      background: #FFFFFF;
      color: var(--color-charcoal);
      border-radius: var(--radius-md);
      padding: var(--space-2xl);
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
      border: 1px solid rgba(212, 175, 55, 0.35);
    }
  </style>
</head>
<body>

  <!-- MINIMAL HEADER -->
  <header style="padding: 20px 0; border-bottom: 1px solid rgba(212, 175, 55, 0.2);">
    <div class="container auth-header-container" style="display: flex; justify-content: space-between; align-items: center;">
      <a href="index.html" class="brand" aria-label="L'Étoile Home">
        <span class="brand-title" style="color: #FAF7F2;">L'ÉTOILE</span>
        <span class="brand-subtitle" style="color: var(--color-champagne);">EXECUTIVE MANAGEMENT PORTAL</span>
      </a>
      <div class="auth-header-actions">
        <a href="index.html" style="color: rgba(250, 247, 242, 0.7); font-size: 0.85rem; text-decoration: none;">
          &larr; Back to Public Sanctuary
        </a>
      </div>
    </div>
  </header>

  <!-- AUTHENTICATION CONTAINER -->
  <main style="flex: 1; display: flex; align-items: center; justify-content: center; padding: var(--space-3xl) var(--space-md);">
    <div class="container" style="max-width: 480px; width: 100%;">

      <!-- Flash Notification -->
      <?php if ($flash): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid var(--color-champagne); padding: 14px 18px; margin-bottom: var(--space-lg); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="font-weight: 600; font-size: 0.9rem; color: var(--color-charcoal);"><?php echo e($flash['message']); ?></div>
        </div>
      <?php endif; ?>

      <!-- Error Notification -->
      <?php if ($loginError): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid #C95353; padding: 14px 18px; margin-bottom: var(--space-lg); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="color: #9E2A2B; font-weight: 600; font-size: 0.9rem;"><?php echo e($loginError); ?></div>
        </div>
      <?php endif; ?>

      <!-- Admin Login Card -->
      <div class="admin-card">
        <div style="text-align: center; margin-bottom: var(--space-xl);">
          <span class="eyebrow eyebrow-no-line" style="color: var(--color-champagne-dark);">Administrative Clearance</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.1rem; color: var(--color-charcoal); margin: 6px 0 10px;">
            Concierge <em>Sign In</em>
          </h1>
          <p style="font-size: 0.88rem; color: var(--color-charcoal-muted); margin: 0; line-height: 1.5;">
            Authorized atelier management, master stylist roster control, and appointment scheduling.
          </p>
        </div>

        <form action="admin-login.php" method="POST" novalidate>
          <?php echo csrf_field(); ?>

          <div class="form-group">
            <label for="identifier" class="form-label">Admin Username or Email <span class="req">*</span></label>
            <input 
              type="text" 
              id="identifier" 
              name="identifier" 
              class="form-control" 
              placeholder="e.g. admin or admin@gmail.com" 
              value="<?php echo e($identifierValue); ?>" 
              required 
              autocomplete="username"
              autofocus
            >
          </div>

          <div class="form-group">
            <label for="password" class="form-label">Password <span class="req">*</span></label>
            <input 
              type="password" 
              id="password" 
              name="password" 
              class="form-control" 
              placeholder="••••••••••••" 
              required 
              autocomplete="current-password"
            >
          </div>

          <button type="submit" class="btn btn-champagne btn-lg" style="width: 100%; margin-top: var(--space-sm);">
            <span>Access Executive Dashboard</span>
            <span class="btn-icon" aria-hidden="true">&rarr;</span>
          </button>
        </form>

        <div style="text-align: center; margin-top: var(--space-xl); padding-top: var(--space-lg); border-top: 1px solid var(--color-charcoal-border);">
          <p style="font-size: 0.85rem; color: var(--color-charcoal-muted); margin-bottom: 6px;">
            Looking for regular customer account access?
          </p>
          <a href="login.php" style="color: var(--color-champagne-dark); font-weight: 600; font-size: 0.9rem; text-decoration: none;">
            &larr; Switch to Guest / Customer Sign In
          </a>
        </div>
      </div>

      <!-- Demonstration Credentials Helper -->
      <div style="margin-top: var(--space-lg); background-color: rgba(255,255,255,0.08); border: 1px dashed rgba(212,175,55,0.4); border-radius: var(--radius-xs); padding: 16px 20px; font-size: 0.85rem; color: rgba(250,247,242,0.85); box-shadow: var(--shadow-subtle);">
        <div style="font-weight: 700; color: var(--color-champagne); text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.08em; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
          <span>👑 Admin Portal Quick-Fill</span>
          <span style="color: var(--color-champagne-bright); font-weight: 600; font-size: 0.75rem;">1-Click Auto-Fill</span>
        </div>
        <div style="margin-bottom: 10px;">
          <button type="button" onclick="fillAdminCreds('admin@salon.com', 'Admin@123')" class="btn btn-champagne btn-sm" style="width: 100%; justify-content: center; font-size: 0.82rem; padding: 10px 14px; white-space: normal; letter-spacing: 0.05em;">
            ⚡ Auto-Fill Demo Admin
          </button>
        </div>
        <div style="font-size: 0.76rem; color: rgba(250,247,242,0.65); line-height: 1.4; border-top: 1px dotted rgba(212,175,55,0.25); padding-top: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px;">
          <div>
            &bull; <strong>Demo:</strong> <code>admin@salon.com</code> (Pass: <code>Admin@123</code>)
            &bull; <a href="javascript:void(0)" onclick="fillAdminCreds('admin', 'admin123')" style="color: var(--color-champagne); font-weight: 600; text-decoration: underline;">Fill admin / admin123</a>
          </div>
          <div><a href="login.php" style="color: var(--color-champagne); font-weight: 600; text-decoration: none;">Client Login &rarr;</a></div>
        </div>
      </div>

      <script>
        function fillAdminCreds(ident, pass) {
          var idEl = document.getElementById('identifier');
          var pwEl = document.getElementById('password');
          if (idEl && pwEl) {
            idEl.value = ident;
            pwEl.value = pass;
            idEl.focus();
          }
        }
      </script>

    </div>
  </main>

  <!-- MINIMAL FOOTER -->
  <footer style="padding: 16px 0; font-size: 0.82rem; text-align: center; border-top: 1px solid rgba(212, 175, 55, 0.15); color: rgba(250, 247, 242, 0.5);">
    <div class="container">
      &copy; <?php echo date('Y'); ?> L'Étoile Luxury Unisex Hair Salon &amp; Spa &bull; Executive Administrative Console
    </div>
  </footer>

</body>
</html>
