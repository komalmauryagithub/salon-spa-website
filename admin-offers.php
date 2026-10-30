<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Promotional Offers & Seasonal Packages Administration
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 1. Guard check: Administrator clearance required
Auth::requireRole(['admin', 'manager', 'receptionist'], 'admin-login.php');

$currentUser = Auth::user();
$activeAdminPage = 'offers';
$statusMessage = null;
$statusError   = null;

try {
    $pdo = Database::getConnection();

    // 2. Handle POST Actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? null;
        if (!Csrf::verify($token)) {
            $statusError = "Security token validation failed. Please refresh the page.";
        } else {
            $action = $_POST['action'] ?? '';

            // A. Create New Promotional Offer
            if ($action === 'create_offer') {
                $title       = trim((string)($_POST['title'] ?? ''));
                $rawSlug     = trim((string)($_POST['slug'] ?? ''));
                $badgeText   = trim((string)($_POST['badge_text'] ?? ''));
                $shortDesc   = trim((string)($_POST['short_description'] ?? ''));
                $origPrice   = (float)($_POST['original_price'] ?? 0.00);
                $packPrice   = (float)($_POST['package_price'] ?? 0.00);
                $duration    = (int)($_POST['estimated_duration_minutes'] ?? 90);
                $rawFeatures = trim((string)($_POST['included_features'] ?? ''));
                $startDate   = !empty($_POST['start_date']) ? trim((string)$_POST['start_date']) : null;
                $endDate     = !empty($_POST['end_date']) ? trim((string)$_POST['end_date']) : null;
                $isFeatured  = !empty($_POST['is_featured']) ? 1 : 0;
                $isActive    = !empty($_POST['is_active']) ? 1 : 0;

                // Parse newline-delimited features into JSON
                $featuresList = array_values(array_filter(array_map('trim', explode("\n", $rawFeatures))));
                $featuresJson = json_encode($featuresList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                if (empty($title)) {
                    $statusError = "Package title is required.";
                } elseif ($packPrice <= 0) {
                    $statusError = "Package price must be greater than zero.";
                } elseif ($duration < 15) {
                    $statusError = "Estimated duration must be at least 15 minutes.";
                } elseif ($startDate && $endDate && $startDate > $endDate) {
                    $statusError = "Offer end date cannot precede start date.";
                } else {
                    if (empty($rawSlug)) {
                        $rawSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
                    } else {
                        $rawSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $rawSlug), '-'));
                    }

                    $chk = $pdo->prepare("SELECT id FROM offers WHERE slug = :slug LIMIT 1");
                    $chk->execute(['slug' => $rawSlug]);
                    if ($chk->fetch()) {
                        $rawSlug .= '-' . time();
                    }

                    $ins = $pdo->prepare("
                        INSERT INTO offers (
                            slug, title, badge_text, short_description,
                            original_price, package_price, estimated_duration_minutes,
                            included_features, start_date, end_date,
                            is_featured, is_active, created_at, updated_at
                        ) VALUES (
                            :slug, :title, :badge, :short_desc,
                            :orig_price, :pack_price, :duration,
                            :features, :start_dt, :end_dt,
                            :featured, :active, NOW(), NOW()
                        )
                    ");
                    $ins->execute([
                        'slug'       => $rawSlug,
                        'title'      => $title,
                        'badge'      => !empty($badgeText) ? $badgeText : null,
                        'short_desc' => $shortDesc,
                        'orig_price' => $origPrice,
                        'pack_price' => $packPrice,
                        'duration'   => $duration,
                        'features'   => $featuresJson,
                        'start_dt'   => $startDate,
                        'end_dt'     => $endDate,
                        'featured'   => $isFeatured,
                        'active'     => $isActive
                    ]);

                    $statusMessage = "Promotional package '{$title}' created successfully.";
                    AppLogger::info("Admin #{$currentUser['id']} created package offer '{$title}' (#" . $pdo->lastInsertId() . ")");
                }

            // B. Edit Existing Promotional Offer
            } elseif ($action === 'edit_offer') {
                $offerId     = (int)($_POST['offer_id'] ?? 0);
                $title       = trim((string)($_POST['title'] ?? ''));
                $rawSlug     = trim((string)($_POST['slug'] ?? ''));
                $badgeText   = trim((string)($_POST['badge_text'] ?? ''));
                $shortDesc   = trim((string)($_POST['short_description'] ?? ''));
                $origPrice   = (float)($_POST['original_price'] ?? 0.00);
                $packPrice   = (float)($_POST['package_price'] ?? 0.00);
                $duration    = (int)($_POST['estimated_duration_minutes'] ?? 90);
                $rawFeatures = trim((string)($_POST['included_features'] ?? ''));
                $startDate   = !empty($_POST['start_date']) ? trim((string)$_POST['start_date']) : null;
                $endDate     = !empty($_POST['end_date']) ? trim((string)$_POST['end_date']) : null;
                $isFeatured  = !empty($_POST['is_featured']) ? 1 : 0;
                $isActive    = !empty($_POST['is_active']) ? 1 : 0;

                $featuresList = array_values(array_filter(array_map('trim', explode("\n", $rawFeatures))));
                $featuresJson = json_encode($featuresList, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                if ($offerId <= 0) {
                    $statusError = "Invalid offer target specified.";
                } elseif (empty($title)) {
                    $statusError = "Package title is required.";
                } elseif ($packPrice <= 0) {
                    $statusError = "Package price must be greater than zero.";
                } elseif ($startDate && $endDate && $startDate > $endDate) {
                    $statusError = "Offer end date cannot precede start date.";
                } else {
                    if (empty($rawSlug)) {
                        $rawSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));
                    } else {
                        $rawSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $rawSlug), '-'));
                    }

                    // Check slug uniqueness excluding self
                    $chk = $pdo->prepare("SELECT id FROM offers WHERE slug = :slug AND id != :id LIMIT 1");
                    $chk->execute(['slug' => $rawSlug, 'id' => $offerId]);
                    if ($chk->fetch()) {
                        $rawSlug .= '-' . time();
                    }

                    $up = $pdo->prepare("
                        UPDATE offers SET
                            slug                       = :slug,
                            title                      = :title,
                            badge_text                 = :badge,
                            short_description          = :short_desc,
                            original_price             = :orig_price,
                            package_price              = :pack_price,
                            estimated_duration_minutes = :duration,
                            included_features          = :features,
                            start_date                 = :start_dt,
                            end_date                   = :end_dt,
                            is_featured                = :featured,
                            is_active                  = :active,
                            updated_at                 = NOW()
                        WHERE id = :id
                    ");
                    $up->execute([
                        'slug'       => $rawSlug,
                        'title'      => $title,
                        'badge'      => !empty($badgeText) ? $badgeText : null,
                        'short_desc' => $shortDesc,
                        'orig_price' => $origPrice,
                        'pack_price' => $packPrice,
                        'duration'   => $duration,
                        'features'   => $featuresJson,
                        'start_dt'   => $startDate,
                        'end_dt'     => $endDate,
                        'featured'   => $isFeatured,
                        'active'     => $isActive,
                        'id'         => $offerId
                    ]);

                    $statusMessage = "Promotional package '{$title}' updated successfully.";
                    AppLogger::info("Admin #{$currentUser['id']} updated package offer #{$offerId}");
                }

            // C. Toggle Active Status
            } elseif ($action === 'toggle_active') {
                $offerId = (int)($_POST['offer_id'] ?? 0);
                $newActive = !empty($_POST['is_active']) ? 1 : 0;
                if ($offerId > 0) {
                    $upStmt = $pdo->prepare("UPDATE offers SET is_active = :act, updated_at = NOW() WHERE id = :id");
                    $upStmt->execute(['act' => $newActive, 'id' => $offerId]);
                    $statusMessage = "Offer availability status updated.";
                    AppLogger::info("Admin #{$currentUser['id']} set offer #{$offerId} active to {$newActive}");
                }
            }
        }
    }

    // 3. Load All Promotional Offers with Booking Counts
    $sql = "
        SELECT 
            o.id,
            o.slug,
            o.title,
            o.badge_text,
            o.short_description,
            o.original_price,
            o.package_price,
            o.estimated_duration_minutes,
            o.included_features,
            o.start_date,
            o.end_date,
            o.is_featured,
            o.is_active,
            COUNT(a.id) AS total_bookings,
            COALESCE(SUM(CASE WHEN a.status IN ('confirmed', 'completed') THEN a.total_price ELSE 0 END), 0) AS total_revenue
        FROM offers o
        LEFT JOIN appointments a ON o.id = a.offer_id
        GROUP BY o.id
        ORDER BY o.is_active DESC, o.package_price ASC
    ";
    $offers = $pdo->query($sql)->fetchAll();

    $activeOffersCount = count(array_filter($offers, fn($o) => (int)$o['is_active'] === 1));

} catch (Exception $e) {
    AppLogger::error("Offers administration error: " . $e->getMessage());
    $offers = [];
    $activeOffersCount = 0;
    $statusError = "Could not load promotional offers from database.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Offers &amp; Packages Administration — L'ÉTOILE Luxury Hair Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="assets/css/style.css?v=3.5">
  <link rel="stylesheet" href="assets/css/responsive.css?v=3.5">
  <style>
    .kpi-card {
      background: var(--color-white);
      border: 1px solid var(--color-charcoal-border);
      border-radius: var(--radius-xs);
      padding: var(--space-lg);
      transition: border-color var(--transition-fast);
    }
    .kpi-card:hover {
      border-color: var(--color-champagne);
    }
    .kpi-val {
      font-family: var(--font-serif);
      font-size: 2.2rem;
      font-weight: 600;
      color: var(--color-charcoal);
      line-height: 1.1;
      margin-top: 4px;
    }
    .kpi-label {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: var(--color-charcoal-muted);
    }
    .admin-table {
      width: 100%;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.88rem;
    }
    .admin-table th {
      background: var(--color-ivory);
      color: var(--color-charcoal);
      font-family: var(--font-serif);
      font-size: 0.95rem;
      font-weight: 600;
      padding: 12px 16px;
      border-bottom: 1px solid var(--color-charcoal-border);
      white-space: nowrap;
    }
    .admin-table td {
      padding: 14px 16px;
      border-bottom: 1px solid var(--color-charcoal-border);
      vertical-align: middle;
    }
    .admin-table tr:hover {
      background-color: rgba(245, 239, 230, 0.4);
    }
    .badge-status {
      display: inline-block;
      padding: 2px 8px;
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      border-radius: 12px;
    }
    .badge-active {
      background: #F4FAF5;
      color: #1E5128;
      border: 1px solid #8FD19E;
    }
    .badge-inactive {
      background: #FFF5F5;
      color: #9E2A2B;
      border: 1px solid rgba(201,83,83,0.3);
    }
    .btn-action-sm {
      display: inline-block;
      padding: 4px 10px;
      font-size: 0.78rem;
      border-radius: 2px;
      cursor: pointer;
      border: 1px solid var(--color-champagne);
      background: transparent;
      color: var(--color-champagne-dark);
      text-decoration: none;
      font-weight: 600;
    }
    .btn-action-sm:hover {
      background: var(--color-champagne);
      color: var(--color-white);
    }
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(37,35,33,0.7);
      backdrop-filter: blur(4px);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .modal-dialog {
      background: #FFFFFF;
      border: 1px solid var(--color-champagne);
      border-radius: var(--radius-xs);
      max-width: 620px;
      width: 100%;
      padding: var(--space-2xl);
      box-shadow: var(--shadow-elevated);
      max-height: 90vh;
      overflow-y: auto;
    }
  </style>
</head>
<body style="background-color: var(--color-ivory);">

  <!-- SHARED ADMIN NAVIGATION -->
  <?php require_once __DIR__ . '/includes/admin-nav.php'; ?>

  <!-- HERO SECTION -->
  <section style="background-color: var(--color-white); border-bottom: 1px solid var(--color-charcoal-border); padding: var(--space-xl) 0;">
    <div class="container">
      <div style="display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Promotional Marketing</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.2rem; color: var(--color-charcoal); margin: 4px 0 6px;">
            Offers &amp; Seasonal Packages
          </h1>
          <p style="color: var(--color-charcoal-muted); margin: 0; font-size: 0.95rem;">
            Curate multi-service suites, seasonal promotional rates, validity periods, and booking discounts.
          </p>
        </div>
        <div>
          <button type="button" id="btnOpenNewOffer" class="btn btn-champagne btn-sm">+ Add Promotional Package</button>
        </div>
      </div>

      <!-- KPI METRICS -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:var(--space-md); margin-top:var(--space-xl);">
        
        <div class="kpi-card">
          <div class="kpi-label">Active Packages</div>
          <div class="kpi-val" style="color:#1E5128;"><?php echo $activeOffersCount; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Available for appointment reservation</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Total Suites in Catalog</div>
          <div class="kpi-val"><?php echo count($offers); ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Executive, bridal &amp; wellness duos</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Realized Package Revenue</div>
          <div class="kpi-val" style="color:var(--color-champagne-dark);">
            $<?php 
              $totRev = array_sum(array_column($offers, 'total_revenue'));
              echo number_format((float)$totRev, 2); 
            ?>
          </div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">From confirmed &amp; completed package rituals</div>
        </div>

      </div>

    </div>
  </section>

  <!-- MAIN OFFERS CONTENT -->
  <main style="padding: var(--space-3xl) 0 var(--space-4xl);">
    <div class="container">

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

      <!-- OFFERS TABLE -->
      <div class="table-responsive" style="background:var(--color-white); border:1px solid var(--color-charcoal-border); border-radius:var(--radius-xs); box-shadow:var(--shadow-subtle); overflow-x:auto;">
        <?php if (empty($offers)): ?>
          <div style="text-align:center; padding:40px 20px;">
            <p style="color:var(--color-charcoal-muted); margin-bottom:12px;">No promotional packages have been configured yet.</p>
            <button type="button" id="btnOpenNewOfferEmpty" class="btn btn-champagne btn-sm">+ Create Your First Offer</button>
          </div>
        <?php else: ?>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Package Title &amp; Badge</th>
                <th>Duration</th>
                <th>Pricing &amp; Savings</th>
                <th>Validity Window</th>
                <th>Bookings</th>
                <th>Revenue</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($offers as $off): ?>
                <?php 
                  $isActive = (int)$off['is_active'] === 1;
                  $savings  = (float)$off['original_price'] > (float)$off['package_price'] ? ((float)$off['original_price'] - (float)$off['package_price']) : 0;
                  
                  // Format features for edit modal
                  $featsArr = [];
                  if (!empty($off['included_features'])) {
                      $decoded = json_decode($off['included_features'], true);
                      if (is_array($decoded)) {
                          $featsArr = $decoded;
                      }
                  }
                  $featsNewline = implode("\n", $featsArr);

                  $offPayload = [
                      'id'                         => $off['id'],
                      'slug'                       => $off['slug'],
                      'title'                      => $off['title'],
                      'badge_text'                 => $off['badge_text'],
                      'short_description'          => $off['short_description'],
                      'original_price'             => $off['original_price'],
                      'package_price'              => $off['package_price'],
                      'estimated_duration_minutes' => $off['estimated_duration_minutes'],
                      'included_features'          => $featsNewline,
                      'start_date'                 => $off['start_date'],
                      'end_date'                   => $off['end_date'],
                      'is_featured'                => $off['is_featured'],
                      'is_active'                  => $off['is_active']
                  ];
                  $jsonPayload = htmlspecialchars(json_encode($offPayload), ENT_QUOTES, 'UTF-8');
                ?>
                <tr>
                  <td>
                    <div style="font-weight:600; font-size:0.95rem; color:var(--color-charcoal);">
                      <?php echo e($off['title']); ?>
                      <?php if (!empty($off['badge_text'])): ?>
                        <span style="font-size:0.72rem; padding:2px 8px; border-radius:12px; background:var(--color-champagne-subtle); color:var(--color-champagne-dark); font-weight:700; border:1px solid var(--color-champagne-border); margin-left:6px;">
                          <?php echo e($off['badge_text']); ?>
                        </span>
                      <?php endif; ?>
                    </div>
                    <div style="font-size:0.78rem; color:var(--color-charcoal-muted); margin-top:2px;">
                      <?php echo e($off['short_description']); ?>
                    </div>
                  </td>
                  <td>
                    <strong><?php echo (int)$off['estimated_duration_minutes']; ?></strong> mins
                  </td>
                  <td>
                    <div style="font-weight:700; color:var(--color-champagne-dark); font-size:1.05rem;">
                      $<?php echo number_format((float)$off['package_price'], 2); ?>
                    </div>
                    <?php if ($savings > 0): ?>
                      <div style="font-size:0.75rem; color:var(--color-charcoal-muted);">
                        Orig: <del>$<?php echo number_format((float)$off['original_price'], 2); ?></del> &bull; Save $<?php echo number_format($savings, 2); ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($off['start_date'] || $off['end_date']): ?>
                      <span style="font-size:0.8rem; color:var(--color-charcoal);">
                        <?php echo $off['start_date'] ? date('M j, Y', strtotime($off['start_date'])) : 'Open'; ?> 
                        &rarr; 
                        <?php echo $off['end_date'] ? date('M j, Y', strtotime($off['end_date'])) : 'No Expiry'; ?>
                      </span>
                    <?php else: ?>
                      <span style="font-size:0.78rem; color:var(--color-charcoal-muted);">Ongoing Offer</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong><?php echo (int)$off['total_bookings']; ?></strong>
                  </td>
                  <td>
                    <strong style="color:#1E5128;">
                      $<?php echo number_format((float)$off['total_revenue'], 2); ?>
                    </strong>
                  </td>
                  <td>
                    <form action="admin-offers.php" method="POST" style="display:inline;">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="toggle_active">
                      <input type="hidden" name="offer_id" value="<?php echo (int)$off['id']; ?>">
                      <input type="hidden" name="is_active" value="<?php echo $isActive ? '0' : '1'; ?>">
                      <button type="submit" class="badge-status <?php echo $isActive ? 'badge-active' : 'badge-inactive'; ?>" style="border:none; cursor:pointer;" title="Click to toggle availability">
                        <?php echo $isActive ? 'Active' : 'Inactive'; ?>
                      </button>
                    </form>
                  </td>
                  <td>
                    <button type="button" class="btn-action-sm btn-edit-offer" data-offer="<?php echo $jsonPayload; ?>">
                      Edit
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

    </div>
  </main>

  <!-- CREATE OFFER MODAL -->
  <div id="createOfferModal" class="modal-overlay">
    <div class="modal-dialog">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Promotional Curation</span>
          <h3 style="font-family:var(--font-serif); font-size:1.5rem; margin:2px 0 0;">Add Promotional Package</h3>
        </div>
        <button type="button" class="btn-close-modal" data-target="createOfferModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
      </div>

      <form action="admin-offers.php" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="create_offer">

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Package Title *</label>
            <input type="text" name="title" class="form-control" required placeholder="e.g. The Spring Radiance Duo">
          </div>
          <div>
            <label class="form-label">Badge Tag</label>
            <input type="text" name="badge_text" class="form-control" placeholder="e.g. Most Requested">
          </div>
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Short Description *</label>
          <input type="text" name="short_description" class="form-control" required placeholder="Editorial summary for client package cards">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Package Price ($) *</label>
            <input type="number" step="0.01" min="1" name="package_price" class="form-control" required placeholder="220.00">
          </div>
          <div>
            <label class="form-label">Original Value ($)</label>
            <input type="number" step="0.01" min="0" name="original_price" class="form-control" placeholder="265.00">
          </div>
          <div>
            <label class="form-label">Duration (Mins) *</label>
            <input type="number" step="15" min="15" max="600" name="estimated_duration_minutes" class="form-control" value="120" required>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Start Date (Optional)</label>
            <input type="date" name="start_date" class="form-control">
          </div>
          <div>
            <label class="form-label">End Date (Optional)</label>
            <input type="date" name="end_date" class="form-control">
          </div>
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Included Features (One per line)</label>
          <textarea name="included_features" class="form-control" rows="3" placeholder="Precision Geometric Haircut & Velvet Blowout&#10;Japanese Micro-Mist Head Spa Waterfall&#10;Cellular Cryo Radiance Facial"></textarea>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px;">
          <div style="display:flex; gap:16px;">
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
              <input type="checkbox" name="is_active" value="1" checked> Active in Booking
            </label>
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
              <input type="checkbox" name="is_featured" value="1"> Feature on Packages Page
            </label>
          </div>
          <div style="display:flex; gap:8px;">
            <button type="button" class="btn btn-outline btn-sm btn-close-modal" data-target="createOfferModal">Cancel</button>
            <button type="submit" class="btn btn-champagne btn-sm">Create Package</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT OFFER MODAL -->
  <div id="editOfferModal" class="modal-overlay">
    <div class="modal-dialog">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Promotional Maintenance</span>
          <h3 style="font-family:var(--font-serif); font-size:1.5rem; margin:2px 0 0;" id="editOfferHeading">Edit Promotional Package</h3>
        </div>
        <button type="button" class="btn-close-modal" data-target="editOfferModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
      </div>

      <form action="admin-offers.php" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="edit_offer">
        <input type="hidden" name="offer_id" id="edit_offer_id" value="">

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Package Title *</label>
            <input type="text" name="title" id="edit_title" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Badge Tag</label>
            <input type="text" name="badge_text" id="edit_badge_text" class="form-control">
          </div>
        </div>

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Slug</label>
            <input type="text" name="slug" id="edit_slug" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Duration (Mins) *</label>
            <input type="number" step="15" min="15" max="600" name="estimated_duration_minutes" id="edit_duration" class="form-control" required>
          </div>
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Short Description *</label>
          <input type="text" name="short_description" id="edit_short_description" class="form-control" required>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Package Price ($) *</label>
            <input type="number" step="0.01" min="1" name="package_price" id="edit_package_price" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Original Value ($)</label>
            <input type="number" step="0.01" min="0" name="original_price" id="edit_original_price" class="form-control">
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Start Date (Optional)</label>
            <input type="date" name="start_date" id="edit_start_date" class="form-control">
          </div>
          <div>
            <label class="form-label">End Date (Optional)</label>
            <input type="date" name="end_date" id="edit_end_date" class="form-control">
          </div>
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Included Features (One per line)</label>
          <textarea name="included_features" id="edit_included_features" class="form-control" rows="3"></textarea>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:16px;">
          <div style="display:flex; gap:16px;">
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
              <input type="checkbox" name="is_active" id="edit_is_active" value="1"> Active in Booking
            </label>
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
              <input type="checkbox" name="is_featured" id="edit_is_featured" value="1"> Feature on Packages Page
            </label>
          </div>
          <div style="display:flex; gap:8px;">
            <button type="button" class="btn btn-outline btn-sm btn-close-modal" data-target="editOfferModal">Cancel</button>
            <button type="submit" class="btn btn-champagne btn-sm">Update Package</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- FOOTER -->
  <footer class="site-footer" role="contentinfo">
    <div class="container footer-bottom-inner">
      <p>&copy; <?php echo date('Y'); ?> L'ÉTOILE Luxury Unisex Hair Salon &amp; Spa &bull; Concierge Operations Panel.</p>
    </div>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Create Modal
      const btnNew = document.getElementById('btnOpenNewOffer');
      const btnNewEmpty = document.getElementById('btnOpenNewOfferEmpty');
      const createModal = document.getElementById('createOfferModal');
      if (btnNew && createModal) {
        btnNew.addEventListener('click', () => createModal.style.display = 'flex');
      }
      if (btnNewEmpty && createModal) {
        btnNewEmpty.addEventListener('click', () => createModal.style.display = 'flex');
      }

      // Close Modals
      document.querySelectorAll('.btn-close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
          const targetId = btn.getAttribute('data-target');
          const targetModal = document.getElementById(targetId);
          if (targetModal) targetModal.style.display = 'none';
        });
      });

      document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
          if (e.target === modal) modal.style.display = 'none';
        });
      });

      // Edit Modal Population
      const editModal = document.getElementById('editOfferModal');
      document.querySelectorAll('.btn-edit-offer').forEach(btn => {
        btn.addEventListener('click', () => {
          try {
            const data = JSON.parse(btn.getAttribute('data-offer'));
            document.getElementById('edit_offer_id').value = data.id;
            document.getElementById('edit_title').value = data.title || '';
            document.getElementById('edit_slug').value = data.slug || '';
            document.getElementById('edit_badge_text').value = data.badge_text || '';
            document.getElementById('edit_short_description').value = data.short_description || '';
            document.getElementById('edit_original_price').value = data.original_price || '';
            document.getElementById('edit_package_price').value = data.package_price || '';
            document.getElementById('edit_duration').value = data.estimated_duration_minutes || '90';
            document.getElementById('edit_start_date').value = data.start_date || '';
            document.getElementById('edit_end_date').value = data.end_date || '';
            document.getElementById('edit_included_features').value = data.included_features || '';
            document.getElementById('edit_is_active').checked = parseInt(data.is_active) === 1;
            document.getElementById('edit_is_featured').checked = parseInt(data.is_featured) === 1;
            document.getElementById('editOfferHeading').textContent = 'Edit ' + data.title;

            editModal.style.display = 'flex';
          } catch (e) {
            alert('Could not parse offer details.');
          }
        });
      });
    });
  </script>

</body>
</html>
