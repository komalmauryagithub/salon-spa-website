<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Demo Customer Protected Sanctuary Portal & Profile
 * 
 * Strict Brand Theme: Ivory #FAF7F2, Champagne #D4AF37, Charcoal #161413, White #FFFFFF
 */

require_once __DIR__ . '/../includes/bootstrap.php';
Demo::requireEnabled();

// 1. Guard check: Must be authenticated demo customer
$customer = DemoAuth::requireCustomer('login.php');

$profileMessage = null;
$profileError = null;

// 2. Handle POST Actions (Edit Profile or Cancel Appointment)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['demo_csrf_token'] ?? null;
    if (!DemoCsrf::verify($token)) {
        $profileError = 'Security session expired. Please refresh the page.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'update_profile') {
            DemoStore::updateCustomer([
                'first_name'        => $_POST['first_name'] ?? '',
                'last_name'         => $_POST['last_name'] ?? '',
                'phone'             => $_POST['phone'] ?? '',
                'gender_preference' => $_POST['gender_preference'] ?? "Women's Styling Focus",
                'allergies_notes'   => $_POST['allergies_notes'] ?? ''
            ]);
            $customer = DemoAuth::customer();
            $profileMessage = 'Your bespoke demo profile preferences have been updated in session.';
        } elseif ($action === 'cancel_appointment') {
            $ref = trim((string)($_POST['booking_reference'] ?? ''));
            $reason = trim((string)($_POST['cancellation_reason'] ?? 'Cancelled via Demo Portal'));

            if (empty($ref)) {
                $profileError = 'Invalid booking reference.';
            } else {
                $cancelled = DemoStore::cancelAppointment($ref, $reason);
                if ($cancelled) {
                    $profileMessage = "Reservation [{$ref}] has been successfully cancelled in demo session memory.";
                } else {
                    $profileError = 'Unable to cancel appointment. Record not found.';
                }
            }
        }
    }
}

