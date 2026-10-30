<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Salon Business Settings, Contact Details & Operating Hours Console
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 1. Guard check: Administrator & Manager role required
Auth::requireRole(['admin', 'manager'], 'admin-login.php');

$currentUser = Auth::user();
$activeAdminPage = 'settings';
$statusMessage = null;
$statusError   = null;

try {
    // 2. Handle POST Settings Submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? null;
        if (!Csrf::verify($token)) {
            $statusError = "Security token validation failed. Please refresh the page.";
        } else {
            $updatedSettings = [
                // General Brand Identity
                'salon_name'                      => trim((string)($_POST['salon_name'] ?? "L'Étoile Hair Salon & Spa")),
                'salon_tagline'                   => trim((string)($_POST['salon_tagline'] ?? 'Haute Coiffure & Holistic Spa Sanctuary')),
                
                // Contact Details
                'contact_address'                 => trim((string)($_POST['contact_address'] ?? '')),
                'contact_phone'                   => trim((string)($_POST['contact_phone'] ?? '')),
                'contact_email'                   => trim((string)($_POST['contact_email'] ?? '')),
                'contact_whatsapp'                => trim((string)($_POST['contact_whatsapp'] ?? '')),
                
                // Hours of Operation
                'hours_weekdays'                  => trim((string)($_POST['hours_weekdays'] ?? '09:00 AM – 08:30 PM')),
                'hours_weekends'                  => trim((string)($_POST['hours_weekends'] ?? '09:30 AM – 07:00 PM')),
                'hours_monday'                    => trim((string)($_POST['hours_monday'] ?? 'Closed for Masterclasses & Private Education')),
                
                // Booking & Scheduling Policies
                'currency_symbol'                 => trim((string)($_POST['currency_symbol'] ?? '$')),
                'appointment_deposit_percentage'  => (string)max(0, min(100, (int)($_POST['appointment_deposit_percentage'] ?? 20))),
                'cancellation_notice_hours'       => (string)max(1, min(168, (int)($_POST['cancellation_notice_hours'] ?? 24))),
                'booking_advance_days_max'        => (string)max(7, min(365, (int)($_POST['booking_advance_days_max'] ?? 60)))
            ];

            // Validation
            if (empty($updatedSettings['salon_name'])) {
                $statusError = "Salon brand name cannot be empty.";
            } elseif (!empty($updatedSettings['contact_email']) && !filter_var($updatedSettings['contact_email'], FILTER_VALIDATE_EMAIL)) {
                $statusError = "Please specify a valid contact email address.";
            } else {
                if (Settings::setMultiple($updatedSettings)) {
                    $statusMessage = "Salon business parameters, operating hours, and booking policies updated successfully.";
                    AppLogger::info("Admin #{$currentUser['id']} updated salon business settings");
                } else {
                    $statusError = "An error occurred while saving settings to the database.";
                }
            }
        }
    }

    // 3. Load Active Settings Dictionary
    $settings = Settings::all();

} catch (Exception $e) {
    AppLogger::error("Settings error: " . $e->getMessage());
    $settings = [];
    $statusError = "Could not load settings from database.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Salon Settings &amp; Policies — L'ÉTOILE Luxury Hair Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="assets/css/style.css?v=3.5">
  <link rel="stylesheet" href="assets/css/responsive.css?v=3.5">
  <style>
    .settings-panel {
      background: var(--color-white);
      border: 1px solid var(--color-charcoal-border);
      border-radius: var(--radius-xs);
      padding: var(--space-2xl);
      box-shadow: var(--shadow-subtle);
      margin-bottom: var(--space-2xl);
      width: 100%;
      box-sizing: border-box;
    }
    .panel-heading {
      font-family: var(--font-serif);
      font-size: 1.5rem;
      margin: 0 0 4px;
      color: var(--color-charcoal);
    }
    .panel-sub {
      font-size: 0.85rem;
      color: var(--color-charcoal-muted);
      margin-bottom: var(--space-xl);
      border-bottom: 1px solid var(--color-charcoal-border);
      padding-bottom: var(--space-md);
    }
    .settings-grid-4 {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 14px;
      margin-bottom: 14px;
      width: 100%;
      box-sizing: border-box;
    }
    .settings-grid-3 {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 14px;
      margin-bottom: 14px;
      width: 100%;
      box-sizing: border-box;
    }
    .settings-grid-2 {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 16px;
      margin-bottom: 14px;
      width: 100%;
      box-sizing: border-box;
    }
    .settings-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      width: 100%;
      box-sizing: border-box;
    }

    @media (max-width: 900px) {
      .settings-grid-4 {
        grid-template-columns: repeat(2, 1fr);
      }
      .settings-grid-3 {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 600px) {
      .settings-panel {
        padding: 18px 14px !important;
        margin-bottom: var(--space-lg) !important;
      }
      .panel-heading {
        font-size: 1.25rem !important;
      }
      .panel-sub {
        font-size: 0.8rem !important;
        margin-bottom: var(--space-md) !important;
        padding-bottom: var(--space-sm) !important;
      }
      .settings-grid-4,
      .settings-grid-3,
      .settings-grid-2 {
        grid-template-columns: 1fr !important;
        gap: 12px !important;
      }
      .settings-actions {
        flex-direction: column !important;
      }
      .settings-actions .btn {
        width: 100% !important;
        text-align: center !important;
        justify-content: center !important;
      }
    }
  </style>
</head>
<body style="background-color: var(--color-ivory);">

  <!-- SHARED ADMIN NAVIGATION -->
  <?php require_once __DIR__ . '/includes/admin-nav.php'; ?>

  <!-- HERO SECTION -->
  <section style="background-color: var(--color-white); border-bottom: 1px solid var(--color-charcoal-border); padding: var(--space-xl) 0;">
    <div class="container">
      <span class="eyebrow eyebrow-no-line">Atelier Configuration</span>
      <h1 style="font-family: var(--font-serif); font-size: 2.2rem; color: var(--color-charcoal); margin: 4px 0 6px;">
        Salon Settings &amp; Business Rules
      </h1>
      <p style="color: var(--color-charcoal-muted); margin: 0; font-size: 0.95rem;">
        Manage business contact details, weekly operating hours, deposit percentages, and cancellation policies.
      </p>
    </div>
  </section>

  <!-- MAIN SETTINGS CONTENT -->
  <main style="padding: var(--space-3xl) 0 var(--space-4xl);">
    <div class="container" style="max-width:880px;">

      <!-- Notifications -->
      <?php if ($statusMessage): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid #4E9F3D; padding: 14px 18px; margin-bottom: var(--space-xl); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="font-weight: 600; color: #1E5128; font-size: 0.92rem;"><?php echo e($statusMessage); ?></div>
        </div>
      <?php endif; ?>

      <?php if ($statusError): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid #C95353; padding: 14px 18px; margin-bottom: var(--space-xl); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="font-weight: 600; color: #9E2A2B; font-size: 0.92rem;"><?php echo e($statusError); ?></div>
        </div>
      <?php endif; ?>

      <form action="admin-settings.php" method="POST">
        <?php echo csrf_field(); ?>

        <!-- 1. BRAND & ATELIER IDENTITY -->
        <div class="settings-panel">
          <h3 class="panel-heading">Atelier Brand &amp; Identity</h3>
          <div class="panel-sub">Public wordmark and tagline displayed across customer sanctuary portals.</div>

          <div class="settings-grid-2">
            <div>
              <label class="form-label">Salon Brand Name *</label>
              <input type="text" name="salon_name" class="form-control" value="<?php echo e($settings['salon_name'] ?? "L'Étoile Hair Salon & Spa"); ?>" required>
            </div>
            <div>
              <label class="form-label">Editorial Subtitle / Tagline</label>
              <input type="text" name="salon_tagline" class="form-control" value="<?php echo e($settings['salon_tagline'] ?? 'Haute Coiffure & Holistic Spa Sanctuary'); ?>">
            </div>
          </div>
        </div>

        <!-- 2. CONTACT & LOCATION DETAILS -->
        <div class="settings-panel">
          <h3 class="panel-heading">Public Concierge &amp; Location</h3>
          <div class="panel-sub">Official communication coordinates displayed in website footers, confirmations, and reminders.</div>

          <div style="margin-bottom:14px;">
            <label class="form-label">Atelier Physical Address</label>
            <input type="text" name="contact_address" class="form-control" value="<?php echo e($settings['contact_address'] ?? '482 Boulevard Saint-Honoré, Suite 400, Design Quarter, NY 10021'); ?>">
          </div>

          <div class="settings-grid-3">
            <div>
              <label class="form-label">Concierge Telephone</label>
              <input type="text" name="contact_phone" class="form-control" value="<?php echo e($settings['contact_phone'] ?? '+1 (800) 555-0192'); ?>">
            </div>
            <div>
              <label class="form-label">Support Email Address</label>
              <input type="email" name="contact_email" class="form-control" value="<?php echo e($settings['contact_email'] ?? 'concierge@letoile-salon-placeholder.com'); ?>">
            </div>
            <div>
              <label class="form-label">WhatsApp Hotline (Digits)</label>
              <input type="text" name="contact_whatsapp" class="form-control" value="<?php echo e($settings['contact_whatsapp'] ?? '+18005550192'); ?>">
            </div>
          </div>
        </div>

        <!-- 3. OPERATING HOURS & SCHEDULES -->
        <div class="settings-panel">
          <h3 class="panel-heading">Operating Hours &amp; Atelier Schedule</h3>
          <div class="panel-sub">Working schedules reflected in the live appointment availability engine.</div>

          <div class="settings-grid-2">
            <div>
              <label class="form-label">Tuesday – Friday Hours</label>
              <input type="text" name="hours_weekdays" class="form-control" value="<?php echo e($settings['hours_weekdays'] ?? '09:00 AM – 08:30 PM'); ?>">
            </div>
            <div>
              <label class="form-label">Saturday – Sunday Hours</label>
              <input type="text" name="hours_weekends" class="form-control" value="<?php echo e($settings['hours_weekends'] ?? '09:30 AM – 07:00 PM'); ?>">
            </div>
          </div>

          <div style="margin-bottom:14px;">
            <label class="form-label">Monday Status (Masterclass / Education)</label>
            <input type="text" name="hours_monday" class="form-control" value="<?php echo e($settings['hours_monday'] ?? 'Closed for Masterclasses & Private Education'); ?>">
          </div>
        </div>

        <!-- 4. BOOKING & CANCELLATION POLICIES -->
        <div class="settings-panel">
          <h3 class="panel-heading">Booking &amp; Cancellation Policies</h3>
          <div class="panel-sub">Enforced rules governing online self-service cancellations and booking lead times.</div>

          <div class="settings-grid-4">
            <div>
              <label class="form-label">Currency Symbol</label>
              <input type="text" name="currency_symbol" class="form-control" value="<?php echo e($settings['currency_symbol'] ?? '$'); ?>">
            </div>
            <div>
              <label class="form-label">Deposit Required (%)</label>
              <input type="number" min="0" max="100" name="appointment_deposit_percentage" class="form-control" value="<?php echo e($settings['appointment_deposit_percentage'] ?? '20'); ?>">
            </div>
            <div>
              <label class="form-label">Cancellation Limit (Hours)</label>
              <input type="number" min="1" max="168" name="cancellation_notice_hours" class="form-control" value="<?php echo e($settings['cancellation_notice_hours'] ?? '24'); ?>">
              <div style="font-size:0.7rem; color:var(--color-charcoal-muted); margin-top:2px;">Online self-cancel cutoff</div>
            </div>
            <div>
              <label class="form-label">Advance Horizon (Days)</label>
              <input type="number" min="7" max="365" name="booking_advance_days_max" class="form-control" value="<?php echo e($settings['booking_advance_days_max'] ?? '60'); ?>">
            </div>
          </div>
        </div>

        <div class="settings-actions">
          <a href="admin-dashboard.php" class="btn btn-outline">Cancel</a>
          <button type="submit" class="btn btn-champagne">Save Salon Settings</button>
        </div>

      </form>

    </div>
  </main>

  <!-- FOOTER -->
  <footer class="site-footer" role="contentinfo">
    <div class="container footer-bottom-inner">
      <p>&copy; <?php echo date('Y'); ?> L'ÉTOILE Luxury Unisex Hair Salon &amp; Spa &bull; Concierge Operations Panel.</p>
    </div>
  </footer>

</body>
</html>
