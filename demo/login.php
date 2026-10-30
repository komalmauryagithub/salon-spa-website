<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Demo Customer Sign In Portal
 * 
 * Strict Brand Theme: Ivory #FAF7F2, Champagne #D4AF37, Charcoal #161413, White #FFFFFF
 */

require_once __DIR__ . '/../includes/bootstrap.php';
Demo::requireEnabled();

// If already authenticated as demo customer, redirect
if (DemoAuth::isCustomer()) {
    $redirect = trim((string)($_GET['redirect'] ?? 'customer-profile.php'));
    if (preg_match('#^https?://#i', $redirect) || str_starts_with($redirect, '//') || str_contains($redirect, '\\')) {
        $redirect = 'customer-profile.php';
    }
    header("Location: {$redirect}");
    exit;
}

$loginError = null;
$emailValue = DEMO_CUSTOMER_EMAIL; // default to demo email for quick preview
$redirectTarget = trim((string)($_GET['redirect'] ?? $_POST['redirect'] ?? ''));
if ($redirectTarget && (preg_match('#^https?://#i', $redirectTarget) || str_starts_with($redirectTarget, '//') || str_contains($redirectTarget, '\\'))) {
    $redirectTarget = '';
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['demo_csrf_token'] ?? null;
    if (!DemoCsrf::verify($token)) {
        $loginError = 'Security session expired. Please refresh and try again.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');
        $emailValue = $email;

        $auth = DemoAuth::attemptCustomer($email, $pass);
        if ($auth['success']) {
            $target = $redirectTarget ?: 'customer-profile.php';
            header("Location: {$target}");
            exit;
        } else {
            $loginError = $auth['error'];
        }
    }
}

