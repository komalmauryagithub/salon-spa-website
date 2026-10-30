<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Customer Registration Portal
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
    } else {
        Response::redirect('index.html', 'You are currently signed in as ' . e(Auth::role()) . '.', 'info');
    }
}

$registerError = null;
$fieldErrors = [];
$formData = [
    'first_name'        => '',
    'last_name'         => '',
    'email'             => '',
    'phone'             => '',
    'gender_preference' => 'Unisex / Flexible',
    'allergies_notes'   => ''
];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. CSRF Verification
    $token = $_POST['csrf_token'] ?? null;
    if (!Csrf::verify($token)) {
        $registerError = 'Security session expired. Please refresh and try again.';
    } else {
        $formData['first_name']        = trim((string)($_POST['first_name'] ?? ''));
        $formData['last_name']         = trim((string)($_POST['last_name'] ?? ''));
        $formData['email']             = trim((string)($_POST['email'] ?? ''));
        $formData['phone']             = trim((string)($_POST['phone'] ?? ''));
        $formData['gender_preference'] = (string)($_POST['gender_preference'] ?? 'Unisex / Flexible');
        $formData['allergies_notes']   = trim((string)($_POST['allergies_notes'] ?? ''));

        // 2. Register Customer Account
        $result = Auth::registerCustomer($_POST);
        if ($result['success']) {
            if ($redirectTarget) {
                Response::redirect($redirectTarget, "Welcome to L'Étoile, " . e($formData['first_name']) . "! Your account has been established. You may now complete your reservation.", 'success');
            } else {
                Response::redirect('customer-profile.php', "Welcome to the L'Étoile Atelier Circle, " . e($formData['first_name']) . "! Your account has been established.", 'success');
            }
        } else {
            $registerError = $result['first_error'] ?? 'Please correct the highlighted fields.';
            $fieldErrors   = $result['errors'] ?? [];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Create Atelier Account — L'Étoile Luxury Unisex Hair Salon &amp; Spa</title>
  <meta name="description" content="Register an exclusive guest profile with L'Étoile to manage your haircut, balayage, and Japanese head spa reservations.">
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
        <a href="login.php<?php echo $redirectTarget ? '?redirect=' . urlencode($redirectTarget) . '&reason=booking' : ''; ?>" class="btn btn-outline btn-sm">Sign In Instead</a>
      </div>
    </div>
  </header>

  <!-- REGISTRATION CONTAINER -->
  <main style="flex: 1; display: flex; align-items: center; justify-content: center; padding: var(--space-3xl) var(--space-md);">
    <div class="container" style="max-width: 620px; width: 100%;">

      <!-- Booking Notice -->
      <?php if ($isBookingReason): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid var(--color-champagne); padding: 16px 20px; margin-bottom: var(--space-lg); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 1.5rem;">📅</span>
            <div>
              <div style="font-weight: 700; font-size: 0.95rem; color: var(--color-charcoal);">Create Account to Complete Your Reservation</div>
              <p style="margin: 3px 0 0; font-size: 0.84rem; color: var(--color-charcoal-muted); line-height: 1.4;">
                Complete your complimentary registration to lock in your appointment. You will be redirected right back to confirm your booking.
              </p>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Error Notification -->
      <?php if ($registerError): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid #C95353; padding: 14px 18px; margin-bottom: var(--space-lg); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="color: #9E2A2B; font-weight: 600; font-size: 0.9rem;"><?php echo e($registerError); ?></div>
        </div>
      <?php endif; ?>

      <!-- Register Card -->
      <div class="form-card" style="box-shadow: 0 15px 35px rgba(37,35,33,0.06); padding: var(--space-2xl);">
        <div style="text-align: center; margin-bottom: var(--space-xl);">
          <span class="eyebrow eyebrow-no-line">Join Our Circle</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.1rem; color: var(--color-charcoal); margin: 6px 0 10px;">
            Create Your <em>Atelier Profile</em>
          </h1>
          <p style="font-size: 0.92rem; color: var(--color-charcoal-muted); margin: 0; line-height: 1.5;">
            Registering saves your private styling history, chemical formulations, and quiet service preferences across all salon rituals.
          </p>
        </div>

        <form action="register.php" method="POST" novalidate>
          <?php echo csrf_field(); ?>
          <?php if ($redirectTarget): ?>
            <input type="hidden" name="redirect" value="<?php echo e($redirectTarget); ?>">
          <?php endif; ?>

          <!-- First & Last Name -->
          <div class="form-row">
            <div class="form-group">
              <label for="first_name" class="form-label">First Name <span class="req">*</span></label>
              <input 
                type="text" 
                id="first_name" 
                name="first_name" 
                class="form-control <?php echo isset($fieldErrors['first_name']) ? 'is-invalid' : ''; ?>" 
                placeholder="e.g. Julianne" 
                value="<?php echo e($formData['first_name']); ?>" 
                required 
                autofocus
              >
              <?php if (isset($fieldErrors['first_name'])): ?>
                <div class="form-error" style="display:block;"><?php echo e($fieldErrors['first_name']); ?></div>
              <?php endif; ?>
            </div>

            <div class="form-group">
              <label for="last_name" class="form-label">Last Name <span class="req">*</span></label>
              <input 
                type="text" 
                id="last_name" 
                name="last_name" 
                class="form-control <?php echo isset($fieldErrors['last_name']) ? 'is-invalid' : ''; ?>" 
                placeholder="e.g. Sterling" 
                value="<?php echo e($formData['last_name']); ?>" 
                required
              >
              <?php if (isset($fieldErrors['last_name'])): ?>
                <div class="form-error" style="display:block;"><?php echo e($fieldErrors['last_name']); ?></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Email & Phone -->
          <div class="form-row">
            <div class="form-group">
              <label for="email" class="form-label">Email Address <span class="req">*</span></label>
              <input 
                type="email" 
                id="email" 
                name="email" 
                class="form-control <?php echo isset($fieldErrors['email']) ? 'is-invalid' : ''; ?>" 
                placeholder="julianne@example.com" 
                value="<?php echo e($formData['email']); ?>" 
                required
              >
              <?php if (isset($fieldErrors['email'])): ?>
                <div class="form-error" style="display:block;"><?php echo e($fieldErrors['email']); ?></div>
              <?php endif; ?>
            </div>

            <div class="form-group">
              <label for="phone" class="form-label">Phone Number <span class="req">*</span></label>
              <input 
                type="tel" 
                id="phone" 
                name="phone" 
                class="form-control <?php echo isset($fieldErrors['phone']) ? 'is-invalid' : ''; ?>" 
                placeholder="+1 (555) 000-0000" 
                value="<?php echo e($formData['phone']); ?>" 
                required
              >
              <?php if (isset($fieldErrors['phone'])): ?>
                <div class="form-error" style="display:block;"><?php echo e($fieldErrors['phone']); ?></div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Styling Preference -->
          <div class="form-group">
            <label for="gender_preference" class="form-label">Styling &amp; Treatment Focus</label>
            <select id="gender_preference" name="gender_preference" class="form-control">
              <option value="Unisex / Flexible" <?php echo $formData['gender_preference'] === 'Unisex / Flexible' ? 'selected' : ''; ?>>Unisex / Flexible</option>
              <option value="Women's Styling Focus" <?php echo $formData['gender_preference'] === "Women's Styling Focus" ? 'selected' : ''; ?>>Women's Styling Focus (Balayage, Cuts, Facials)</option>
              <option value="Men's Barbering Focus" <?php echo $formData['gender_preference'] === "Men's Barbering Focus" ? 'selected' : ''; ?>>Men's Barbering Focus (Scissor Tapers, Straight Razor)</option>
            </select>
          </div>

          <!-- Allergies / Preferences -->
          <div class="form-group">
            <label for="allergies_notes" class="form-label">Product Sensitivities &amp; Atelier Notes (Optional)</label>
            <textarea 
              id="allergies_notes" 
              name="allergies_notes" 
              class="form-control" 
              style="min-height: 80px;" 
              placeholder="e.g. Sensitive scalp, fragrance sensitivities, preference for quiet appointments..."
            ><?php echo e($formData['allergies_notes']); ?></textarea>
          </div>

          <!-- Passwords -->
          <div class="form-row">
            <div class="form-group">
              <label for="password" class="form-label">Password <span class="req">*</span> <small style="color:var(--color-charcoal-muted);">(Min. 8 chars)</small></label>
              <input 
                type="password" 
                id="password" 
                name="password" 
                class="form-control <?php echo isset($fieldErrors['password']) ? 'is-invalid' : ''; ?>" 
                placeholder="••••••••••••" 
                required 
                autocomplete="new-password"
              >
              <?php if (isset($fieldErrors['password'])): ?>
                <div class="form-error" style="display:block;"><?php echo e($fieldErrors['password']); ?></div>
              <?php endif; ?>
            </div>

            <div class="form-group">
              <label for="password_confirm" class="form-label">Confirm Password <span class="req">*</span></label>
              <input 
                type="password" 
                id="password_confirm" 
                name="password_confirm" 
                class="form-control <?php echo isset($fieldErrors['password_confirm']) ? 'is-invalid' : ''; ?>" 
                placeholder="••••••••••••" 
                required 
                autocomplete="new-password"
              >
              <?php if (isset($fieldErrors['password_confirm'])): ?>
                <div class="form-error" style="display:block;"><?php echo e($fieldErrors['password_confirm']); ?></div>
              <?php endif; ?>
            </div>
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: var(--space-sm);">
            <span>Establish Atelier Account</span>
            <span class="btn-icon" aria-hidden="true">&rarr;</span>
          </button>
        </form>

        <div style="text-align: center; margin-top: var(--space-xl); padding-top: var(--space-lg); border-top: 1px solid var(--color-charcoal-border);">
          <p style="font-size: 0.9rem; color: var(--color-charcoal-muted); margin-bottom: 8px;">
            Already have an atelier guest profile?
          </p>
          <a href="login.php<?php echo $redirectTarget ? '?redirect=' . urlencode($redirectTarget) . '&reason=booking' : ''; ?>" style="color: var(--color-champagne-dark); font-weight: 600; font-size: 0.95rem; text-decoration: none;">
            Sign in to your account &rarr;
          </a>
        </div>
      </div>

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
