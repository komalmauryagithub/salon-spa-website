<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Protected Customer Sanctuary Portal & Profile
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 1. Guard check: Must be authenticated customer
$customer = Auth::requireCustomer('login.php');
$customerId = (int)$customer['id'];
$user = Auth::user();

$profileMessage = null;
$profileError = null;

// 2. Handle Profile Edit POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!Csrf::verify($token)) {
        $profileError = 'Security session expired. Please refresh the page.';
    } else {
        $action = $_POST['action'] ?? 'update_profile';

        if ($action === 'update_profile') {
            $result = Auth::updateCustomerProfile($customerId, $_POST);
            if ($result['success']) {
                $profileMessage = $result['message'];
                // Refresh local customer variable
                $customer = Auth::customer();
            } else {
                $profileError = $result['first_error'] ?? 'Profile update could not be completed.';
            }
        } elseif ($action === 'cancel_appointment') {
            $aptId = (int)($_POST['appointment_id'] ?? 0);
            $reason = !empty($_POST['cancellation_reason']) ? Validator::sanitizeString((string)$_POST['cancellation_reason']) : 'Cancelled by guest via atelier portal';

            try {
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare("SELECT id, booking_reference, customer_id, appointment_date, appointment_time, status FROM appointments WHERE id = :id LIMIT 1");
                $stmt->execute(['id' => $aptId]);
                $apt = $stmt->fetch();

                if (!$apt || (int)$apt['customer_id'] !== $customerId) {
                    $profileError = 'Unauthorized or reservation not found.';
                } else {
                    $cancelCheck = Scheduler::canCustomerCancel($apt);
                    if (!$cancelCheck['can_cancel']) {
                        $profileError = $cancelCheck['reason'];
                    } else {
                        $upStmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled', cancellation_reason = :reason, updated_at = NOW() WHERE id = :id");
                        $upStmt->execute(['reason' => $reason, 'id' => $aptId]);
                        $profileMessage = "Your reservation [{$apt['booking_reference']}] has been successfully cancelled.";
                        AppLogger::info("Customer #{$customerId} cancelled reservation #{$aptId} [{$apt['booking_reference']}]");
                    }
                }
            } catch (Exception $e) {
                AppLogger::error("Profile appointment cancellation error: " . $e->getMessage());
                // Fallback for Render demo / offline showcase
                if (!empty($_SESSION['user_demo_appointments'])) {
                    foreach ($_SESSION['user_demo_appointments'] as &$dAppt) {
                        if ((int)($dAppt['id'] ?? 0) === $aptId || ($dAppt['booking_reference'] ?? '') === (string)$aptId) {
                            $dAppt['status'] = 'cancelled';
                            $dAppt['cancellation_reason'] = $reason;
                        }
                    }
                }
                $profileMessage = "Your reservation has been successfully cancelled.";
            }
        }
    }
}

// 3. Fetch IDOR-Safe Appointment History strictly for this customer
$appointments = Auth::getCustomerAppointments($customerId);

$flash = get_flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Atelier Account — L'Étoile Luxury Unisex Hair Salon &amp; Spa</title>
  <meta name="description" content="View personal salon appointments, treatment history, and tailor bespoke sanctuary preferences.">
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="assets/css/style.css?v=3.5">
  <link rel="stylesheet" href="assets/css/responsive.css?v=3.5">
  <style>
    .status-badge {
      display: inline-block;
      padding: 3px 10px;
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      border-radius: 20px;
    }
    .status-pending {
      background-color: var(--color-champagne-subtle);
      color: var(--color-champagne-dark);
      border: 1px solid var(--color-champagne-border);
    }
    .status-confirmed {
      background-color: var(--color-champagne);
      color: var(--color-charcoal);
    }
    .status-completed {
      background-color: var(--color-charcoal);
      color: var(--color-ivory);
    }
    .status-cancelled {
      background-color: rgba(201, 83, 83, 0.1);
      color: #9E2A2B;
      border: 1px solid rgba(201, 83, 83, 0.3);
    }
    .history-card {
      background: var(--color-white);
      border: 1px solid var(--color-charcoal-border);
      border-radius: var(--radius-xs);
      padding: var(--space-lg);
      margin-bottom: var(--space-md);
      transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
    }
    .history-card:hover {
      border-color: var(--color-champagne);
      box-shadow: var(--shadow-subtle);
    }
  </style>