$notice = $_GET['notice'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Demo Client Sign In — L'Étoile Luxury Unisex Hair Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body style="background-color: var(--color-ivory); min-height: 100vh; display: flex; flex-direction: column;">

  <!-- TOP BAR -->
  <div class="top-bar">
    <div class="container top-bar-inner">
      <div class="top-bar-left">
        <span class="top-bar-item">
          <span>🎭 <strong>DEMO MODE: CUSTOMER ACCESS</strong> &bull; Zero Live Database Required</span>
        </span>
      </div>
      <div class="top-bar-right">
        <span class="top-bar-item">
          <a href="index.php" style="color:var(--color-champagne-light);">&larr; Demo Gateway</a>
        </span>
      </div>
    </div>
  </div>

  <!-- MINIMAL HEADER -->
  <header class="site-header" style="position: relative; border-bottom: 1px solid var(--color-charcoal-border);">
    <div class="container nav-container" style="justify-content: space-between;">
      <a href="index.php" class="brand" aria-label="L'Étoile Home">
        <span class="brand-title">L'ÉTOILE</span>
        <span class="brand-subtitle">HAIR SALON &amp; SPA &bull; DEMO</span>
      </a>
      <div style="display: flex; gap: var(--space-md); align-items: center;">
        <a href="index.php" class="nav-link" style="font-size: 0.9rem;">&larr; Demo Hub</a>
        <a href="admin-login.php" class="btn btn-outline btn-sm">Demo Admin Login</a>
      </div>
    </div>
  </header>

  <!-- AUTHENTICATION CONTAINER -->
  <main style="flex: 1; display: flex; align-items: center; justify-content: center; padding: var(--space-3xl) var(--space-md);">
    <div class="container" style="max-width: 480px; width: 100%;">

      <!-- Notice Alert -->
      <?php if ($notice): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid var(--color-champagne); padding: 14px 18px; margin-bottom: var(--space-lg); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="font-weight: 600; font-size: 0.9rem; color: var(--color-charcoal);"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endif; ?>

      <!-- Error Notification -->
      <?php if ($loginError): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid #C95353; padding: 14px 18px; margin-bottom: var(--space-lg); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="color: #9E2A2B; font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endif; ?>

      <!-- Login Card -->
      <div class="form-card" style="box-shadow: 0 15px 35px rgba(37,35,33,0.06); padding: var(--space-2xl);">
        <div style="text-align: center; margin-bottom: var(--space-xl);">
          <span class="eyebrow eyebrow-no-line">Demo Customer Experience</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.1rem; color: var(--color-charcoal); margin: 6px 0 10px;">
            Demo Client <em>Sign In</em>
          </h1>
          <p style="font-size: 0.92rem; color: var(--color-charcoal-muted); margin: 0; line-height: 1.5;">
            Access Adrienne Laurent's simulated appointment history, ritual formulas, and VIP sanctuary profile.
          </p>
        </div>

        <form action="login.php" method="POST" novalidate>
          <?php echo DemoCsrf::field(); ?>
          <?php if ($redirectTarget): ?>
            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirectTarget, ENT_QUOTES, 'UTF-8'); ?>">
          <?php endif; ?>

          <div class="form-group">
            <label for="email" class="form-label">Demo Email Address <span class="req">*</span></label>
            <input 
              type="email" 
              id="email" 
              name="email" 
              class="form-control" 
              placeholder="demo@salon.com" 
              value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>" 
              required 
              autofocus
            >
          </div>

          <div class="form-group">
            <label for="password" class="form-label">Demo Password <span class="req">*</span></label>
            <input 
              type="password" 
              id="password" 
              name="password" 
              class="form-control" 
              placeholder="Demo@123" 
              value="<?php echo DEMO_CUSTOMER_PASS; ?>"
              required 
            >
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: var(--space-sm);">
            <span>Sign In to Demo Sanctuary</span>
            <span class="btn-icon" aria-hidden="true">&rarr;</span>
          </button>
        </form>

        <!-- Quick 1-Click Fill Helper -->
        <div style="margin-top: var(--space-lg); background-color: var(--color-ivory); border: 1px dashed var(--color-champagne); border-radius: var(--radius-xs); padding: 14px 16px; font-size: 0.85rem; color: var(--color-charcoal);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <strong style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-champagne-dark);">Demo Credentials</strong>
            <button type="button" onclick="fillDemoCreds()" style="background:none; border:none; color:var(--color-charcoal); font-weight:700; font-size:0.75rem; text-decoration:underline; cursor:pointer;">
              ⚡ Auto-Fill
            </button>
          </div>
          <div style="font-size: 0.8rem; line-height: 1.5; color: var(--color-charcoal-muted);">
            Email: <code><?php echo DEMO_CUSTOMER_EMAIL; ?></code><br>
            Password: <code><?php echo DEMO_CUSTOMER_PASS; ?></code>
          </div>
        </div>

        <div style="text-align: center; margin-top: var(--space-xl); padding-top: var(--space-lg); border-top: 1px solid var(--color-charcoal-border); display: flex; justify-content: space-between; font-size: 0.88rem;">
          <a href="book-appointment.php" style="color: var(--color-champagne-dark); font-weight: 600; text-decoration: none;">
            Book Demo Ritual &rarr;
          </a>
          <a href="../login.php" style="color: var(--color-charcoal-muted); text-decoration: none;">
            Production Login &rarr;
          </a>
        </div>
      </div>

    </div>
  </main>

  <script>
    function fillDemoCreds() {
      document.getElementById('email').value = '<?php echo DEMO_CUSTOMER_EMAIL; ?>';
      document.getElementById('password').value = '<?php echo DEMO_CUSTOMER_PASS; ?>';
      document.getElementById('password').focus();
    }
  </script>

  <!-- MINIMAL FOOTER -->
  <footer style="background-color: var(--color-charcoal); color: var(--color-ivory); padding: var(--space-lg) 0; font-size: 0.85rem; text-align: center; border-top: 1px solid var(--color-champagne-border);">
    <div class="container">
      <p style="margin: 0; color: rgba(245,239,230,0.7);">
        &copy; <?php echo date('Y'); ?> L'Étoile Luxury Unisex Hair Salon &amp; Spa &bull; Demo Mode Customer Portal
      </p>
    </div>
  </footer>

</body>
</html>
