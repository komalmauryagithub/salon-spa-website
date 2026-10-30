<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Isolated Demo Mode Concierge Gateway
 * 
 * Strict Brand Theme: Ivory #FAF7F2, Champagne #D4AF37, Charcoal #161413, White #FFFFFF
 */

require_once __DIR__ . '/../includes/bootstrap.php';
Demo::requireEnabled();
DemoStore::initSessionStore();

// Handle 1-Click Reset or Quick-Launch actions
$action = $_GET['action'] ?? null;
if ($action === 'reset') {
    DemoStore::seedInitialDemoData();
    header('Location: index.php?notice=' . urlencode('Demo session data has been reset to initial baseline.'));
    exit;
} elseif ($action === 'quick_customer') {
    DemoAuth::attemptCustomer(DEMO_CUSTOMER_EMAIL, DEMO_CUSTOMER_PASS);
    header('Location: customer-profile.php');
    exit;
} elseif ($action === 'quick_admin') {
    DemoAuth::attemptAdmin(DEMO_ADMIN_EMAIL, DEMO_ADMIN_PASS);
    header('Location: admin-dashboard.php');
    exit;
}

$notice = $_GET['notice'] ?? null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Demo Mode Portal — L'Étoile Luxury Unisex Hair Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  
  <style>
    .demo-gateway-card {
      background: var(--color-white);
      border: 1px solid var(--color-charcoal-border);
      border-radius: var(--radius-xs);
      padding: var(--space-2xl);
      transition: transform var(--transition-fast), box-shadow var(--transition-fast), border-color var(--transition-fast);
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .demo-gateway-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--shadow-hover);
      border-color: var(--color-champagne);
    }
    .demo-badge-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 14px;
      border-radius: 20px;
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }
    .badge-champagne {
      background: rgba(212,175,55,0.12);
      color: var(--color-champagne-dark);
      border: 1px solid var(--color-champagne);
    }
    .badge-charcoal {
      background: var(--color-charcoal);
      color: var(--color-ivory);
    }
    .creds-box {
      background: var(--color-ivory);
      border: 1px dashed var(--color-champagne);
      border-radius: var(--radius-xs);
      padding: 14px 18px;
      margin: var(--space-md) 0 var(--space-lg);
      font-size: 0.85rem;
      text-align: left;
    }
  </style>
