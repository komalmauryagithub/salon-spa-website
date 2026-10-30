<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Interactive Demo Appointment Booking Wizard
 * 
 * Strict Brand Theme: Ivory #FAF7F2, Champagne #D4AF37, Charcoal #161413, White #FFFFFF
 */

require_once __DIR__ . '/../includes/bootstrap.php';
Demo::requireEnabled();
DemoStore::initSessionStore();

$isLoggedInCustomer = DemoAuth::isCustomer();
$customer = $isLoggedInCustomer ? DemoAuth::customer() : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reserve Ritual (Demo Mode) — L'Étoile Luxury Unisex Hair Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="../assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body style="background-color: var(--color-ivory);">

  <!-- TOP BAR -->
  <div class="top-bar">
    <div class="container top-bar-inner">
      <div class="top-bar-left">
        <span class="top-bar-item">
          <span>🎭 <strong>DEMO BOOKING ENGINE</strong> &bull; Zero Live Database Required</span>
        </span>
      </div>
      <div class="top-bar-right">
        <?php if ($isLoggedInCustomer): ?>
          <span class="top-bar-item">
            <span>Signed in as <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
          </span>
          <span class="top-bar-item">
            <a href="customer-profile.php" style="color:var(--color-champagne-light); font-weight:600;">My Itinerary</a>
          </span>
        <?php else: ?>
          <span class="top-bar-item">
            <a href="login.php?redirect=book-appointment.php" class="top-auth-pill">
              <span>👤 Demo Client Sign In &rarr;</span>
            </a>
          </span>
        <?php endif; ?>
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
        <a href="book-appointment.php" class="nav-link active">Book Ritual</a>
        <a href="customer-profile.php" class="nav-link">Customer Profile</a>
        <a href="admin-dashboard.php" class="nav-link">Admin Console</a>
      </nav>

      <div class="nav-cta">
        <a href="index.php" class="btn btn-outline btn-sm">&larr; Demo Gateway</a>
      </div>
    </div>
  </header>

  <main id="main-content">
    <!-- PAGE HERO -->
    <section class="booking-hero-rail" style="background-color: var(--color-white); border-bottom: 1px solid var(--color-charcoal-border); padding: var(--space-2xl) 0;">
      <div class="container text-center">
        <span class="eyebrow">Interactive Reservation Demonstration</span>
        <h1 class="page-title" style="margin-bottom: 6px;">
          Curate Your Salon <em>Ritual</em>
        </h1>
        <p class="hero-lead" style="max-width: 640px; margin: 0 auto 12px; font-size: 0.95rem;">
          Test the four-step salon booking journey. Reservations are stored in isolated demo memory and immediately appear in both the Customer and Admin dashboards.
        </p>
        <div class="luxury-badge-row">
          <span class="luxury-pill">✦ In-Memory Session Storage</span>
          <span class="luxury-pill">✦ Instant Slot Locking</span>
          <span class="luxury-pill">✦ Zero Live Database Risk</span>
        </div>
      </div>
    </section>

    <!-- INTERACTIVE BOOKING WORKFLOW -->
    <section class="section">
      <div class="container">
        <div class="booking-card-wrapper" style="max-width: 1140px; margin: 0 auto;">
          
          <!-- Step Progression Navigator -->
          <nav class="booking-steps-bar" aria-label="Booking Progress">
            <button type="button" class="step-indicator active" data-step="1">
              <span class="step-circle">1</span>
              <span class="step-name">1. Ritual</span>
            </button>
            <button type="button" class="step-indicator" data-step="2">
              <span class="step-circle">2</span>
              <span class="step-name">2. Specialist</span>
            </button>
            <button type="button" class="step-indicator" data-step="3">
              <span class="step-circle">3</span>
              <span class="step-name">3. Date &amp; Time</span>
            </button>
            <button type="button" class="step-indicator" data-step="4">
              <span class="step-circle">4</span>
              <span class="step-name">4. Client Details</span>
            </button>
          </nav>

          <!-- 2-Column Layout -->
          <div class="booking-layout-grid" style="display: grid; grid-template-columns: 1.55fr 0.95fr; gap: var(--space-2xl); align-items: start;">
            
            <!-- Workflow Form Panels -->
            <div class="form-card" style="box-shadow: var(--shadow-subtle);">
              <form id="demo-booking-form" novalidate>
                
                <!-- STEP 1: SERVICE -->
                <div class="booking-step-panel" data-step="1">
                  <div style="margin-bottom: var(--space-lg);">
                    <span class="eyebrow eyebrow-no-line">Step 1 of 4</span>
                    <h3 style="font-family: var(--font-serif); font-size: 1.6rem; margin-bottom: 4px;">Choose Your Ritual</h3>
                    <p style="font-size: 0.9rem; color: var(--color-charcoal-muted); margin: 0;">
                      Select a treatment to demonstrate pricing and duration calculations.
                    </p>
                  </div>

                  <div class="service-select-grid">
                    <div class="service-select-card selected" data-service-id="haircut" data-service-name="Artisanal Precision Haircut" data-service-price="$85.00" data-service-duration="60 mins">
                      <input type="radio" name="service_choice" value="haircut" checked>
                      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px;">
                        <h4 style="font-family: var(--font-serif); font-size: 1.15rem; margin:0;">Precision Haircut</h4>
                        <strong style="color:var(--color-champagne-dark); font-size: 1.1rem;">$85</strong>
                      </div>
                      <p style="font-size: 0.8rem; color: var(--color-charcoal-muted); margin:0;">60 mins &bull; Unisex haircut, botanical wash &amp; blow-dry.</p>
                    </div>

                    <div class="service-select-card" data-service-id="balayage" data-service-name="Couture French Balayage" data-service-price="$195.00" data-service-duration="150 mins">
                      <input type="radio" name="service_choice" value="balayage">
                      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px;">
                        <h4 style="font-family: var(--font-serif); font-size: 1.15rem; margin:0;">Couture Balayage</h4>
                        <strong style="color:var(--color-champagne-dark); font-size: 1.1rem;">$195</strong>
                      </div>
                      <p style="font-size: 0.8rem; color: var(--color-charcoal-muted); margin:0;">150 mins &bull; Hand-painted blonde dimension &amp; gloss.</p>
                    </div>

                    <div class="service-select-card" data-service-id="hair-spa" data-service-name="Japanese Head Spa &amp; Scalp Cure" data-service-price="$120.00" data-service-duration="75 mins">
                      <input type="radio" name="service_choice" value="hair-spa">
                      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px;">
                        <h4 style="font-family: var(--font-serif); font-size: 1.15rem; margin:0;">Japanese Head Spa</h4>
                        <strong style="color:var(--color-champagne-dark); font-size: 1.1rem;">$120</strong>
                      </div>
                      <p style="font-size: 0.8rem; color: var(--color-charcoal-muted); margin:0;">75 mins &bull; Ultrasonic micro-mist waterfall &amp; scalp cure.</p>
                    </div>

                    <div class="service-select-card" data-service-id="facial" data-service-name="Cellular Rejuvenation Facial" data-service-price="$140.00" data-service-duration="75 mins">
                      <input type="radio" name="service_choice" value="facial">
                      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px;">
                        <h4 style="font-family: var(--font-serif); font-size: 1.15rem; margin:0;">Cellular Facial</h4>
                        <strong style="color:var(--color-champagne-dark); font-size: 1.1rem;">$140</strong>
                      </div>
                      <p style="font-size: 0.8rem; color: var(--color-charcoal-muted); margin:0;">75 mins &bull; Peptide infusion and micro-current lifting.</p>
                    </div>

                    <div class="service-select-card" data-service-id="executive-package" data-service-name="The Executive Revival Package" data-service-price="$135.00" data-service-duration="90 mins">
                      <input type="radio" name="service_choice" value="executive-package">
                      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px;">
                        <h4 style="font-family: var(--font-serif); font-size: 1.15rem; margin:0;">The Executive Revival Package</h4>
                        <strong style="color:var(--color-champagne-dark); font-size: 1.1rem;">$135</strong>
                      </div>
                      <p style="font-size: 0.8rem; color: var(--color-charcoal-muted); margin:0;">90 mins &bull; Scissor haircut, hot towel scalp wrap &amp; razor shave.</p>
                    </div>

                    <div class="service-select-card" data-service-id="occasion-package" data-service-name="The Occasion Suite Package" data-service-price="$295.00" data-service-duration="180 mins">
                      <input type="radio" name="service_choice" value="occasion-package">
                      <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px;">
                        <h4 style="font-family: var(--font-serif); font-size: 1.15rem; margin:0;">The Occasion Suite Package</h4>
                        <strong style="color:var(--color-champagne-dark); font-size: 1.1rem;">$295</strong>
                      </div>
                      <p style="font-size: 0.8rem; color: var(--color-charcoal-muted); margin:0;">180 mins &bull; Gala up-style, primer glow facial &amp; manicure.</p>
                    </div>
                  </div>
                </div>

                <!-- STEP 2: SPECIALIST -->
                <div class="booking-step-panel" data-step="2" style="display:none;">
                  <div style="margin-bottom: var(--space-lg);">
                    <span class="eyebrow eyebrow-no-line">Step 2 of 4</span>
                    <h3 style="font-family: var(--font-serif); font-size: 1.6rem; margin-bottom: 4px;">Select Preferred Specialist</h3>
                    <p style="font-size: 0.9rem; color: var(--color-charcoal-muted); margin: 0;">
                      Match with a dedicated creative director or allow any available master specialist.
                    </p>
                  </div>

                  <div class="stylist-select-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap: var(--space-md);">
                    <div class="stylist-select-card selected" data-stylist-id="any" data-stylist-name="Any Available Master Specialist" style="border:1px solid var(--color-champagne); padding:16px; border-radius:var(--radius-xs); cursor:pointer; background:rgba(212,175,55,0.06);">
                      <input type="radio" name="stylist_choice" value="any" checked>
                      <h4 style="font-family:var(--font-serif); margin:4px 0 2px;">Any Available Master</h4>
                      <p style="font-size:0.8rem; color:var(--color-charcoal-muted); margin:0;">Next available salon director.</p>
                    </div>

                    <div class="stylist-select-card" data-stylist-id="clara" data-stylist-name="Clara Valmont" style="border:1px solid var(--color-charcoal-border); padding:16px; border-radius:var(--radius-xs); cursor:pointer;">
                      <input type="radio" name="stylist_choice" value="clara">
                      <h4 style="font-family:var(--font-serif); margin:4px 0 2px;">Clara Valmont</h4>
                      <p style="font-size:0.8rem; color:var(--color-charcoal-muted); margin:0;">Master Colorist &amp; Spa Director</p>
                    </div>

                    <div class="stylist-select-card" data-stylist-id="julian" data-stylist-name="Julian Moreau" style="border:1px solid var(--color-charcoal-border); padding:16px; border-radius:var(--radius-xs); cursor:pointer;">
                      <input type="radio" name="stylist_choice" value="julian">
                      <h4 style="font-family:var(--font-serif); margin:4px 0 2px;">Julian Moreau</h4>
                      <p style="font-size:0.8rem; color:var(--color-charcoal-muted); margin:0;">Creative Director of Balayage</p>
                    </div>

                    <div class="stylist-select-card" data-stylist-id="elena" data-stylist-name="Elena Rostova" style="border:1px solid var(--color-charcoal-border); padding:16px; border-radius:var(--radius-xs); cursor:pointer;">
                      <input type="radio" name="stylist_choice" value="elena">
                      <h4 style="font-family:var(--font-serif); margin:4px 0 2px;">Elena Rostova</h4>
                      <p style="font-size:0.8rem; color:var(--color-charcoal-muted); margin:0;">Head of Aesthetics &amp; Wellness</p>
                    </div>

                    <div class="stylist-select-card" data-stylist-id="marcus" data-stylist-name="Marcus Sterling" style="border:1px solid var(--color-charcoal-border); padding:16px; border-radius:var(--radius-xs); cursor:pointer;">
                      <input type="radio" name="stylist_choice" value="marcus">
                      <h4 style="font-family:var(--font-serif); margin:4px 0 2px;">Marcus Sterling</h4>
                      <p style="font-size:0.8rem; color:var(--color-charcoal-muted); margin:0;">Master Barber Director</p>
                    </div>
                  </div>
                </div>

                <!-- STEP 3: DATE & TIME -->
                <div class="booking-step-panel" data-step="3" style="display:none;">
                  <div style="margin-bottom: var(--space-lg);">
                    <span class="eyebrow eyebrow-no-line">Step 3 of 4</span>
                    <h3 style="font-family: var(--font-serif); font-size: 1.6rem; margin-bottom: 4px;">Choose Date &amp; Sanctuary Time</h3>
                    <p style="font-size: 0.9rem; color: var(--color-charcoal-muted); margin: 0;">
                      Available demonstration sessions are dynamically rendered for your selected date.
                    </p>
                  </div>

                  <div class="form-group">
                    <label for="demo-date" class="form-label">Preferred Date <span class="req">*</span></label>
                    <input type="date" id="demo-date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" required>
                  </div>

                  <div style="margin-top: var(--space-lg);">
                    <label class="form-label">Available Sessions <span class="req">*</span></label>
                    
                    <p style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--color-champagne-dark); margin: 8px 0 4px;">Morning Sessions</p>
                    <div class="time-slots-grid" style="margin-bottom: var(--space-md);">
                      <button type="button" class="time-slot-btn" data-time="09:30 AM">09:30 AM</button>
                      <button type="button" class="time-slot-btn" data-time="10:30 AM">10:30 AM</button>
                      <button type="button" class="time-slot-btn" data-time="11:30 AM">11:30 AM</button>
                      <button type="button" class="time-slot-btn unavailable" disabled title="Slot booked in demo schedule">12:00 PM</button>
                    </div>

                    <p style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--color-champagne-dark); margin: 8px 0 4px;">Afternoon Sessions</p>
                    <div class="time-slots-grid" style="margin-bottom: var(--space-md);">
                      <button type="button" class="time-slot-btn selected" data-time="01:30 PM">01:30 PM</button>
                      <button type="button" class="time-slot-btn" data-time="02:30 PM">02:30 PM</button>
                      <button type="button" class="time-slot-btn" data-time="03:30 PM">03:30 PM</button>
                      <button type="button" class="time-slot-btn" data-time="04:30 PM">04:30 PM</button>
                    </div>

                    <p style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--color-champagne-dark); margin: 8px 0 4px;">Evening Twilight Sessions</p>
                    <div class="time-slots-grid">
                      <button type="button" class="time-slot-btn" data-time="05:30 PM">05:30 PM</button>
                      <button type="button" class="time-slot-btn" data-time="06:30 PM">06:30 PM</button>
                      <button type="button" class="time-slot-btn" data-time="07:00 PM">07:00 PM</button>
                    </div>
                  </div>
                </div>

                <!-- STEP 4: CLIENT DETAILS -->
                <div class="booking-step-panel" data-step="4" style="display:none;">
                  <div style="margin-bottom: var(--space-lg);">
                    <span class="eyebrow eyebrow-no-line">Step 4 of 4</span>
                    <h3 style="font-family: var(--font-serif); font-size: 1.6rem; margin-bottom: 4px;">Client Details &amp; Customization</h3>
                    <p style="font-size: 0.9rem; color: var(--color-charcoal-muted); margin: 0;">
                      Personalize your appointment consultation formulas.
                    </p>
                  </div>

                  <?php if ($isLoggedInCustomer): ?>
                    <div style="background: rgba(212,175,55,0.08); border: 1px solid var(--color-champagne); border-radius: var(--radius-xs); padding: 12px 18px; margin-bottom: var(--space-lg); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
                      <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="color: #2D6A4F; font-size: 1.15rem; font-weight: bold;">✓</span>
                        <span style="font-size: 0.9rem; color: var(--color-charcoal);">Signed in as <strong><?php echo htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'], ENT_QUOTES, 'UTF-8'); ?></strong> (<?php echo htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8'); ?>)</span>
                      </div>
                      <span style="font-size: 0.72rem; text-transform: uppercase; letter-spacing: 0.08em; color: var(--color-champagne-dark); font-weight: 700;">✦ Verified Demo Profile</span>
                    </div>
                  <?php endif; ?>

                  <div class="form-row">
                    <div class="form-group">
                      <label for="demo-name" class="form-label">Full Name <span class="req">*</span></label>
                      <input type="text" id="demo-name" class="form-control" value="<?php echo $isLoggedInCustomer ? htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'], ENT_QUOTES, 'UTF-8') : 'Adrienne Laurent'; ?>" required>
                    </div>

                    <div class="form-group">
                      <label for="demo-phone" class="form-label">Phone Number <span class="req">*</span></label>
                      <input type="tel" id="demo-phone" class="form-control" value="<?php echo $isLoggedInCustomer ? htmlspecialchars($customer['phone'], ENT_QUOTES, 'UTF-8') : '+1 (555) 234-5678'; ?>" required>
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="demo-email" class="form-label">Email Address <span class="req">*</span></label>
                    <input type="email" id="demo-email" class="form-control" value="<?php echo $isLoggedInCustomer ? htmlspecialchars($customer['email'], ENT_QUOTES, 'UTF-8') : DEMO_CUSTOMER_EMAIL; ?>" required>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Gender Aesthetic Preference</label>
                    <div style="display:flex; gap: var(--space-md); flex-wrap:wrap; font-size: 0.88rem;">
                      <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="radio" name="demo_gender" value="Unisex / Flexible" checked> Unisex / Flexible
                      </label>
                      <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="radio" name="demo_gender" value="Women's Styling Focus"> Women's Styling Focus
                      </label>
                      <label style="display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                        <input type="radio" name="demo_gender" value="Men's Barbering Focus"> Men's Barbering Focus
                      </label>
                    </div>
                  </div>

                  <div class="form-group">
                    <label for="demo-notes" class="form-label">Notes or Silent Appointment Request</label>
                    <textarea id="demo-notes" class="form-control" placeholder="Mention product sensitivities, quiet consultation request..."><?php echo $isLoggedInCustomer ? htmlspecialchars($customer['allergies_notes'] ?? '', ENT_QUOTES, 'UTF-8') : 'Silent appointment requested. Organic botanical washes.'; ?></textarea>
                  </div>

                  <div class="form-group">
                    <label style="display:inline-flex; align-items:center; gap:8px; cursor:pointer; font-size:0.88rem;">
                      <input type="checkbox" id="demo-silent" checked> 
                      <span>Request Silent Appointment (Tranquil, quiet service)</span>
                    </label>
                  </div>
                </div>

                <!-- Navigation Buttons -->
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top: var(--space-xl); padding-top: var(--space-md); border-top: 1px solid var(--color-charcoal-border);">
                  <button type="button" class="btn btn-outline" id="demo-prev-btn" style="display:none;">
                    &larr; Back
                  </button>

                  <div style="margin-left: auto; display: flex; gap: var(--space-sm);">
                    <button type="button" class="btn btn-primary" id="demo-next-btn">
                      <span>Continue</span>
                      <span class="btn-icon">&rarr;</span>
                    </button>
                    <button type="submit" class="btn btn-champagne" id="demo-submit-btn" style="display:none;">
                      <span>Confirm Demo Reservation</span>
                      <span class="btn-icon">&rarr;</span>
                    </button>
                  </div>
                </div>

              </form>
            </div>

            <!-- Live Summary Sidebar -->
            <aside class="booking-summary-sidebar" style="background-color: var(--color-charcoal); color: var(--color-ivory); padding: var(--space-2xl); border-radius: var(--radius-xs); position: sticky; top: 100px;">
              <span class="eyebrow" style="color:var(--color-champagne-light); margin-bottom:2px;">Live Reservation Card</span>
              <h3>Your Atelier Itinerary</h3>

              <div class="summary-row">
                <span class="label">Ritual:</span>
                <span class="val" id="sum-service-name">Artisanal Precision Haircut</span>
              </div>

              <div class="summary-row">
                <span class="label">Duration:</span>
                <span class="val" id="sum-duration">60 mins</span>
              </div>

              <div class="summary-row">
                <span class="label">Specialist:</span>
                <span class="val" id="sum-stylist-name">Any Available Master Specialist</span>
              </div>

              <div class="summary-row">
                <span class="label">Date:</span>
                <span class="val" id="sum-date"><?php echo date('Y-m-d', strtotime('+3 days')); ?></span>
              </div>

              <div class="summary-row">
                <span class="label">Time Slot:</span>
                <span class="val" id="sum-time">01:30 PM</span>
              </div>

              <div class="summary-total">
                <span class="total-label">Total Fee:</span>
                <span class="total-price" id="sum-price">$85.00</span>
              </div>

              <div style="margin-top: var(--space-md); padding-top: var(--space-sm); border-top: 1px dashed rgba(197, 164, 109, 0.25); font-size: 0.76rem; color: rgba(245, 239, 230, 0.78); line-height: 1.5;">
                <span style="color:var(--color-champagne-light); font-weight:600;">✦ Demo Storage:</span><br>
                This reservation will be saved into your active PHP session. You can review and cancel it inside the Customer Profile or Admin Console.
              </div>
            </aside>

          </div>
        </div>
      </div>
    </section>
  </main>

  <!-- CONFIRMATION MODAL -->
  <div id="demo-success-modal" class="custom-modal-backdrop" style="display:none; position:fixed; inset:0; z-index:99999; background:rgba(22,20,19,0.72); backdrop-filter:blur(6px); align-items:center; justify-content:center; padding:16px;">
    <div style="background:#FFFFFF; border-radius:var(--radius-xs); max-width:480px; width:100%; padding:30px 26px; box-shadow:0 20px 40px rgba(0,0,0,0.25); border-top:4px solid var(--color-champagne); text-align:center;">
      <div style="font-size:2.6rem; margin-bottom:8px;">✨</div>
      <span class="luxury-pill" style="margin-bottom:8px;">✦ Demo Reservation Confirmed</span>
      <h3 style="font-family:var(--font-serif); font-size:1.75rem; color:var(--color-charcoal); margin:8px 0 6px;" id="modal-ref-title">
        Ref: DEMO-2026-XXXXX
      </h3>
      <p style="color:var(--color-charcoal-muted); font-size:0.9rem; line-height:1.5; margin:0 0 20px 0;">
        Your appointment has been registered in demo session memory! You can inspect this booking immediately in the Customer Sanctuary or Admin Management Console.
      </p>

      <div style="background:var(--color-ivory); border-radius:var(--radius-xs); padding:14px; margin-bottom:20px; font-size:0.86rem; text-align:left; color:var(--color-charcoal);">
        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
          <span>Ritual:</span> <strong id="modal-service">Artisanal Precision Haircut</strong>
        </div>
        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
          <span>Schedule:</span> <strong id="modal-schedule">Date at Time</strong>
        </div>
        <div style="display:flex; justify-content:space-between; margin-bottom:6px;">
          <span>Specialist:</span> <strong id="modal-specialist">Clara Valmont</strong>
        </div>
        <div style="display:flex; justify-content:space-between;">
          <span>Fee:</span> <strong id="modal-fee" style="color:var(--color-champagne-dark); font-size:1.05rem;">$85.00</strong>
        </div>
      </div>

      <div style="display:flex; flex-direction:column; gap:10px;">
        <a href="customer-profile.php" class="btn btn-champagne" style="justify-content:center; width:100%;">
          <span>👤 View in Customer Profile</span>
          <span class="btn-icon">&rarr;</span>
        </a>
        <a href="admin-dashboard.php" class="btn btn-primary" style="justify-content:center; width:100%;">
          <span>👑 View in Admin Console</span>
          <span class="btn-icon">&rarr;</span>
        </a>
        <button type="button" onclick="closeDemoModal()" class="btn btn-outline" style="justify-content:center; width:100%;">
          Book Another Demo Ritual
        </button>
      </div>
    </div>
  </div>

  <script>
    // Demo Booking State
    const state = {
      serviceId: 'haircut',
      serviceName: 'Artisanal Precision Haircut',
      servicePrice: '$85.00',
      serviceDuration: '60 mins',
      stylistId: 'any',
      stylistName: 'Any Available Master Specialist',
      date: '<?php echo date('Y-m-d', strtotime('+3 days')); ?>',
      time: '01:30 PM'
    };

    let curStep = 1;
    const totalSteps = 4;
    const panels = document.querySelectorAll('.booking-step-panel');
    const indicators = document.querySelectorAll('.step-indicator');
    const btnNext = document.getElementById('demo-next-btn');
    const btnPrev = document.getElementById('demo-prev-btn');
    const btnSubmit = document.getElementById('demo-submit-btn');

    function updateSummary() {
      document.getElementById('sum-service-name').textContent = state.serviceName;
      document.getElementById('sum-duration').textContent = state.serviceDuration;
      document.getElementById('sum-stylist-name').textContent = state.stylistName;
      document.getElementById('sum-date').textContent = state.date;
      document.getElementById('sum-time').textContent = state.time;
      document.getElementById('sum-price').textContent = state.servicePrice;
    }

    function goToStep(s) {
      if (s < 1 || s > totalSteps) return;
      curStep = s;
      panels.forEach(p => p.style.display = parseInt(p.getAttribute('data-step')) === curStep ? 'block' : 'none');
      indicators.forEach(ind => {
        const iStep = parseInt(ind.getAttribute('data-step'));
        ind.classList.remove('active', 'completed');
        if (iStep === curStep) ind.classList.add('active');
        else if (iStep < curStep) ind.classList.add('completed');
      });
      btnPrev.style.display = curStep === 1 ? 'none' : 'inline-flex';
      btnNext.style.display = curStep === totalSteps ? 'none' : 'inline-flex';
      btnSubmit.style.display = curStep === totalSteps ? 'inline-flex' : 'none';
      window.scrollTo({ top: 200, behavior: 'smooth' });
    }

    btnNext.addEventListener('click', () => goToStep(curStep + 1));
    btnPrev.addEventListener('click', () => goToStep(curStep - 1));
    indicators.forEach(ind => ind.addEventListener('click', () => goToStep(parseInt(ind.getAttribute('data-step')))));

    // Service select
    document.querySelectorAll('.service-select-card').forEach(card => {
      card.addEventListener('click', () => {
        document.querySelectorAll('.service-select-card').forEach(c => c.classList.remove('selected'));
        card.classList.add('selected');
        const radio = card.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
        state.serviceId = card.getAttribute('data-service-id');
        state.serviceName = card.getAttribute('data-service-name');
        state.servicePrice = card.getAttribute('data-service-price');
        state.serviceDuration = card.getAttribute('data-service-duration');
        updateSummary();
      });
    });

    // Stylist select
    document.querySelectorAll('.stylist-select-card').forEach(card => {
      card.addEventListener('click', () => {
        document.querySelectorAll('.stylist-select-card').forEach(c => {
          c.classList.remove('selected');
          c.style.borderColor = 'var(--color-charcoal-border)';
          c.style.background = 'transparent';
        });
        card.classList.add('selected');
        card.style.borderColor = 'var(--color-champagne)';
        card.style.background = 'rgba(212,175,55,0.06)';
        const radio = card.querySelector('input[type="radio"]');
        if (radio) radio.checked = true;
        state.stylistId = card.getAttribute('data-stylist-id');
        state.stylistName = card.getAttribute('data-stylist-name');
        updateSummary();
      });
    });

    // Date & Time
    const dateInput = document.getElementById('demo-date');
    if (dateInput) {
      dateInput.addEventListener('change', () => {
        state.date = dateInput.value;
        updateSummary();
      });
    }

    document.querySelectorAll('.time-slot-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        if (btn.classList.contains('unavailable')) return;
        document.querySelectorAll('.time-slot-btn').forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        state.time = btn.getAttribute('data-time');
        updateSummary();
      });
    });

    // Form submission
    const form = document.getElementById('demo-booking-form');
    form.addEventListener('submit', (e) => {
      e.preventDefault();

      const name = document.getElementById('demo-name').value.trim();
      const phone = document.getElementById('demo-phone').value.trim();
      const email = document.getElementById('demo-email').value.trim();
      const notes = document.getElementById('demo-notes').value.trim();
      const silent = document.getElementById('demo-silent').checked ? 1 : 0;

      if (!name || !phone || !email) {
        alert('Please complete all required fields.');
        return;
      }

      btnSubmit.disabled = true;
      btnSubmit.innerHTML = '<span>Saving Demo Reservation...</span>';

      fetch('api/book.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          service_id: state.serviceId,
          stylist_id: state.stylistId,
          date: state.date,
          time: state.time,
          guest_name: name,
          guest_phone: phone,
          guest_email: email,
          notes: notes,
          silent_appointment: silent
        })
      })
      .then(r => r.json())
      .then(res => {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<span>Confirm Demo Reservation</span><span class="btn-icon">&rarr;</span>';

        if (res.success) {
          document.getElementById('modal-ref-title').textContent = 'Ref: ' + res.booking_reference;
          document.getElementById('modal-service').textContent = res.details.service_name;
          document.getElementById('modal-schedule').textContent = res.details.date + ' at ' + res.details.time;
          document.getElementById('modal-specialist').textContent = res.details.stylist_name;
          document.getElementById('modal-fee').textContent = res.details.estimated_price;
          document.getElementById('demo-success-modal').style.display = 'flex';
        } else {
          alert(res.error || 'Booking could not be processed.');
        }
      })
      .catch(() => {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<span>Confirm Demo Reservation</span><span class="btn-icon">&rarr;</span>';
        alert('Demo reservation registered in session.');
      });
    });

    function closeDemoModal() {
      document.getElementById('demo-success-modal').style.display = 'none';
      goToStep(1);
    }
  </script>

  <!-- FOOTER -->
  <footer style="background-color: var(--color-charcoal); color: var(--color-ivory); padding: var(--space-lg) 0; font-size: 0.85rem; text-align: center; border-top: 1px solid var(--color-champagne-border);">
    <div class="container">
      <p style="margin: 0; color: rgba(245,239,230,0.7);">
        &copy; <?php echo date('Y'); ?> L'Étoile Luxury Unisex Hair Salon &amp; Spa &bull; Isolated Demonstration Mode
      </p>
    </div>
  </footer>

</body>
</html>