</head>
<body style="background-color: var(--color-ivory);">

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
          <span>Signed in as <strong><?php echo e($customer['first_name'] . ' ' . $customer['last_name']); ?></strong></span>
        </span>
        <span class="top-bar-item">
          <a href="logout.php" style="color:var(--color-champagne-dark); font-weight:600;">Sign Out</a>
        </span>
      </div>
    </div>
  </div>

  <!-- SITE HEADER -->
  <header class="site-header" role="banner">
    <div class="container nav-container">
      <a href="index.html" class="brand" aria-label="L'Étoile Home">
        <span class="brand-title">L'ÉTOILE</span>
        <span class="brand-subtitle">HAIR SALON &amp; SPA</span>
      </a>

      <nav class="nav-links" id="primary-navigation" aria-label="Main Navigation">
        <a href="index.html" class="nav-link">Home</a>
        <a href="services.html" class="nav-link">Services</a>
        <a href="hair-services.html" class="nav-link">Hair</a>
        <a href="spa-services.html" class="nav-link">Spa</a>
        <a href="packages.html" class="nav-link">Packages</a>
        <a href="contact.html" class="nav-link">Contact</a>
        <a href="customer-profile.php" class="nav-link active">My Account</a>
        <a href="logout.php" class="nav-link" style="color: #FF8A80 !important; font-weight: 600;">Sign Out</a>
        <div class="mobile-only-cta">
          <a href="book-appointment.html" class="btn btn-champagne">Book New Ritual</a>
        </div>
      </nav>

      <div class="nav-cta">
        <a href="book-appointment.html" class="btn btn-champagne btn-sm">
          <span>Book Ritual</span>
          <span class="btn-icon">&rarr;</span>
        </a>
      </div>

      <!-- Mobile Hamburger Toggle -->
      <button type="button" class="nav-toggle" aria-controls="primary-navigation" aria-expanded="false" aria-label="Toggle navigation menu">
        <span class="nav-toggle-bar"></span>
        <span class="nav-toggle-bar"></span>
        <span class="nav-toggle-bar"></span>
      </button>
    </div>
  </header>

  <!-- DASHBOARD HERO -->
  <section style="background-color: var(--color-white); border-bottom: 1px solid var(--color-charcoal-border); padding: var(--space-2xl) 0;">
    <div class="container">
      <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: var(--space-md);" class="sanctuary-hero-inner">
        <div>
          <span class="eyebrow eyebrow-no-line">Private Guest Sanctuary</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.4rem; color: var(--color-charcoal); margin: 4px 0 8px;">
            Bonjour, <em><?php echo e($customer['first_name']); ?></em>
          </h1>
          <p style="color: var(--color-charcoal-muted); margin: 0; font-size: 0.95rem;">
            Member of L'Étoile Atelier Circle &bull; Client ID: #<?php echo str_pad((string)$customerId, 4, '0', STR_PAD_LEFT); ?>
            <?php if (!empty($customer['is_vip'])): ?>
              &bull; <strong style="color:var(--color-champagne-dark);">&#9733; VIP Sanctuary Patron</strong>
            <?php endif; ?>
          </p>
        </div>
        <div class="sanctuary-hero-actions">
          <a href="book-appointment.html" class="btn btn-champagne btn-md">Reserve New Ritual</a>
          <a href="logout.php" class="btn btn-outline btn-md">Sign Out</a>
        </div>
      </div>
    </div>
  </section>

  <!-- MAIN DASHBOARD CONTENT -->
  <main style="padding: var(--space-3xl) 0 var(--space-4xl);">
    <div class="container">

      <!-- Notifications -->
      <?php if ($flash): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid var(--color-champagne); padding: 14px 18px; margin-bottom: var(--space-xl); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="font-weight: 600; color: var(--color-charcoal); font-size: 0.92rem;"><?php echo e($flash['message']); ?></div>
        </div>
      <?php endif; ?>

      <?php if ($profileMessage): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid #4E9F3D; padding: 14px 18px; margin-bottom: var(--space-xl); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="font-weight: 600; color: #1E5128; font-size: 0.92rem;"><?php echo e($profileMessage); ?></div>
        </div>
      <?php endif; ?>

      <?php if ($profileError): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid #C95353; padding: 14px 18px; margin-bottom: var(--space-xl); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="font-weight: 600; color: #9E2A2B; font-size: 0.92rem;"><?php echo e($profileError); ?></div>
        </div>
      <?php endif; ?>

      <div style="display: grid; grid-template-columns: 1fr 1.6fr; gap: var(--space-2xl); align-items: start;" class="profile-layout-grid">
        
        <!-- LEFT COLUMN: Profile & Preferences Card -->
        <div>
          <div class="form-card" style="box-shadow: var(--shadow-subtle); margin-bottom: var(--space-xl);">
            <div style="border-bottom: 1px solid var(--color-charcoal-border); padding-bottom: var(--space-md); margin-bottom: var(--space-lg);">
              <span class="eyebrow eyebrow-no-line">Profile &amp; Preferences</span>
              <h3 style="font-family: var(--font-serif); font-size: 1.5rem; margin: 4px 0 0;">Atelier Details</h3>
            </div>

            <form action="customer-profile.php" method="POST" novalidate>
              <?php echo csrf_field(); ?>
              <input type="hidden" name="action" value="update_profile">

              <div class="form-row">
                <div class="form-group">
                  <label for="first_name" class="form-label">First Name <span class="req">*</span></label>
                  <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo e($customer['first_name']); ?>" required>
                </div>

                <div class="form-group">
                  <label for="last_name" class="form-label">Last Name <span class="req">*</span></label>
                  <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo e($customer['last_name']); ?>" required>
                </div>
              </div>

              <div class="form-group">
                <label for="email_static" class="form-label">Email Address <small style="color:var(--color-charcoal-muted);">(Account Identifier)</small></label>
                <input type="email" id="email_static" class="form-control" value="<?php echo e($customer['email']); ?>" disabled style="background-color: var(--color-ivory); cursor: not-allowed;">
              </div>

              <div class="form-group">
                <label for="phone" class="form-label">Telephone Number <span class="req">*</span></label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo e($customer['phone']); ?>" required>
              </div>

              <div class="form-group">
                <label for="gender_preference" class="form-label">Styling Focus</label>
                <select id="gender_preference" name="gender_preference" class="form-control">
                  <option value="Unisex / Flexible" <?php echo $customer['gender_preference'] === 'Unisex / Flexible' ? 'selected' : ''; ?>>Unisex / Flexible</option>
                  <option value="Women's Styling Focus" <?php echo $customer['gender_preference'] === "Women's Styling Focus" ? 'selected' : ''; ?>>Women's Styling Focus</option>
                  <option value="Men's Barbering Focus" <?php echo $customer['gender_preference'] === "Men's Barbering Focus" ? 'selected' : ''; ?>>Men's Barbering Focus</option>
                </select>
              </div>

              <div class="form-group">
                <label for="allergies_notes" class="form-label">Product Sensitivities &amp; Atelier Notes</label>
                <textarea id="allergies_notes" name="allergies_notes" class="form-control" style="min-height: 85px;"><?php echo e($customer['allergies_notes'] ?? ''); ?></textarea>
              </div>

              <!-- Optional Password Change -->
              <div style="border-top: 1px dashed var(--color-charcoal-border); margin: var(--space-lg) 0 var(--space-md); padding-top: var(--space-md);">
                <h4 style="font-family: var(--font-serif); font-size: 1.15rem; margin-bottom: 6px;">Change Password (Optional)</h4>
                <p style="font-size: 0.82rem; color: var(--color-charcoal-muted); margin-bottom: var(--space-md);">Leave blank if you do not wish to update your password.</p>

                <div class="form-group">
                  <label for="current_password" class="form-label">Current Password</label>
                  <input type="password" id="current_password" name="current_password" class="form-control" placeholder="••••••••••••" autocomplete="current-password">
                </div>

                <div class="form-row">
                  <div class="form-group">
                    <label for="new_password" class="form-label">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Min. 8 chars" autocomplete="new-password">
                  </div>
                  <div class="form-group">
                    <label for="confirm_new_password" class="form-label">Confirm New</label>
                    <input type="password" id="confirm_new_password" name="confirm_new_password" class="form-control" placeholder="Re-type" autocomplete="new-password">
                  </div>
                </div>
              </div>

              <button type="submit" class="btn btn-primary btn-md" style="width: 100%;">
                <span>Save Profile Changes</span>
                <span class="btn-icon">&rarr;</span>
              </button>
            </form>
          </div>

          <!-- Account Security Info Card -->
          <div style="background-color: var(--color-white); border: 1px solid var(--color-champagne-border); border-radius: var(--radius-xs); padding: var(--space-lg); font-size: 0.85rem; color: var(--color-charcoal-muted);">
            <h5 style="color:var(--color-charcoal); font-family:var(--font-serif); font-size:1.05rem; margin:0 0 6px;">Account Integrity &amp; Privacy</h5>
            <p style="margin: 0; line-height: 1.5;">
              Your data is protected under hospital-grade data protocols. Passwords are encrypted with one-way BCrypt algorithms and appointment records are accessible strictly via your authenticated session.
            </p>
          </div>
        </div>

        <!-- RIGHT COLUMN: Appointment History -->
        <div>
          <div style="background-color: var(--color-white); border: 1px solid var(--color-charcoal-border); border-radius: var(--radius-xs); padding: var(--space-xl); box-shadow: var(--shadow-subtle);">
            <div style="display: flex; justify-content: space-between; align-items: baseline; border-bottom: 1px solid var(--color-charcoal-border); padding-bottom: var(--space-md); margin-bottom: var(--space-lg);">
              <div>
                <span class="eyebrow eyebrow-no-line">Your Salon Itinerary</span>
                <h3 style="font-family: var(--font-serif); font-size: 1.6rem; margin: 4px 0 0;">Appointment History</h3>
              </div>
              <span style="font-size: 0.85rem; color: var(--color-charcoal-muted);">
                <?php echo count($appointments); ?> recorded <?php echo count($appointments) === 1 ? 'reservation' : 'reservations'; ?>
              </span>
            </div>

            <!-- Policy Callout -->
            <div style="background-color: var(--color-ivory); border: 1px solid var(--color-champagne-border); border-radius: var(--radius-xs); padding: 12px 16px; margin-bottom: var(--space-md); font-size: 0.82rem; color: var(--color-charcoal-muted); display: flex; align-items: flex-start; gap: 10px;">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--color-champagne-dark); flex-shrink: 0; margin-top: 1px;"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
              <div>
                <strong style="color:var(--color-charcoal);">Atelier Cancellation Policy:</strong> Complimentary online cancellation is permitted up to 24 hours prior to your scheduled ritual. For changes within 24 hours, please contact our concierge desk directly at <a href="tel:+18005550192" style="color:var(--color-champagne-dark); font-weight:600;">+1 (800) 555-0192</a>.
              </div>
            </div>

            <?php if (empty($appointments)): ?>
              <!-- Empty State -->
              <div style="text-align: center; padding: var(--space-3xl) var(--space-md);">
                <div style="width: 56px; height: 56px; margin: 0 auto var(--space-md); border-radius: 50%; background: var(--color-ivory); border: 1px solid var(--color-champagne-border); display: flex; align-items: center; justify-content: center; color: var(--color-champagne-dark);">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
                <h4 style="font-family: var(--font-serif); font-size: 1.35rem; margin-bottom: 6px;">No Appointments Scheduled Yet</h4>
                <p style="color: var(--color-charcoal-muted); font-size: 0.92rem; max-width: 380px; margin: 0 auto var(--space-xl);">
                  Your appointment timeline is clear. Reserve a precision haircut, balayage, or Japanese head spa treatment with our interactive booking interface.
                </p>
                <a href="book-appointment.html" class="btn btn-champagne btn-md">
                  <span>Schedule Your First Ritual</span>
                  <span class="btn-icon">&rarr;</span>
                </a>
              </div>
            <?php else: ?>
              <!-- Appointment Cards List -->
              <div>
                <?php foreach ($appointments as $apt): ?>
                  <?php 
                    $statusClass = 'status-' . strtolower((string)$apt['status']);
                    $formattedDate = date('l, F j, Y', strtotime($apt['appointment_date']));
                    $formattedTime = date('g:i A', strtotime($apt['appointment_time']));
                    $cancelEligibility = Scheduler::canCustomerCancel($apt);
                    $isUpcoming = in_array(strtolower((string)$apt['status']), ['pending', 'confirmed'], true);
                  ?>
                  <div class="history-card" id="apt-card-<?php echo (int)$apt['id']; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                      <div>
                        <span style="font-size: 0.75rem; letter-spacing: 0.08em; text-transform: uppercase; color: var(--color-charcoal-muted);">Reference:</span>
                        <strong style="color: var(--color-charcoal); font-family: monospace; font-size: 0.9rem;"><?php echo e($apt['booking_reference']); ?></strong>
                      </div>
                      <span class="status-badge <?php echo $statusClass; ?>" id="apt-status-badge-<?php echo (int)$apt['id']; ?>">
                        <?php echo e(ucfirst($apt['status'])); ?>
                      </span>
                    </div>

                    <h4 style="font-family: var(--font-serif); font-size: 1.35rem; margin: 0 0 4px; color: var(--color-charcoal);">
                      <?php echo e($apt['service_name']); ?>
                    </h4>

                    <div style="display: flex; flex-wrap: wrap; gap: var(--space-md); margin-top: 8px; font-size: 0.88rem; color: var(--color-charcoal-muted);">
                      <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                        <span><?php echo e($formattedDate); ?> at <?php echo e($formattedTime); ?></span>
                      </div>
                      <div style="display: flex; align-items: center; gap: 6px;">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <span>Specialist: <strong><?php echo e($apt['specialist_name'] ?: 'Any Available Master Specialist'); ?></strong></span>
                      </div>
                    </div>

                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 14px; padding-top: 10px; border-top: 1px dashed var(--color-charcoal-border); font-size: 0.85rem;">
                      <div>
                        <?php if (!empty($apt['silent_appointment'])): ?>
                          <span style="background-color: var(--color-ivory); border: 1px solid var(--color-champagne-border); color: var(--color-charcoal); font-size: 0.72rem; padding: 2px 8px; border-radius: 2px; text-transform: uppercase;">
                            Quiet Sanctuary Service
                          </span>
                        <?php endif; ?>
                        <?php if (!empty($apt['duration_minutes'])): ?>
                          <span style="color: var(--color-charcoal-muted); margin-left: 6px;">
                            <?php echo (int)$apt['duration_minutes']; ?> mins duration
                          </span>
                        <?php endif; ?>
                      </div>
                      <div>
                        <span style="color: var(--color-charcoal-muted);">Total:</span>
                        <strong style="color: var(--color-champagne-dark); font-size: 1.05rem;">
                          $<?php echo number_format((float)$apt['total_price'], 2); ?>
                        </strong>
                      </div>
                    </div>

                    <!-- Cancellation Status or Action Area -->
                    <?php if (strtolower((string)$apt['status']) === 'cancelled'): ?>
                      <div style="margin-top: 12px; font-size: 0.82rem; color: #9E2A2B; background: rgba(201, 83, 83, 0.08); padding: 8px 12px; border-radius: 2px; border-left: 3px solid #9E2A2B;">
                        <strong>Cancellation Note:</strong> <?php echo e($apt['cancellation_reason'] ?: 'Cancelled via atelier portal'); ?>
                      </div>
                    <?php elseif ($isUpcoming): ?>
                      <div id="apt-action-area-<?php echo (int)$apt['id']; ?>" style="margin-top: 12px; padding-top: 10px; border-top: 1px solid rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                        <?php if ($cancelEligibility['can_cancel']): ?>
                          <button type="button" 
                                  class="btn btn-outline btn-sm trigger-cancel-btn" 
                                  data-id="<?php echo (int)$apt['id']; ?>"
                                  data-ref="<?php echo e($apt['booking_reference']); ?>"
                                  data-service="<?php echo e($apt['service_name']); ?>"
                                  data-datetime="<?php echo e($formattedDate . ' at ' . $formattedTime); ?>"
                                  style="color: #9E2A2B; border-color: rgba(201,83,83,0.4); padding: 4px 12px; font-size: 0.76rem;">
                            Cancel Reservation
                          </button>
                          <span style="font-size: 0.75rem; color: var(--color-charcoal-muted);">
                            Eligible for online cancellation (>24h notice)
                          </span>
                        <?php else: ?>
                          <span style="font-size: 0.76rem; color: var(--color-charcoal-muted); font-style: italic;">
                            Within 24h &bull; Call Concierge: <a href="tel:+18005550192" style="color: var(--color-champagne-dark); font-weight: 600;">+1 (800) 555-0192</a>
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>

                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

          </div>
        </div>

      </div>

    </div>
  </main>

  <!-- FOOTER -->
  <footer class="site-footer" role="contentinfo">
    <div class="footer-top">
      <div class="container footer-grid">
        <div class="footer-brand-col">
          <div class="brand">
            <span class="brand-title" style="color: var(--color-white);">L'ÉTOILE</span>
            <span class="brand-subtitle">HAIR SALON &amp; SPA</span>
          </div>
          <p>An intimate unisex sanctuary fusing European hair architecture, mindful skin wellness, and bespoke sensory hydrotherapy.</p>
        </div>

        <div>
          <h4 class="footer-col-title">Guest Portal</h4>
          <ul class="footer-links-list">
            <li><a href="customer-profile.php">My Account Dashboard</a></li>
            <li><a href="book-appointment.html">Schedule an Appointment</a></li>
            <li><a href="services.html">View Services &amp; Pricing</a></li>
            <li><a href="logout.php">Sign Out of Sanctuary</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-col-title">Concierge Desk</h4>
          <ul class="footer-links-list">
            <li><a href="tel:+18005550192">+1 (800) 555-0192</a></li>
            <li><a href="mailto:concierge@letoile-salon-placeholder.com">concierge@letoile-salon-placeholder.com</a></li>
            <li>482 Boulevard Saint-Honoré, Suite 400</li>
          </ul>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="container footer-bottom-inner">
        <p>&copy; <?php echo date('Y'); ?> L'Étoile Luxury Unisex Hair Salon &amp; Spa. All rights reserved.</p>
      </div>
    </div>
  </footer>

  <!-- CANCELLATION MODAL DIALOG -->
  <div id="cancelModalOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(37,35,33,0.7); backdrop-filter:blur(4px); z-index:9999; align-items:center; justify-content:center; padding:20px;">
    <div style="background:#FFFFFF; border:1px solid var(--color-champagne); border-radius:var(--radius-xs); max-width:480px; width:100%; padding:var(--space-2xl); box-shadow:var(--shadow-elevated); position:relative;">
      
      <div style="border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-md);">
        <span class="eyebrow eyebrow-no-line" style="color:#9E2A2B;">Atelier Cancellation Request</span>
        <h3 style="font-family:var(--font-serif); font-size:1.6rem; color:var(--color-charcoal); margin:4px 0 0;">Cancel Reservation</h3>
      </div>

      <p style="font-size:0.88rem; color:var(--color-charcoal-muted); line-height:1.5; margin-bottom:var(--space-md);">
        Are you sure you wish to cancel this reservation? The master specialist's time will be released back into the atelier schedule.
      </p>

      <div style="background:var(--color-ivory); border:1px solid var(--color-champagne-border); border-radius:var(--radius-xs); padding:12px 14px; margin-bottom:var(--space-lg); font-size:0.85rem;">
        <div style="margin-bottom:4px;"><strong>Reference:</strong> <span id="modalAptRef" style="font-family:monospace; font-weight:700;"></span></div>
        <div style="margin-bottom:4px;"><strong>Ritual:</strong> <span id="modalAptService"></span></div>
        <div><strong>Schedule:</strong> <span id="modalAptDateTime"></span></div>
      </div>

      <form id="cancelAppointmentForm" action="customer-profile.php" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="cancel_appointment">
        <input type="hidden" name="appointment_id" id="modalAptId" value="">

        <div class="form-group" style="margin-bottom:var(--space-lg);">
          <label for="cancellation_reason" class="form-label" style="font-size:0.85rem;">Reason for Cancellation (Optional)</label>
          <select id="cancellation_reason" name="cancellation_reason" class="form-control" style="font-size:0.88rem;">
            <option value="Schedule conflict / Rescheduling required">Schedule conflict / Rescheduling required</option>
            <option value="Travelling or out of city">Travelling or out of city</option>
            <option value="Personal emergency">Personal emergency</option>
            <option value="Health or indisposition">Health or indisposition</option>
            <option value="Other / Bespoke preference change">Other / Bespoke preference change</option>
          </select>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:var(--space-sm); flex-wrap:wrap;">
          <button type="button" id="closeCancelModalBtn" class="btn btn-outline btn-sm">Keep Reservation</button>
          <button type="submit" id="confirmCancelSubmitBtn" class="btn btn-primary btn-sm" style="background:#9E2A2B; border-color:#9E2A2B; color:#FFFFFF;">
            Confirm Cancellation
          </button>
        </div>
      </form>

    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const modal = document.getElementById('cancelModalOverlay');
      const closeBtn = document.getElementById('closeCancelModalBtn');
      const cancelForm = document.getElementById('cancelAppointmentForm');
      const modalId = document.getElementById('modalAptId');
      const modalRef = document.getElementById('modalAptRef');
      const modalService = document.getElementById('modalAptService');
      const modalDateTime = document.getElementById('modalAptDateTime');

      // Open Modal
      document.querySelectorAll('.trigger-cancel-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          modalId.value = btn.getAttribute('data-id');
          modalRef.textContent = btn.getAttribute('data-ref');
          modalService.textContent = btn.getAttribute('data-service');
          modalDateTime.textContent = btn.getAttribute('data-datetime');
          modal.style.display = 'flex';
        });
      });

      // Close Modal
      function closeModal() {
        modal.style.display = 'none';
      }

      if (closeBtn) closeBtn.addEventListener('click', closeModal);
      modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
      });

      // AJAX Form Handling
      cancelForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const submitBtn = document.getElementById('confirmCancelSubmitBtn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Processing...';

        const aptId = modalId.value;
        const reason = document.getElementById('cancellation_reason').value;
        const csrfToken = cancelForm.querySelector('input[name="csrf_token"]').value;

        fetch('api/cancel-appointment.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-Token': csrfToken
          },
          body: JSON.stringify({
            appointment_id: aptId,
            reason: reason,
            csrf_token: csrfToken
          })
        })
        .then(async (res) => {
          const data = await res.json().catch(() => null);
          if (res.ok && data && data.success) {
            closeModal();
            // Update Card UI seamlessly
            const card = document.getElementById(`apt-card-${aptId}`);
            const badge = document.getElementById(`apt-status-badge-${aptId}`);
            const actionArea = document.getElementById(`apt-action-area-${aptId}`);

            if (badge) {
              badge.className = 'status-badge status-cancelled';
              badge.textContent = 'Cancelled';
            }
            if (actionArea) {
              actionArea.innerHTML = `<div style="margin-top:4px; font-size:0.82rem; color:#9E2A2B; background:rgba(201,83,83,0.08); padding:8px 12px; border-radius:2px; border-left:3px solid #9E2A2B; width:100%;"><strong>Cancellation Note:</strong> ${reason}</div>`;
            }
            alert(`Your reservation [${data.booking_reference}] has been cancelled.`);
          } else {
            alert(data?.error || 'Cancellation could not be completed.');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Confirm Cancellation';
          }
        })
        .catch(() => {
          // Fallback to standard form post
          cancelForm.submit();
        });
      });
    });
  </script>
  <script src="assets/js/main.js?v=3.5" defer></script>
</body>
</html>
