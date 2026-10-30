<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Initial Administrator Secure Setup Utility
 * 
 * Configures the initial administrator account securely.
 * Automatically locks down once an administrator exists.
 * Does NOT use any hardcoded passwords.
 */

require_once __DIR__ . '/includes/bootstrap.php';

$setupError = null;
$setupSuccess = null;
$adminExists = false;

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminCount = (int)$stmt->fetchColumn();
    $adminExists = ($adminCount > 0);
} catch (Exception $e) {
    $setupError = "Database connection error: " . $e->getMessage();
}

// Handle Form Submission when no admin exists
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$adminExists) {
    $token = $_POST['csrf_token'] ?? null;
    if (!Csrf::verify($token)) {
        $setupError = 'Security token expired. Please refresh and try again.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $email    = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $confirm  = (string)($_POST['password_confirm'] ?? '');

        $validator = Validator::make($_POST)
            ->required('username', 'Username is required.')
            ->lengthBetween('username', 4, 30, 'Username must be between 4 and 30 characters.')
            ->required('email', 'Email is required.')
            ->email('email')
            ->required('password', 'Password is required.')
            ->lengthBetween('password', 10, 72, 'Password must be at least 10 characters.');

        if ($validator->fails()) {
            $setupError = $validator->firstError();
        } elseif ($password !== $confirm) {
            $setupError = 'Password confirmation does not match.';
        } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $setupError = 'Password must include at least one uppercase letter and one numeric digit.';
        } else {
            try {
                // Re-verify that no admin was created concurrently
                $checkStmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
                if ((int)$checkStmt->fetchColumn() > 0) {
                    $setupError = 'An administrator account has already been initialized.';
                    $adminExists = true;
                } else {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $ins = $pdo->prepare("
                        INSERT INTO users (username, email, password_hash, role, is_active, created_at)
                        VALUES (:u, :e, :h, 'admin', 1, NOW())
                    ");
                    $ins->execute([
                        'u' => $username,
                        'e' => $email,
                        'h' => $hash
                    ]);

                    AppLogger::info("Initial Administrator account created: {$username} ({$email})");
                    Response::redirect('login.php', "Initial Administrator '{$username}' successfully initialized! You may now sign in.", 'success');
                }
            } catch (Exception $e) {
                AppLogger::error("Admin setup error: " . $e->getMessage());
                $setupError = 'An error occurred while creating the administrator account: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Administrator Setup — L'Étoile Luxury Unisex Hair Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css?v=3.5">
  <link rel="stylesheet" href="assets/css/responsive.css?v=3.5">
</head>
<body style="background-color: var(--color-ivory); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: var(--space-xl);">

  <div class="container" style="max-width: 520px; width: 100%;">
    <div class="form-card" style="box-shadow: 0 15px 35px rgba(37,35,33,0.08); padding: var(--space-2xl);">
      
      <div style="text-align: center; margin-bottom: var(--space-xl);">
        <span class="eyebrow eyebrow-no-line">Security Utility</span>
        <h1 style="font-family: var(--font-serif); font-size: 2.1rem; color: var(--color-charcoal); margin: 6px 0 10px;">
          Administrator <em>Initialization</em>
        </h1>
        <p style="font-size: 0.9rem; color: var(--color-charcoal-muted); margin: 0; line-height: 1.5;">
          This secure wizard establishes the primary salon administrator. No default or public hardcoded passwords are used.
        </p>
      </div>

      <?php if ($adminExists): ?>
        <!-- LOCKED STATE: Admin already exists -->
        <div style="background-color: var(--color-ivory); border: 1px solid var(--color-champagne-border); border-radius: var(--radius-xs); padding: var(--space-lg); text-align: center; margin-bottom: var(--space-lg);">
          <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--color-champagne-subtle); color: var(--color-champagne-dark); display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect><path d="M7 11V7a5 5 0 0 1 10 0v4"></path></svg>
          </div>
          <h4 style="font-family: var(--font-serif); font-size: 1.35rem; margin-bottom: 6px;">Setup Locked for Security</h4>
          <p style="font-size: 0.88rem; color: var(--color-charcoal-muted); margin: 0 0 16px; line-height: 1.5;">
            An active administrator account has already been initialized in <code>letoile_salon_db</code>. Direct administrative creation via this script is automatically disabled.
          </p>
          <a href="login.php" class="btn btn-primary btn-md">Go to Sign In Portal &rarr;</a>
        </div>
      <?php else: ?>
        <!-- SETUP FORM: When no admin exists -->
        <?php if ($setupError): ?>
          <div style="background-color: #FFFFFF; border-left: 4px solid #C95353; padding: 12px 16px; margin-bottom: var(--space-lg); border-radius: var(--radius-xs);">
            <div style="color: #9E2A2B; font-weight: 600; font-size: 0.88rem;"><?php echo e($setupError); ?></div>
          </div>
        <?php endif; ?>

        <form action="setup-admin.php" method="POST" novalidate>
          <?php echo csrf_field(); ?>

          <div class="form-group">
            <label for="username" class="form-label">Administrator Username <span class="req">*</span></label>
            <input type="text" id="username" name="username" class="form-control" placeholder="e.g. director_admin" required autofocus>
          </div>

          <div class="form-group">
            <label for="email" class="form-label">Administrator Email <span class="req">*</span></label>
            <input type="email" id="email" name="email" class="form-control" placeholder="admin@letoile-salon-placeholder.com" required>
          </div>

          <div class="form-group">
            <label for="password" class="form-label">Master Password <span class="req">*</span> <small style="color:var(--color-charcoal-muted);">(Min. 10 chars, 1 uppercase, 1 number)</small></label>
            <input type="password" id="password" name="password" class="form-control" placeholder="••••••••••••" required autocomplete="new-password">
          </div>

          <div class="form-group">
            <label for="password_confirm" class="form-label">Confirm Password <span class="req">*</span></label>
            <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="••••••••••••" required autocomplete="new-password">
          </div>

          <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: var(--space-sm);">
            <span>Initialize Administrator Account</span>
            <span class="btn-icon">&rarr;</span>
          </button>
        </form>
      <?php endif; ?>

      <div style="text-align: center; margin-top: var(--space-lg); padding-top: var(--space-md); border-top: 1px solid var(--color-charcoal-border);">
        <a href="index.html" style="font-size: 0.85rem; color: var(--color-charcoal-muted); text-decoration: none;">&larr; Return to Sanctuary Homepage</a>
      </div>

    </div>
  </div>

</body>
</html>