// 3. Fetch Personal Appointments from Demo Store
$appointments = DemoStore::getAppointments(DEMO_CUSTOMER_EMAIL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Sanctuary Profile (Demo) — L'Étoile Luxury Unisex Hair Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  
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
      background-color: rgba(212,175,55,0.15);
      color: var(--color-champagne-dark);
      border: 1px solid var(--color-champagne);
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
          <span>🎭 <strong>DEMO CUSTOMER SANCTUARY</strong> &bull; Zero Live Database Required</span>
        </span>
      </div>
      <div class="top-bar-right">
        <span class="top-bar-item">
          <span>Signed in as <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
        </span>
        <span class="top-bar-item">
          <a href="logout.php" style="color:var(--color-champagne-light); font-weight:600;">Demo Sign Out</a>
        </span>
      </div>
    </div>
  </div>

  <!-- SITE HEADER -->
  <header class="site-header" role="banner">
    <div class="container nav-container">
      <a href="index.php" class="brand" aria-label="L'Étoile Home">
        <span class="brand-title">L'ÉTOILE</span>
        <span class="brand-subtitle">HAIR SALON &amp; SPA &bull; DEMO</span>
      </a>

      <nav class="nav-links" id="primary-navigation" aria-label="Main Navigation">
        <a href="index.php" class="nav-link">Demo Hub</a>
        <a href="book-appointment.php" class="nav-link">Book Ritual</a>
        <a href="customer-profile.php" class="nav-link active">My Account</a>
        <a href="admin-dashboard.php" class="nav-link">Admin Console</a>
        <div class="mobile-only-cta">
          <a href="book-appointment.php" class="btn btn-champagne">Book New Ritual</a>
        </div>
      </nav>

      <div class="nav-cta">
        <a href="book-appointment.php" class="btn btn-champagne btn-sm">
          <span>Book Ritual</span>
          <span class="btn-icon">&rarr;</span>
        </a>
      </div>
    </div>
  </header>

  <!-- DASHBOARD HERO -->
  <section style="background-color: var(--color-white); border-bottom: 1px solid var(--color-charcoal-border); padding: var(--space-2xl) 0;">
    <div class="container">
      <div style="display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Private Demo Guest Sanctuary</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.4rem; color: var(--color-charcoal); margin: 4px 0 8px;">
            Bonjour, <em><?php echo htmlspecialchars($customer['first_name'], ENT_QUOTES, 'UTF-8'); ?></em>
          </h1>
          <p style="color: var(--color-charcoal-muted); margin: 0; font-size: 0.95rem;">
            Member of L'Étoile Atelier Circle &bull; Client ID: #<?php echo str_pad((string)$customer['id'], 4, '0', STR_PAD_LEFT); ?>
            &bull; <strong style="color:var(--color-champagne-dark);">&#9733; VIP Sanctuary Patron</strong>
          </p>
        </div>
        <div>
          <a href="book-appointment.php" class="btn btn-champagne btn-md">Reserve New Ritual</a>
          <a href="logout.php" class="btn btn-outline btn-md" style="margin-left: 8px;">Sign Out</a>
        </div>
      </div>
    </div>
  </section>

  <!-- MAIN DASHBOARD CONTENT -->
  <main style="padding: var(--space-3xl) 0 var(--space-4xl);">
    <div class="container">

      <!-- Notifications -->
      <?php if ($profileMessage): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid var(--color-champagne); padding: 14px 18px; margin-bottom: var(--space-xl); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="font-weight: 600; font-size: 0.92rem; color: var(--color-charcoal);"><?php echo htmlspecialchars($profileMessage, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endif; ?>

      <?php if ($profileError): ?>
        <div style="background-color: #FFFFFF; border-left: 4px solid #C95353; padding: 14px 18px; margin-bottom: var(--space-xl); border-radius: var(--radius-xs); box-shadow: var(--shadow-subtle);">
          <div style="color: #9E2A2B; font-weight: 600; font-size: 0.92rem;"><?php echo htmlspecialchars($profileError, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>
      <?php endif; ?>

      <div style="display: grid; grid-template-columns: 1.8fr 1.2fr; gap: var(--space-2xl); align-items: start;">
        
        <!-- LEFT COLUMN: APPOINTMENT HISTORY -->
        <div>
          <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-lg);">
            <div>
              <h2 style="font-family: var(--font-serif); font-size: 1.7rem; color: var(--color-charcoal); margin: 0 0 4px;">
                Personal Appointment Itinerary
              </h2>
              <p style="font-size: 0.88rem; color: var(--color-charcoal-muted); margin: 0;">
                All upcoming and past sanctuary visits recorded in your isolated demonstration session.
              </p>
            </div>
            <a href="book-appointment.php" class="btn btn-outline btn-sm">+ New Booking</a>
          </div>

          <?php if (empty($appointments)): ?>
            <div style="background: var(--color-white); border: 1px dashed var(--color-charcoal-border); border-radius: var(--radius-xs); padding: var(--space-2xl); text-align: center;">
              <p style="color: var(--color-charcoal-muted); margin-bottom: var(--space-md);">No appointments found in your demo itinerary.</p>
              <a href="book-appointment.php" class="btn btn-champagne btn-sm">Reserve Your First Ritual</a>
            </div>
          <?php else: ?>
            <?php foreach ($appointments as $apt): ?>
              <?php
                $statusClass = 'status-' . strtolower($apt['status']);
                $dateFormatted = date('l, F j, Y', strtotime($apt['appointment_date']));
                $timeFormatted = date('g:i A', strtotime($apt['appointment_time']));
                $canCancel = in_array(strtolower($apt['status']), ['pending', 'confirmed'], true);
              ?>
              <div class="history-card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; flex-wrap: wrap; gap: 8px;">
                  <div>
                    <span class="status-badge <?php echo $statusClass; ?>">
                      <?php echo htmlspecialchars($apt['status'], ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <strong style="margin-left: 8px; font-size: 0.85rem; color: var(--color-charcoal-muted);">
                      Ref: <?php echo htmlspecialchars($apt['booking_reference'], ENT_QUOTES, 'UTF-8'); ?>
                    </strong>
                  </div>
                  <div style="font-weight: 700; color: var(--color-champagne-dark); font-size: 1.15rem;">
                    $<?php echo number_format((float)$apt['total_price'], 2); ?>
                  </div>
                </div>

                <h3 style="font-family: var(--font-serif); font-size: 1.35rem; margin: 4px 0 6px; color: var(--color-charcoal);">
                  <?php echo htmlspecialchars($apt['service_name'], ENT_QUOTES, 'UTF-8'); ?>
                </h3>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px; font-size: 0.86rem; color: var(--color-charcoal-muted); margin: 10px 0 14px;">
                  <div>
                    <strong>Date &amp; Time:</strong><br>
                    <?php echo $dateFormatted; ?> at <?php echo $timeFormatted; ?>
                  </div>
                  <div>
                    <strong>Specialist:</strong><br>
                    <?php echo htmlspecialchars($apt['staff_name'] ?? 'Master Specialist', ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                  <div>
                    <strong>Duration:</strong><br>
                    <?php echo (int)$apt['duration_minutes']; ?> mins
                  </div>
                </div>

                <?php if (!empty($apt['notes'])): ?>
                  <div style="font-size: 0.8rem; background: var(--color-ivory); padding: 8px 12px; border-radius: var(--radius-xs); margin-bottom: 12px; color: var(--color-charcoal-muted);">
                    <strong>Notes:</strong> <?php echo htmlspecialchars($apt['notes'], ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                <?php endif; ?>

                <?php if ($canCancel): ?>
                  <div style="display: flex; justify-content: flex-end; padding-top: 10px; border-top: 1px dashed var(--color-charcoal-border);">
                    <form action="customer-profile.php" method="POST" onsubmit="return confirm('Are you sure you wish to cancel reservation [<?php echo $apt['booking_reference']; ?>]?');" style="margin: 0;">
                      <?php echo DemoCsrf::field(); ?>
                      <input type="hidden" name="action" value="cancel_appointment">
                      <input type="hidden" name="booking_reference" value="<?php echo htmlspecialchars($apt['booking_reference'], ENT_QUOTES, 'UTF-8'); ?>">
                      <input type="hidden" name="cancellation_reason" value="Cancelled by client via demo sanctuary">
                      <button type="submit" class="btn btn-outline btn-sm" style="color: #9E2A2B; border-color: rgba(201,83,83,0.3); padding: 6px 14px; font-size: 0.78rem;">
                        Cancel Reservation
                      </button>
                    </form>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <!-- RIGHT COLUMN: EDIT PROFILE PREFERENCES -->
        <div>
          <div class="form-card" style="box-shadow: var(--shadow-subtle);">
            <div style="margin-bottom: var(--space-lg);">
              <span class="eyebrow eyebrow-no-line">Client Customization</span>
              <h2 style="font-family: var(--font-serif); font-size: 1.5rem; color: var(--color-charcoal); margin: 4px 0 6px;">
                Sanctuary Profile
              </h2>
              <p style="font-size: 0.86rem; color: var(--color-charcoal-muted); margin: 0;">
                Update your preferred treatments and sensory preferences in demo session memory.
              </p>
            </div>

            <form action="customer-profile.php" method="POST" novalidate>
              <?php echo DemoCsrf::field(); ?>
              <input type="hidden" name="action" value="update_profile">

              <div class="form-row">
                <div class="form-group">
                  <label for="first_name" class="form-label">First Name <span class="req">*</span></label>
                  <input type="text" id="first_name" name="first_name" class="form-control" value="<?php echo htmlspecialchars($customer['first_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
                <div class="form-group">
                  <label for="last_name" class="form-label">Last Name <span class="req">*</span></label>
                  <input type="text" id="last_name" name="last_name" class="form-control" value="<?php echo htmlspecialchars($customer['last_name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>
              </div>

              <div class="form-group">
                <label for="email" class="form-label">Email Address (Read-Only in Demo)</label>
                <input type="email" id="email" class="form-control" value="<?php echo htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8'); ?>" readonly style="background:#F0ECE4;">
              </div>

              <div class="form-group">
                <label for="phone" class="form-label">Contact Telephone <span class="req">*</span></label>
                <input type="tel" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($customer['phone'], ENT_QUOTES, 'UTF-8'); ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Styling Focus</label>
                <select name="gender_preference" class="form-control">
                  <option value="Unisex / Flexible" <?php echo $customer['gender_preference'] === 'Unisex / Flexible' ? 'selected' : ''; ?>>Unisex / Flexible</option>
                  <option value="Women's Styling Focus" <?php echo $customer['gender_preference'] === "Women's Styling Focus" ? 'selected' : ''; ?>>Women's Styling Focus</option>
                  <option value="Men's Barbering Focus" <?php echo $customer['gender_preference'] === "Men's Barbering Focus" ? 'selected' : ''; ?>>Men's Barbering Focus</option>
                </select>
              </div>

              <div class="form-group">
                <label for="allergies_notes" class="form-label">Botanical Allergies &amp; Scalp Preferences</label>
                <textarea id="allergies_notes" name="allergies_notes" class="form-control" rows="3"><?php echo htmlspecialchars($customer['allergies_notes'] ?? '', ENT_QUOTES, 'UTF-8'); ?></textarea>
              </div>

              <button type="submit" class="btn btn-primary" style="width: 100%;">
                <span>Save Profile Changes</span>
                <span class="btn-icon">&rarr;</span>
              </button>
            </form>
          </div>

          <!-- Demo Reset Helper -->
          <div style="margin-top: var(--space-md); text-align: center;">
            <a href="index.php?action=reset" onclick="return confirm('Reset demo appointments to original sample records?');" style="font-size: 0.8rem; color: var(--color-charcoal-muted); text-decoration: underline;">
              ↺ Reset Demo Appointments to Initial Baseline
            </a>
          </div>
        </div>

      </div>

    </div>
  </main>

  <!-- FOOTER -->
  <footer style="background-color: var(--color-charcoal); color: var(--color-ivory); padding: var(--space-lg) 0; font-size: 0.85rem; text-align: center; border-top: 1px solid var(--color-champagne-border);">
    <div class="container">
      <p style="margin: 0; color: rgba(245,239,230,0.7);">
        &copy; <?php echo date('Y'); ?> L'Étoile Luxury Unisex Hair Salon &amp; Spa &bull; Demo Mode Customer Portal
      </p>
    </div>
  </footer>

</body>
</html>
