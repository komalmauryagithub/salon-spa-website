<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Customer & Concierge Sign In Portal
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

$redirectTarget = trim((string)($_GET['redirect'] ?? $_POST['redirect'] ?? ''));
// Validate redirect to prevent Open Redirect vulnerability (relative URLs only)
if ($redirectTarget && (preg_match('#^https?://#i', $redirectTarget) || str_starts_with($redirectTarget, '//') || str_contains($redirectTarget, '\\'))) {
    $redirectTarget = '';
}
$isBookingReason = (isset($_GET['reason']) && $_GET['reason'] === 'booking') || str_contains($redirectTarget, 'book-appointment');

// Redirect if already authenticated
if (Auth::check()) {
    if ($redirectTarget) {
        Response::redirect($redirectTarget);
    }
    if (Auth::hasRole('customer')) {
        Response::redirect('customer-profile.php');
    } elseif (Auth::hasAnyRole(['admin', 'manager', 'receptionist'])) {
        Response::redirect('admin-dashboard.php');
    } else {
        Response::redirect('index.html', 'You are currently signed in as ' . e(Auth::role()) . '.', 'info');
    }
}

$loginError = null;
$identifierValue = '';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Verification
    $token = $_POST['csrf_token'] ?? null;
    if (!Csrf::verify($token)) {
        $loginError = 'Security session expired. Please refresh and try again.';
    } else {
        $identifier = trim((string)($_POST['identifier'] ?? ''));
        $password   = (string)($_POST['password'] ?? '');
        $identifierValue = $identifier;

        if (empty($identifier) || empty($password)) {
            $loginError = 'Please provide both your account identifier and password.';
        } else {
            // 2. Authenticate
            $result = Auth::attempt($identifier, $password);
            if ($result['success']) {
                $user = $result['user'];
                if ($user['role'] === 'customer') {
                    $customer = Auth::customer();
                    $name = $customer ? $customer['first_name'] : $user['username'];
                    if ($redirectTarget) {
                        Response::redirect($redirectTarget, "Welcome back, {$name}! You can now complete your appointment booking.", 'success');
                    } else {
                        Response::redirect('customer-profile.php', "Welcome back to L'Étoile, {$name}.", 'success');
                    }
                } elseif (in_array($user['role'], ['admin', 'manager', 'receptionist'], true)) {
                    if ($redirectTarget) {
                        Response::redirect($redirectTarget, "Signed in as Staff. Proceeding to reservation...", 'success');
                    } else {
                        Response::redirect('admin-dashboard.php', "Welcome to Concierge Management, " . ucfirst($user['username']) . ".", 'success');
                    }
                } else {
                    Response::redirect('index.html', "Signed in successfully as " . ucfirst($user['role']) . ".", 'success');
                }
            } else {
                $loginError = $result['error'] ?? 'Invalid sign-in credentials.';
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
  <title>Sign In — L'Étoile Luxury Unisex Hair Salon &amp; Spa</title>
  <meta name="description" content="Sign in to your private L'Étoile salon account to view personal appointment history and update ritual preferences.">
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="assets/css/style.css?v=3.5">
  <link rel="stylesheet" href="assets/css/responsive.css?v=3.5">
</head>
<body style="background-color: var(--color-ivory); min-height: 100vh; display: flex; flex-direction: column;">

  <!-- TOP BAR -->
  <div class="top-bar">
    <div class="container top-bar-inner">
      <div class="top-bar-left">
        <span class="top-bar-item">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
          <span>482 Boulevard Saint-Honoré, Suite 400</span>
        </span>
      </div>
      <div class="top-bar-right">
        <span class="top-bar-item">
          <a href="tel:+18005550192">+1 (800) 555-0192</a>
        </span>
      </div>
    </div>
  </div>

  <!-- MINIMAL HEADER -->
  <header class="site-header" style="position: relative; border-bottom: 1px solid var(--color-charcoal-border);">
    <div class="container nav-container auth-header-container" style="justify-content: space-between;">
      <a href="index.html" class="brand" aria-label="L'Étoile Home">
        <span class="brand-title">L'ÉTOILE</span>
        <span class="brand-subtitle">HAIR SALON &amp; SPA</span>
      </a>
      <div class="auth-header-actions" style="display: flex; gap: var(--space-md); align-items: center;">
        <a href="index.html" class="nav-link" style="font-size: 0.9rem;">&larr; Back to Sanctuary</a>
        <a href="register.php<?php echo $redirectTarget ? '?redirect=' . urlencode($redirectTarget) . '&reason=booking' : ''; ?>" class="btn btn-outline btn-sm">Create Account</a>
      </div>
    </div>
  </header>

  <!-- AUTHENTICATION CONTAINER -->
  <main style="flex: 1; display: flex; align-items: center; justify-content: center; padding: var(--space-3xl) var(--space-md);">
    <div class="container" style="max-width: 480px; width: 100%;">

      <!-- Flash Notification -->
      <?php if ($flash): ?>
        <div style="background-color: <?php echo $flash['type'] === 'success' ? '#FFFFFF' : '#FFFFFF'; ?>; border-left: 4px solid <?php echo $flash['type'] === 'success' ? 'var(--color-champagne)' : '#C95353'; ?>; padding: 14px 18px; margin-bottom: var(--space-lg); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="font-weight: 600; font-size: 0.9rem; color: var(--color-charcoal);"><?php echo e($flash['message']); ?></div>
        </div>
      <?php endif; ?>

      <!-- Error Notification -->
      <?php if ($loginError): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid #C95353; padding: 14px 18px; margin-bottom: var(--space-lg); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="color: #9E2A2B; font-weight: 600; font-size: 0.9rem;"><?php echo e($loginError); ?></div>
        </div>
      <?php endif; ?>

      <!-- Booking Notice -->
      <?php if ($isBookingReason): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid var(--color-champagne); padding: 16px 20px; margin-bottom: var(--space-lg); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 1.5rem;">📅</span>
            <div>
              <div style="font-weight: 700; font-size: 0.95rem; color: var(--color-charcoal);">Sign In to Complete Your Reservation</div>
              <p style="margin: 3px 0 0; font-size: 0.84rem; color: var(--color-charcoal-muted); line-height: 1.4;">
                Please sign in to your client account to confirm your appointment. You will be redirected right back to finalize your ritual.
              </p>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Login Card -->
      <div class="form-card" style="box-shadow: 0 15px 35px rgba(37,35,33,0.06); padding: var(--space-2xl);">
        <div style="text-align: center; margin-bottom: var(--space-xl);">
          <span class="eyebrow eyebrow-no-line">Guest Portal</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.1rem; color: var(--color-charcoal); margin: 6px 0 10px;">
            Atelier <em>Sign In</em>
          </h1>
          <p style="font-size: 0.92rem; color: var(--color-charcoal-muted); margin: 0; line-height: 1.5;">
            Access your personal appointment schedule, treatment formulas, and bespoke sanctuary preferences.
          </p>
        </div>

        <form action="login.php" method="POST" novalidate>
          <?php echo csrf_field(); ?>
          <?php if ($redirectTarget): ?>
            <input type="hidden" name="redirect" value="<?php echo e($redirectTarget); ?>">
          <?php endif; ?>

          <div class="form-group">
            <label for="identifier" class="form-label">Email Address or Username <span class="req">*</span></label>
            <input 
              type="text" 
              id="identifier" 
              name="identifier" 
              class="form-control" 
              placeholder="user or user@gmail.com" 
              value="<?php echo e($identifierValue); ?>" 
              required 
              autocomplete="username"
              autofocus
            >
          </div>

          <div class="form-group">
            <div style="display: flex; justify-content: space-between; align-items: baseline;">
              <label for="password" class="form-label">Password <span class="req">*</span></label>
            </div>
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

          <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: var(--space-sm);">
            <span>Sign In to Sanctuary</span>
            <span class="btn-icon" aria-hidden="true">&rarr;</span>
          </button>
        </form>

        <div style="text-align: center; margin-top: var(--space-xl); padding-top: var(--space-lg); border-top: 1px solid var(--color-charcoal-border);">
          <p style="font-size: 0.9rem; color: var(--color-charcoal-muted); margin-bottom: 8px;">
            First time visiting our digital atelier?
          </p>
          <a href="register.php<?php echo $redirectTarget ? '?redirect=' . urlencode($redirectTarget) . '&reason=booking' : ''; ?>" style="color: var(--color-champagne-dark); font-weight: 600; font-size: 0.95rem; text-decoration: none;">
            Create Your Atelier Account &rarr;
          </a>
        </div>
      </div>

      <!-- Quick-Fill Easy Credentials Helper -->
      <div style="margin-top: var(--space-lg); background-color: rgba(255,255,255,0.85); border: 1px dashed var(--color-champagne); border-radius: var(--radius-xs); padding: 16px 20px; font-size: 0.85rem; color: var(--color-charcoal); box-shadow: var(--shadow-subtle);">
        <div style="font-weight: 700; color: var(--color-charcoal); text-transform: uppercase; font-size: 0.72rem; letter-spacing: 0.08em; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
          <span style="display: inline-flex; align-items: center; gap: 6px;">
            <span style="display: inline-block; width: 9px; height: 9px; border-radius: 50%; background: <?php echo $dbConnected ? '#2D6A4F' : 'var(--color-champagne)'; ?>;"></span>
            <span><?php echo $dbConnected ? 'Database: <strong style="color:#2D6A4F;">Connected</strong>' : 'Mode: <strong style="color:var(--color-champagne-dark);">Demo Showcase (No DB Required)</strong>'; ?></span>
          </span>
          <span style="color: var(--color-champagne-dark); font-weight: 600; font-size: 0.75rem; text-transform: none;">1-Click Auto-Fill</span>
        </div>
        <div style="margin-bottom: 10px;">
          <button type="button" onclick="fillCreds('demo@salon.com', 'Demo@123')" class="btn btn-champagne btn-sm" style="width: 100%; justify-content: center; font-size: 0.82rem; padding: 10px 14px; white-space: normal; letter-spacing: 0.05em;">
            👤 Auto-Fill Demo Client
          </button>
        </div>
        <div style="font-size: 0.78rem; color: var(--color-charcoal-muted); line-height: 1.5; border-top: 1px dotted var(--color-charcoal-border); padding-top: 8px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 6px;">
          <div>
            &bull; <strong>Demo:</strong> <code>demo@salon.com</code> (Pass: <code>Demo@123</code>)
            &bull; <a href="javascript:void(0)" onclick="fillCreds('user', 'user123')" style="color: var(--color-champagne-dark); font-weight: 600; text-decoration: underline;">Fill user / user123</a>
          </div>
          <div><a href="admin-login.php" style="color: var(--color-champagne-dark); font-weight: 600; text-decoration: none;">Staff Sign In &rarr;</a></div>
        </div>
      </div>

      <script>
        function fillCreds(ident, pass) {
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
  <footer style="background-color: var(--color-charcoal); color: var(--color-ivory); padding: var(--space-lg) 0; font-size: 0.85rem; text-align: center; border-top: 1px solid var(--color-champagne-border);">
    <div class="container">
      <p style="margin: 0; color: rgba(245,239,230,0.7);">
        &copy; <?php echo date('Y'); ?> L'Étoile Luxury Unisex Hair Salon &amp; Spa. All rights reserved.
      </p>
    </div>
  </footer>

</body>
</html>
