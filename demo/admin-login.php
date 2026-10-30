<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Demo Executive Admin Sign In Portal
 * 
 * Strict Brand Theme: Obsidian Charcoal #161413, Champagne Gold #D4AF37, Ivory #FAF7F2, White #FFFFFF
 */

require_once __DIR__ . '/../includes/bootstrap.php';
Demo::requireEnabled();

// If already authenticated as demo admin, redirect to demo dashboard
if (DemoAuth::isAdmin()) {
    header('Location: admin-dashboard.php');
    exit;
}

$loginError = null;
$emailValue = DEMO_ADMIN_EMAIL;

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['demo_csrf_token'] ?? null;
    if (!DemoCsrf::verify($token)) {
        $loginError = 'Security session expired. Please refresh and try again.';
    } else {
        $email = trim((string)($_POST['email'] ?? ''));
        $pass  = (string)($_POST['password'] ?? '');
        $emailValue = $email;

        $auth = DemoAuth::attemptAdmin($email, $pass);
        if ($auth['success']) {
            header('Location: admin-dashboard.php');
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
  <title>Executive Admin Sign In (Demo Mode) — L'Étoile Luxury Unisex Hair Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body style="background-color: var(--color-charcoal); color: var(--color-ivory); min-height: 100vh; display: flex; flex-direction: column;">

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
          <a href="index.php" style="color:var(--color-champagne-light);">&larr; Demo Gateway</a>
        </span>
      </div>
    </div>
  </div>

  <!-- MINIMAL HEADER -->
  <header class="site-header" style="position: relative; background: rgba(22,20,19,0.95); border-bottom: 1px solid var(--color-champagne-border);">
    <div class="container nav-container" style="justify-content: space-between;">
      <a href="index.php" class="brand" aria-label="L'Étoile Home">
        <span class="brand-title" style="color: var(--color-white);">L'ÉTOILE</span>
        <span class="brand-subtitle" style="color: var(--color-champagne-light);">CONCIERGE MANAGEMENT &bull; DEMO</span>
      </a>
      <div style="display: flex; gap: var(--space-md); align-items: center;">
        <a href="login.php" class="nav-link" style="color: var(--color-ivory); font-size: 0.9rem;">&larr; Demo Client Login</a>
        <a href="../admin-login.php" class="btn btn-outline btn-sm" style="color: var(--color-ivory); border-color: rgba(255,255,255,0.25);">Production Admin Login</a>
      </div>
    </div>
  </header>

  <!-- AUTHENTICATION CONTAINER -->
  <main style="flex: 1; display: flex; align-items: center; justify-content: center; padding: var(--space-3xl) var(--space-md);">
    <div class="container" style="max-width: 480px; width: 100%;">

      <!-- Notice Alert -->
      <?php if ($notice): ?>
        <div style="background-color: #252321; border-left: 4px solid var(--color-champagne); padding: 14px 18px; margin-bottom: var(--space-lg); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="font-weight: 600; font-size: 0.9rem; color: var(--color-ivory);"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endif; ?>

      <!-- Error Notification -->
      <?php if ($loginError): ?>
        <div style="background-color: #252321; border-left: 4px solid #C95353; padding: 14px 18px; margin-bottom: var(--space-lg); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="color: #FF8080; font-weight: 600; font-size: 0.9rem;"><?php echo htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endif; ?>

      <!-- Login Card -->
      <div class="form-card" style="background: #1C1A18; border: 1px solid var(--color-champagne-border); box-shadow: 0 20px 45px rgba(0,0,0,0.4); padding: var(--space-2xl);">
        <div style="text-align: center; margin-bottom: var(--space-xl);">
          <span class="eyebrow eyebrow-no-line" style="color: var(--color-champagne-light);">Management Console</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.1rem; color: var(--color-white); margin: 6px 0 10px;">
            Demo Concierge <em>Sign In</em>
          </h1>
          <p style="font-size: 0.92rem; color: rgba(245,239,230,0.7); margin: 0; line-height: 1.5;">
            Access Madame Vivienne Laurent's demonstration dashboard, review salon metrics, and moderate client appointments.
          </p>
        </div>

        <form action="admin-login.php" method="POST" novalidate>
          <?php echo DemoCsrf::field(); ?>

          <div class="form-group">
            <label for="email" class="form-label" style="color: var(--color-ivory);">Demo Admin Email <span class="req">*</span></label>
            <input 
              type="email" 
              id="email" 
              name="email" 
              class="form-control" 
              placeholder="admin@salon.com" 
              value="<?php echo htmlspecialchars($emailValue, ENT_QUOTES, 'UTF-8'); ?>" 
              required 
              autofocus
              style="background:#252321; color:#FAF7F2; border-color:var(--color-champagne-border);"
            >
          </div>

          <div class="form-group">
            <label for="password" class="form-label" style="color: var(--color-ivory);">Demo Admin Password <span class="req">*</span></label>
            <input 
              type="password" 
              id="password" 
              name="password" 
              class="form-control" 
              placeholder="Admin@123" 
              value="<?php echo DEMO_ADMIN_PASS; ?>"
              required 
              style="background:#252321; color:#FAF7F2; border-color:var(--color-champagne-border);"
            >
          </div>

          <button type="submit" class="btn btn-champagne btn-lg" style="width: 100%; margin-top: var(--space-sm);">
            <span>Access Concierge Console</span>
            <span class="btn-icon" aria-hidden="true">&rarr;</span>
          </button>
        </form>

        <!-- Quick 1-Click Fill Helper -->
        <div style="margin-top: var(--space-lg); background-color: #252321; border: 1px dashed var(--color-champagne); border-radius: var(--radius-xs); padding: 14px 16px; font-size: 0.85rem; color: var(--color-ivory);">
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
            <strong style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-champagne-light);">Demo Admin Credentials</strong>
            <button type="button" onclick="fillAdminCreds()" style="background:none; border:none; color:var(--color-champagne); font-weight:700; font-size:0.75rem; text-decoration:underline; cursor:pointer;">
              ⚡ Auto-Fill
            </button>
          </div>
          <div style="font-size: 0.8rem; line-height: 1.5; color: rgba(245,239,230,0.7);">
            Email: <code><?php echo DEMO_ADMIN_EMAIL; ?></code><br>
            Password: <code><?php echo DEMO_ADMIN_PASS; ?></code>
          </div>
        </div>

        <div style="text-align: center; margin-top: var(--space-xl); padding-top: var(--space-lg); border-top: 1px solid var(--color-champagne-border); display: flex; justify-content: space-between; font-size: 0.88rem;">
          <a href="index.php" style="color: var(--color-champagne-light); font-weight: 600; text-decoration: none;">
            &larr; Demo Gateway
          </a>
          <a href="customer-profile.php" style="color: rgba(245,239,230,0.7); text-decoration: none;">
            Customer Portal &rarr;
          </a>
        </div>
      </div>

    </div>
  </main>

  <script>
    function fillAdminCreds() {
      document.getElementById('email').value = '<?php echo DEMO_ADMIN_EMAIL; ?>';
      document.getElementById('password').value = '<?php echo DEMO_ADMIN_PASS; ?>';
      document.getElementById('password').focus();
    }
  </script>

  <!-- MINIMAL FOOTER -->
  <footer style="background-color: #0F0E0D; color: var(--color-ivory); padding: var(--space-lg) 0; font-size: 0.85rem; text-align: center; border-top: 1px solid var(--color-champagne-border);">
    <div class="container">
      <p style="margin: 0; color: rgba(245,239,230,0.7);">
        &copy; <?php echo date('Y'); ?> L'Étoile Luxury Unisex Hair Salon &amp; Spa &bull; Demo Mode Admin Portal
      </p>
    </div>
  </footer>

</body>
</html>