</head>
<body style="background-color: var(--color-ivory); min-height: 100vh; display: flex; flex-direction: column;">

  <!-- TOP BAR -->
  <div class="top-bar">
    <div class="container top-bar-inner">
      <div class="top-bar-left">
        <span class="top-bar-item">
          <span>🎭 <strong>ISOLATED DEMO ENVIRONMENT</strong> &bull; Zero Live Database Required</span>
        </span>
      </div>
      <div class="top-bar-right">
        <span class="top-bar-item">
          <a href="../index.html" style="color:var(--color-champagne-light);">&larr; Return to Production Sanctuary</a>
        </span>
      </div>
    </div>
  </div>

  <!-- MINIMAL HEADER -->
  <header class="site-header" style="position: relative; border-bottom: 1px solid var(--color-charcoal-border);">
    <div class="container nav-container" style="justify-content: space-between;">
      <a href="../index.html" class="brand" aria-label="L'Étoile Home">
        <span class="brand-title">L'ÉTOILE</span>
        <span class="brand-subtitle">HAIR SALON &amp; SPA &bull; DEMO</span>
      </a>
      <div style="display: flex; gap: var(--space-md); align-items: center;">
        <a href="book-appointment.php" class="btn btn-champagne btn-sm">
          <span>Book Demo Ritual</span>
          <span class="btn-icon">&rarr;</span>
        </a>
      </div>
    </div>
  </header>

  <!-- MAIN GATEWAY CONTENT -->
  <main style="flex: 1; padding: var(--space-3xl) 0 var(--space-4xl);">
    <div class="container" style="max-width: 1040px;">

      <!-- Notice Alert -->
      <?php if ($notice): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid var(--color-champagne); padding: 14px 18px; margin-bottom: var(--space-xl); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="font-weight: 600; font-size: 0.92rem; color: var(--color-charcoal);"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endif; ?>

      <!-- Hero Header -->
      <div style="text-align: center; margin-bottom: var(--space-3xl);">
        <span class="demo-badge-pill badge-champagne" style="margin-bottom: var(--space-xs);">
          ✦ Pure In-Memory Demonstration Architecture
        </span>
        <h1 style="font-family: var(--font-serif); font-size: 2.6rem; color: var(--color-charcoal); margin: 8px 0 12px;">
          Explore the Atelier <em>Demo Suite</em>
        </h1>
        <p style="color: var(--color-charcoal-muted); max-width: 680px; margin: 0 auto; font-size: 0.98rem; line-height: 1.6;">
          Demonstrate client booking, VIP account management, and executive salon operations without touching or modifying the live MySQL database.
        </p>
      </div>

      <!-- 2-Portal Cards Grid -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: var(--space-2xl); margin-bottom: var(--space-3xl);">
        
        <!-- CARD 1: CUSTOMER PORTAL -->
        <div class="demo-gateway-card">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-md);">
              <span class="demo-badge-pill badge-champagne">👤 Client Experience</span>
              <span style="font-size: 0.75rem; color: var(--color-charcoal-muted);">VIP Patron Portal</span>
            </div>
            <h2 style="font-family: var(--font-serif); font-size: 1.8rem; margin: 0 0 8px; color: var(--color-charcoal);">
              Customer Sanctuary
            </h2>
            <p style="font-size: 0.9rem; color: var(--color-charcoal-muted); line-height: 1.5; margin: 0;">
              Experience the client booking journey, personal appointment itinerary, preference formulas, and self-service reservation cancellation.
            </p>

            <div class="creds-box">
              <div style="font-weight: 700; color: var(--color-charcoal); margin-bottom: 6px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">
                Demo Client Credentials:
              </div>
              <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span>Email:</span> <code><?php echo DEMO_CUSTOMER_EMAIL; ?></code>
              </div>
              <div style="display: flex; justify-content: space-between;">
                <span>Password:</span> <code><?php echo DEMO_CUSTOMER_PASS; ?></code>
              </div>
            </div>
          </div>

          <div style="display: flex; flex-direction: column; gap: 10px;">
            <a href="index.php?action=quick_customer" class="btn btn-champagne" style="width: 100%; justify-content: center;">
              <span>⚡ 1-Click Launch Client Portal</span>
              <span class="btn-icon">&rarr;</span>
            </a>
            <div style="display: flex; gap: 8px;">
              <a href="login.php" class="btn btn-outline btn-sm" style="flex: 1; justify-content: center;">Manual Sign In</a>
              <a href="book-appointment.php" class="btn btn-outline btn-sm" style="flex: 1; justify-content: center;">Book Ritual</a>
            </div>
          </div>
        </div>

        <!-- CARD 2: ADMIN MANAGEMENT PORTAL -->
        <div class="demo-gateway-card">
          <div>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-md);">
              <span class="demo-badge-pill badge-charcoal">👑 Concierge Management</span>
              <span style="font-size: 0.75rem; color: var(--color-charcoal-muted);">Executive Console</span>
            </div>
            <h2 style="font-family: var(--font-serif); font-size: 1.8rem; margin: 0 0 8px; color: var(--color-charcoal);">
              Admin Dashboard
            </h2>
            <p style="font-size: 0.9rem; color: var(--color-charcoal-muted); line-height: 1.5; margin: 0;">
              Review real-time demonstration salon revenue, moderate appointment bookings (Confirm, Complete, Cancel), and inspect customer records.
            </p>

            <div class="creds-box">
              <div style="font-weight: 700; color: var(--color-charcoal); margin-bottom: 6px; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em;">
                Demo Admin Credentials:
              </div>
              <div style="display: flex; justify-content: space-between; margin-bottom: 4px;">
                <span>Email:</span> <code><?php echo DEMO_ADMIN_EMAIL; ?></code>
              </div>
              <div style="display: flex; justify-content: space-between;">
                <span>Password:</span> <code><?php echo DEMO_ADMIN_PASS; ?></code>
              </div>
            </div>
          </div>

          <div style="display: flex; flex-direction: column; gap: 10px;">
            <a href="index.php?action=quick_admin" class="btn btn-primary" style="width: 100%; justify-content: center;">
              <span>⚡ 1-Click Launch Admin Console</span>
              <span class="btn-icon">&rarr;</span>
            </a>
            <a href="admin-login.php" class="btn btn-outline btn-sm" style="justify-content: center;">Manual Admin Sign In</a>
          </div>
        </div>

      </div>

      <!-- Isolation Guarantee Banner -->
      <div style="background: var(--color-white); border: 1px solid var(--color-charcoal-border); border-radius: var(--radius-xs); padding: var(--space-xl); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-md);">
        <div>
          <h3 style="font-family: var(--font-serif); font-size: 1.25rem; margin: 0 0 4px; color: var(--color-charcoal);">
            Zero Risk to Production Backend
          </h3>
          <p style="font-size: 0.86rem; color: var(--color-charcoal-muted); margin: 0; line-height: 1.4;">
            All demo interactions use isolated PHP Session storage. Production MySQL tables and schemas remain 100% untouched.
          </p>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
          <a href="index.php?action=reset" class="btn btn-outline btn-sm" onclick="return confirm('Reset demo session data to default sample records?');">
            ↺ Reset Demo Data
          </a>
          <a href="../index.html" class="btn btn-champagne btn-sm">
            Visit Public Website &rarr;
          </a>
        </div>
      </div>

    </div>
  </main>

  <!-- FOOTER -->
  <footer style="background-color: var(--color-charcoal); color: var(--color-ivory); padding: var(--space-lg) 0; font-size: 0.85rem; text-align: center; border-top: 1px solid var(--color-champagne-border);">
    <div class="container">
      <p style="margin: 0; color: rgba(245,239,230,0.7);">
        &copy; <?php echo date('Y'); ?> L'Étoile Luxury Unisex Hair Salon &amp; Spa &bull; Isolated Demonstration Mode (<code>DEMO_MODE=true</code>)
      </p>
    </div>
  </footer>

</body>
</html>
