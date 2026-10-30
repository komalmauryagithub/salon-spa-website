<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Services, Rituals & Curated Categories Administration
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 1. Guard check: Administrator clearance required
Auth::requireRole(['admin', 'manager', 'receptionist'], 'admin-login.php');

$currentUser = Auth::user();
$activeAdminPage = 'services';
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

            // A. Create New Service
            if ($action === 'create_service') {
                $name        = trim((string)($_POST['name'] ?? ''));
                $categoryId  = (int)($_POST['category_id'] ?? 0);
                $rawSlug     = trim((string)($_POST['slug'] ?? ''));
                $shortDesc   = trim((string)($_POST['short_description'] ?? ''));
                $fullDesc    = trim((string)($_POST['full_description'] ?? ''));
                $genderSuit  = trim((string)($_POST['gender_suitability'] ?? 'Unisex'));
                $duration    = (int)($_POST['duration_minutes'] ?? 60);
                $price       = (float)($_POST['price'] ?? 0.00);
                $imageUrl    = trim((string)($_POST['image_url'] ?? ''));
                $isFeatured  = !empty($_POST['is_featured']) ? 1 : 0;
                $isActive    = !empty($_POST['is_active']) ? 1 : 0;
                $order       = (int)($_POST['display_order'] ?? 0);

                // Auto slug if empty
                if (empty($rawSlug)) {
                    $rawSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
                } else {
                    $rawSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $rawSlug), '-'));
                }

                // Validation
                if (empty($name)) {
                    $statusError = "Please specify a service ritual name.";
                } elseif ($categoryId <= 0) {
                    $statusError = "Please select a valid service category.";
                } elseif ($duration < 15 || $duration > 480) {
                    $statusError = "Duration must be between 15 and 480 minutes.";
                } elseif ($price <= 0) {
                    $statusError = "Price must be greater than zero.";
                } else {
                    // Check slug uniqueness
                    $chk = $pdo->prepare("SELECT id FROM services WHERE slug = :slug LIMIT 1");
                    $chk->execute(['slug' => $rawSlug]);
                    if ($chk->fetch()) {
                        $rawSlug .= '-' . time();
                    }

                    $ins = $pdo->prepare("
                        INSERT INTO services (
                            category_id, slug, name, short_description, full_description,
                            gender_suitability, duration_minutes, price, image_url,
                            is_featured, is_active, display_order, created_at, updated_at
                        ) VALUES (
                            :cat, :slug, :name, :short_desc, :full_desc,
                            :gender, :duration, :price, :img,
                            :featured, :active, :ord, NOW(), NOW()
                        )
                    ");
                    $ins->execute([
                        'cat'        => $categoryId,
                        'slug'       => $rawSlug,
                        'name'       => $name,
                        'short_desc' => $shortDesc,
                        'full_desc'  => !empty($fullDesc) ? $fullDesc : null,
                        'gender'     => in_array($genderSuit, ['Unisex', 'Women', 'Men', 'Customized'], true) ? $genderSuit : 'Unisex',
                        'duration'   => $duration,
                        'price'      => $price,
                        'img'        => !empty($imageUrl) ? $imageUrl : null,
                        'featured'   => $isFeatured,
                        'active'     => $isActive,
                        'ord'        => $order
                    ]);

                    $statusMessage = "New service '{$name}' created successfully in catalog.";
                    AppLogger::info("Admin #{$currentUser['id']} created service '{$name}' (#" . $pdo->lastInsertId() . ")");
                }

            // B. Edit Existing Service
            } elseif ($action === 'edit_service') {
                $serviceId   = (int)($_POST['service_id'] ?? 0);
                $name        = trim((string)($_POST['name'] ?? ''));
                $categoryId  = (int)($_POST['category_id'] ?? 0);
                $rawSlug     = trim((string)($_POST['slug'] ?? ''));
                $shortDesc   = trim((string)($_POST['short_description'] ?? ''));
                $fullDesc    = trim((string)($_POST['full_description'] ?? ''));
                $genderSuit  = trim((string)($_POST['gender_suitability'] ?? 'Unisex'));
                $duration    = (int)($_POST['duration_minutes'] ?? 60);
                $price       = (float)($_POST['price'] ?? 0.00);
                $imageUrl    = trim((string)($_POST['image_url'] ?? ''));
                $isFeatured  = !empty($_POST['is_featured']) ? 1 : 0;
                $isActive    = !empty($_POST['is_active']) ? 1 : 0;
                $order       = (int)($_POST['display_order'] ?? 0);

                if ($serviceId <= 0) {
                    $statusError = "Invalid service target specified.";
                } elseif (empty($name)) {
                    $statusError = "Please specify a service ritual name.";
                } elseif ($categoryId <= 0) {
                    $statusError = "Please select a valid service category.";
                } elseif ($duration < 15 || $duration > 480) {
                    $statusError = "Duration must be between 15 and 480 minutes.";
                } elseif ($price <= 0) {
                    $statusError = "Price must be greater than zero.";
                } else {
                    if (empty($rawSlug)) {
                        $rawSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));
                    } else {
                        $rawSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $rawSlug), '-'));
                    }

                    // Check slug uniqueness excluding self
                    $chk = $pdo->prepare("SELECT id FROM services WHERE slug = :slug AND id != :id LIMIT 1");
                    $chk->execute(['slug' => $rawSlug, 'id' => $serviceId]);
                    if ($chk->fetch()) {
                        $rawSlug .= '-' . time();
                    }

                    $up = $pdo->prepare("
                        UPDATE services SET
                            category_id        = :cat,
                            slug               = :slug,
                            name               = :name,
                            short_description  = :short_desc,
                            full_description   = :full_desc,
                            gender_suitability = :gender,
                            duration_minutes   = :duration,
                            price              = :price,
                            image_url          = :img,
                            is_featured        = :featured,
                            is_active          = :active,
                            display_order      = :ord,
                            updated_at         = NOW()
                        WHERE id = :id
                    ");
                    $up->execute([
                        'cat'        => $categoryId,
                        'slug'       => $rawSlug,
                        'name'       => $name,
                        'short_desc' => $shortDesc,
                        'full_desc'  => !empty($fullDesc) ? $fullDesc : null,
                        'gender'     => in_array($genderSuit, ['Unisex', 'Women', 'Men', 'Customized'], true) ? $genderSuit : 'Unisex',
                        'duration'   => $duration,
                        'price'      => $price,
                        'img'        => !empty($imageUrl) ? $imageUrl : null,
                        'featured'   => $isFeatured,
                        'active'     => $isActive,
                        'ord'        => $order,
                        'id'         => $serviceId
                    ]);

                    $statusMessage = "Service '{$name}' updated successfully.";
                    AppLogger::info("Admin #{$currentUser['id']} updated service #{$serviceId}");
                }

            // C. Toggle Active (Preserves historical appointment linkage!)
            } elseif ($action === 'toggle_active') {
                $serviceId = (int)($_POST['service_id'] ?? 0);
                $newActive = !empty($_POST['is_active']) ? 1 : 0;
                if ($serviceId > 0) {
                    $up = $pdo->prepare("UPDATE services SET is_active = :act, updated_at = NOW() WHERE id = :id");
                    $up->execute(['act' => $newActive, 'id' => $serviceId]);
                    $statusMessage = $newActive ? "Service activated and visible for new appointments." : "Service deactivated. Historical reservations and reports remain fully preserved.";
                    AppLogger::info("Admin #{$currentUser['id']} set service #{$serviceId} active to {$newActive}");
                }

            // D. Toggle Featured
            } elseif ($action === 'toggle_featured') {
                $serviceId = (int)($_POST['service_id'] ?? 0);
                $newFeatured = !empty($_POST['is_featured']) ? 1 : 0;
                if ($serviceId > 0) {
                    $up = $pdo->prepare("UPDATE services SET is_featured = :feat, updated_at = NOW() WHERE id = :id");
                    $up->execute(['feat' => $newFeatured, 'id' => $serviceId]);
                    $statusMessage = "Featured homepage highlight status updated.";
                    AppLogger::info("Admin #{$currentUser['id']} set service #{$serviceId} featured to {$newFeatured}");
                }

            // E. Create Category
            } elseif ($action === 'create_category') {
                $catName = trim((string)($_POST['category_name'] ?? ''));
                $catDesc = trim((string)($_POST['category_description'] ?? ''));
                $catSlug = trim((string)($_POST['category_slug'] ?? ''));
                $catOrder = (int)($_POST['display_order'] ?? 0);

                if (empty($catName)) {
                    $statusError = "Category name is required.";
                } else {
                    if (empty($catSlug)) {
                        $catSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $catName), '-'));
                    }
                    $chkCat = $pdo->prepare("SELECT id FROM service_categories WHERE slug = :slug LIMIT 1");
                    $chkCat->execute(['slug' => $catSlug]);
                    if ($chkCat->fetch()) {
                        $catSlug .= '-' . time();
                    }

                    $insCat = $pdo->prepare("
                        INSERT INTO service_categories (slug, name, description, display_order, is_active, created_at)
                        VALUES (:slug, :name, :desc, :ord, 1, NOW())
                    ");
                    $insCat->execute([
                        'slug' => $catSlug,
                        'name' => $catName,
                        'desc' => !empty($catDesc) ? $catDesc : null,
                        'ord'  => $catOrder
                    ]);
                    $statusMessage = "New service category '{$catName}' added.";
                    AppLogger::info("Admin #{$currentUser['id']} created category '{$catName}'");
                }
            }
        }
    }

    // 3. Load Categories & Services
    $catStmt = $pdo->query("SELECT id, slug, name, description, display_order FROM service_categories ORDER BY display_order ASC, name ASC");
    $categories = $catStmt->fetchAll();

    // Filtering
    $filterCat = trim((string)($_GET['cat'] ?? 'all'));
    $searchQ   = trim((string)($_GET['q'] ?? ''));

    $where = ["1=1"];
    $params = [];

    if ($filterCat !== 'all' && is_numeric($filterCat)) {
        $where[] = "s.category_id = :filter_cat";
        $params['filter_cat'] = (int)$filterCat;
    }
    if (!empty($searchQ)) {
        $where[] = "(s.name LIKE :search OR s.short_description LIKE :search OR s.slug LIKE :search)";
        $params['search'] = "%{$searchQ}%";
    }

    $whereSql = implode(' AND ', $where);

    $servicesStmt = $pdo->prepare("
        SELECT 
            s.id,
            s.category_id,
            s.slug,
            s.name,
            s.short_description,
            s.full_description,
            s.gender_suitability,
            s.duration_minutes,
            s.price,
            s.image_url,
            s.is_featured,
            s.is_active,
            s.display_order,
            sc.name AS category_name,
            COUNT(a.id) AS booking_count,
            COALESCE(SUM(CASE WHEN a.status IN ('confirmed', 'completed') THEN a.total_price ELSE 0 END), 0) AS total_revenue
        FROM services s
        JOIN service_categories sc ON s.category_id = sc.id
        LEFT JOIN appointments a ON s.id = a.service_id
        WHERE {$whereSql}
        GROUP BY s.id
        ORDER BY sc.display_order ASC, s.display_order ASC, s.name ASC
    ");
    $servicesStmt->execute($params);
    $services = $servicesStmt->fetchAll();

    // 4. Overall Statistics
    $statRow = $pdo->query("
        SELECT 
            COUNT(*) AS total_count,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count,
            AVG(price) AS average_price
        FROM services
    ")->fetch();

    $totalServices = (int)($statRow['total_count'] ?? 0);
    $activeCount   = (int)($statRow['active_count'] ?? 0);
    $avgPrice      = (float)($statRow['average_price'] ?? 0);

} catch (Exception $e) {
    AppLogger::error("Services administration error: " . $e->getMessage());
    $categories = [];
    $services = [];
    $statusError = "Could not load services catalog from database.";
    $totalServices = 0; $activeCount = 0; $avgPrice = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Services &amp; Rituals Menu — L'ÉTOILE Luxury Hair Salon &amp; Spa</title>
  <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
  
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Stylesheets -->
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
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
    .service-category-block {
      background: var(--color-white);
      border: 1px solid var(--color-charcoal-border);
      border-radius: var(--radius-xs);
      margin-bottom: var(--space-2xl);
      box-shadow: var(--shadow-subtle);
      overflow-x: auto;
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
          <span class="eyebrow eyebrow-no-line">Ritual Menu Catalog</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.2rem; color: var(--color-charcoal); margin: 4px 0 6px;">
            Services &amp; Treatment Offerings
          </h1>
          <p style="color: var(--color-charcoal-muted); margin: 0; font-size: 0.95rem;">
            Create, edit, and deactivate salon rituals. Historical bookings remain permanently linked.
          </p>
        </div>
        <div style="display:flex; gap:8px;">
          <button type="button" id="btnOpenNewCategory" class="btn btn-outline btn-sm">+ Add Category</button>
          <button type="button" id="btnOpenNewService" class="btn btn-champagne btn-sm">+ Add New Service</button>
        </div>
      </div>

      <!-- KPI METRICS -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:var(--space-md); margin-top:var(--space-xl);">
        
        <div class="kpi-card">
          <div class="kpi-label">Active Rituals</div>
          <div class="kpi-val" style="color:#1E5128;"><?php echo $activeCount; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Available for online booking</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Average Base Price</div>
          <div class="kpi-val" style="color:var(--color-champagne-dark);">
            $<?php echo number_format($avgPrice, 2); ?>
          </div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Across full treatment catalog</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Catalog Portfolio</div>
          <div class="kpi-val"><?php echo $totalServices; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Across <?php echo count($categories); ?> curated categories</div>
        </div>

      </div>

    </div>
  </section>

  <!-- MAIN CATALOG CONTENT -->
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

      <!-- FILTER & SEARCH BAR -->
      <div style="background:#FFFFFF; border:1px solid var(--color-charcoal-border); padding:var(--space-md) var(--space-lg); border-radius:var(--radius-xs); margin-bottom:var(--space-xl); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <form action="admin-services.php" method="GET" style="display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
          <div>
            <select name="cat" class="form-control" style="font-size:0.85rem; padding:6px 10px;" onchange="this.form.submit()">
              <option value="all">All Categories (<?php echo count($categories); ?>)</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo (int)$cat['id']; ?>" <?php echo $filterCat === (string)$cat['id'] ? 'selected' : ''; ?>>
                  <?php echo e($cat['name']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <input type="text" name="q" placeholder="Search service name, slug..." value="<?php echo e($searchQ); ?>" class="form-control" style="font-size:0.85rem; padding:6px 12px; width:220px;">
          </div>
          <button type="submit" class="btn btn-outline btn-sm">Filter</button>
          <?php if ($filterCat !== 'all' || !empty($searchQ)): ?>
            <a href="admin-services.php" class="btn btn-sm" style="color:var(--color-charcoal-muted);">Clear</a>
          <?php endif; ?>
        </form>

        <div style="font-size:0.85rem; color:var(--color-charcoal-muted);">
          Showing <strong><?php echo count($services); ?></strong> matching services
        </div>
      </div>

      <!-- SERVICES TABLE -->
      <div class="service-category-block">
        <?php if (empty($services)): ?>
          <div style="text-align:center; padding:40px 20px;">
            <p style="color:var(--color-charcoal-muted); margin-bottom:12px;">No services match your active filter.</p>
            <a href="admin-services.php" class="btn btn-outline btn-sm">Reset Filter</a>
          </div>
        <?php else: ?>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Service / Ritual</th>
                <th>Category</th>
                <th>Duration</th>
                <th>Base Price</th>
                <th>Suitability</th>
                <th>Bookings</th>
                <th>Revenue</th>
                <th>Status</th>
                <th>Featured</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($services as $srv): ?>
                <?php 
                  $isActive = (int)$srv['is_active'] === 1;
                  $isFeat   = (int)$srv['is_featured'] === 1;
                  $jsonSrv  = htmlspecialchars(json_encode($srv), ENT_QUOTES, 'UTF-8');
                ?>
                <tr>
                  <td>
                    <div style="font-weight:600; color:var(--color-charcoal); font-size:0.92rem;">
                      <?php echo e($srv['name']); ?>
                    </div>
                    <div style="font-size:0.75rem; color:var(--color-charcoal-muted); font-family:monospace;">
                      /<?php echo e($srv['slug']); ?>
                    </div>
                  </td>
                  <td>
                    <span style="font-size:0.82rem; color:var(--color-charcoal);">
                      <?php echo e($srv['category_name']); ?>
                    </span>
                  </td>
                  <td>
                    <strong><?php echo (int)$srv['duration_minutes']; ?></strong> mins
                  </td>
                  <td>
                    <strong style="color:var(--color-champagne-dark); font-size:0.95rem;">
                      $<?php echo number_format((float)$srv['price'], 2); ?>
                    </strong>
                  </td>
                  <td>
                    <span style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.04em; color:var(--color-charcoal-muted);">
                      <?php echo e($srv['gender_suitability']); ?>
                    </span>
                  </td>
                  <td>
                    <span style="font-weight:600;"><?php echo (int)$srv['booking_count']; ?></span>
                  </td>
                  <td>
                    <strong style="color:#1E5128;">
                      $<?php echo number_format((float)$srv['total_revenue'], 2); ?>
                    </strong>
                  </td>
                  <td>
                    <form action="admin-services.php" method="POST" style="display:inline;">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="toggle_active">
                      <input type="hidden" name="service_id" value="<?php echo (int)$srv['id']; ?>">
                      <input type="hidden" name="is_active" value="<?php echo $isActive ? '0' : '1'; ?>">
                      <button type="submit" class="badge-status <?php echo $isActive ? 'badge-active' : 'badge-inactive'; ?>" style="border:none; cursor:pointer;" title="Click to toggle activation">
                        <?php echo $isActive ? 'Active' : 'Deactivated'; ?>
                      </button>
                    </form>
                  </td>
                  <td>
                    <form action="admin-services.php" method="POST" style="display:inline;">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="toggle_featured">
                      <input type="hidden" name="service_id" value="<?php echo (int)$srv['id']; ?>">
                      <input type="hidden" name="is_featured" value="<?php echo $isFeat ? '0' : '1'; ?>">
                      <button type="submit" style="background:none; border:none; cursor:pointer; font-size:1.1rem; color:<?php echo $isFeat ? 'var(--color-champagne-dark)' : '#CBD5E1'; ?>;" title="Toggle homepage highlight">
                        &#9733;
                      </button>
                    </form>
                  </td>
                  <td>
                    <div style="display:flex; gap:6px;">
                      <button type="button" class="btn-action-sm btn-edit-service" data-service="<?php echo $jsonSrv; ?>">
                        Edit
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>

    </div>
  </main>

  <!-- CREATE SERVICE MODAL -->
  <div id="createServiceModal" class="modal-overlay">
    <div class="modal-dialog">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Catalog Administration</span>
          <h3 style="font-family:var(--font-serif); font-size:1.5rem; margin:2px 0 0;">Create New Service Ritual</h3>
        </div>
        <button type="button" class="btn-close-modal" data-target="createServiceModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
      </div>

      <form action="admin-services.php" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="create_service">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Service Name *</label>
            <input type="text" name="name" class="form-control" required placeholder="e.g. Balayage Sculpting">
          </div>
          <div>
            <label class="form-label">Category *</label>
            <select name="category_id" class="form-control" required>
              <option value="">Select Category...</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo (int)$cat['id']; ?>"><?php echo e($cat['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Base Price ($) *</label>
            <input type="number" step="0.01" min="1" name="price" class="form-control" required placeholder="95.00">
          </div>
          <div>
            <label class="form-label">Duration (Mins) *</label>
            <input type="number" step="5" min="15" max="480" name="duration_minutes" class="form-control" value="60" required>
          </div>
          <div>
            <label class="form-label">Suitability</label>
            <select name="gender_suitability" class="form-control">
              <option value="Unisex">Unisex</option>
              <option value="Women">Women</option>
              <option value="Men">Men</option>
              <option value="Customized">Customized</option>
            </select>
          </div>
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Short Description *</label>
          <input type="text" name="short_description" class="form-control" required placeholder="One-line editorial summary for cards & booking">
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Full Description</label>
          <textarea name="full_description" class="form-control" rows="3" placeholder="Full treatment breakdown, botanicals, technique..."></textarea>
        </div>

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-bottom:14px;">
          <div>
            <label class="form-label">Image URL</label>
            <input type="url" name="image_url" class="form-control" placeholder="https://images.unsplash.com/...">
          </div>
          <div>
            <label class="form-label">Display Order</label>
            <input type="number" name="display_order" class="form-control" value="0">
          </div>
        </div>

        <div style="display:flex; gap:16px; margin-bottom:16px;">
          <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
            <input type="checkbox" name="is_active" value="1" checked> Active in Booking Menu
          </label>
          <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
            <input type="checkbox" name="is_featured" value="1"> Feature on Homepage
          </label>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" class="btn btn-outline btn-sm btn-close-modal" data-target="createServiceModal">Cancel</button>
          <button type="submit" class="btn btn-champagne btn-sm">Save Service</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT SERVICE MODAL -->
  <div id="editServiceModal" class="modal-overlay">
    <div class="modal-dialog">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Modify Ritual</span>
          <h3 style="font-family:var(--font-serif); font-size:1.5rem; margin:2px 0 0;" id="editModalHeading">Edit Service</h3>
        </div>
        <button type="button" class="btn-close-modal" data-target="editServiceModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
      </div>

      <form action="admin-services.php" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="edit_service">
        <input type="hidden" name="service_id" id="edit_service_id" value="">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Service Name *</label>
            <input type="text" name="name" id="edit_name" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Category *</label>
            <select name="category_id" id="edit_category_id" class="form-control" required>
              <?php foreach ($categories as $cat): ?>
                <option value="<?php echo (int)$cat['id']; ?>"><?php echo e($cat['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Base Price ($) *</label>
            <input type="number" step="0.01" min="1" name="price" id="edit_price" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Duration (Mins) *</label>
            <input type="number" step="5" min="15" max="480" name="duration_minutes" id="edit_duration_minutes" class="form-control" required>
          </div>
          <div>
            <label class="form-label">Suitability</label>
            <select name="gender_suitability" id="edit_gender_suitability" class="form-control">
              <option value="Unisex">Unisex</option>
              <option value="Women">Women</option>
              <option value="Men">Men</option>
              <option value="Customized">Customized</option>
            </select>
          </div>
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Service Slug</label>
          <input type="text" name="slug" id="edit_slug" class="form-control" required>
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Short Description *</label>
          <input type="text" name="short_description" id="edit_short_description" class="form-control" required>
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Full Description</label>
          <textarea name="full_description" id="edit_full_description" class="form-control" rows="3"></textarea>
        </div>

        <div style="display:grid; grid-template-columns:2fr 1fr; gap:12px; margin-bottom:14px;">
          <div>
            <label class="form-label">Image URL</label>
            <input type="url" name="image_url" id="edit_image_url" class="form-control">
          </div>
          <div>
            <label class="form-label">Display Order</label>
            <input type="number" name="display_order" id="edit_display_order" class="form-control">
          </div>
        </div>

        <div style="display:flex; gap:16px; margin-bottom:16px;">
          <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
            <input type="checkbox" name="is_active" id="edit_is_active" value="1"> Active in Booking Menu
          </label>
          <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
            <input type="checkbox" name="is_featured" id="edit_is_featured" value="1"> Feature on Homepage
          </label>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" class="btn btn-outline btn-sm btn-close-modal" data-target="editServiceModal">Cancel</button>
          <button type="submit" class="btn btn-champagne btn-sm">Update Service</button>
        </div>
      </form>
    </div>
  </div>

  <!-- CREATE CATEGORY MODAL -->
  <div id="createCategoryModal" class="modal-overlay">
    <div class="modal-dialog" style="max-width:480px;">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Classification</span>
          <h3 style="font-family:var(--font-serif); font-size:1.4rem; margin:2px 0 0;">Add Service Category</h3>
        </div>
        <button type="button" class="btn-close-modal" data-target="createCategoryModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
      </div>

      <form action="admin-services.php" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="create_category">

        <div style="margin-bottom:12px;">
          <label class="form-label">Category Name *</label>
          <input type="text" name="category_name" class="form-control" required placeholder="e.g. Japanese Scalp Spa">
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Description</label>
          <textarea name="category_description" class="form-control" rows="2" placeholder="Brief category summary..."></textarea>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:14px;">
          <div>
            <label class="form-label">Slug (Optional)</label>
            <input type="text" name="category_slug" class="form-control" placeholder="auto-generated">
          </div>
          <div>
            <label class="form-label">Display Order</label>
            <input type="number" name="display_order" class="form-control" value="0">
          </div>
        </div>

        <div style="display:flex; justify-content:flex-end; gap:8px;">
          <button type="button" class="btn btn-outline btn-sm btn-close-modal" data-target="createCategoryModal">Cancel</button>
          <button type="submit" class="btn btn-champagne btn-sm">Create Category</button>
        </div>
      </form>
    </div>
  </div>

  <!-- FOOTER -->
  <footer class="site-footer" role="contentinfo">
    <div class="container footer-bottom-inner">
      <p>&copy; <?php echo date('Y'); ?> L'Étoile Luxury Unisex Hair Salon &amp; Spa &bull; Concierge Operations Panel.</p>
    </div>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      // Open Create Modals
      const btnNewService = document.getElementById('btnOpenNewService');
      const modalCreateService = document.getElementById('createServiceModal');
      if (btnNewService && modalCreateService) {
        btnNewService.addEventListener('click', () => {
          modalCreateService.style.display = 'flex';
        });
      }

      const btnNewCategory = document.getElementById('btnOpenNewCategory');
      const modalCreateCat = document.getElementById('createCategoryModal');
      if (btnNewCategory && modalCreateCat) {
        btnNewCategory.addEventListener('click', () => {
          modalCreateCat.style.display = 'flex';
        });
      }

      // Close Modals
      document.querySelectorAll('.btn-close-modal').forEach(btn => {
        btn.addEventListener('click', () => {
          const targetId = btn.getAttribute('data-target');
          const targetModal = document.getElementById(targetId);
          if (targetModal) targetModal.style.display = 'none';
        });
      });

      // Close on backdrop click
      document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', (e) => {
          if (e.target === modal) modal.style.display = 'none';
        });
      });

      // Edit Service Modal Population
      const editModal = document.getElementById('editServiceModal');
      document.querySelectorAll('.btn-edit-service').forEach(btn => {
        btn.addEventListener('click', () => {
          try {
            const data = JSON.parse(btn.getAttribute('data-service'));
            document.getElementById('edit_service_id').value = data.id;
            document.getElementById('edit_name').value = data.name || '';
            document.getElementById('edit_slug').value = data.slug || '';
            document.getElementById('edit_category_id').value = data.category_id || '';
            document.getElementById('edit_price').value = data.price || '';
            document.getElementById('edit_duration_minutes').value = data.duration_minutes || '60';
            document.getElementById('edit_gender_suitability').value = data.gender_suitability || 'Unisex';
            document.getElementById('edit_short_description').value = data.short_description || '';
            document.getElementById('edit_full_description').value = data.full_description || '';
            document.getElementById('edit_image_url').value = data.image_url || '';
            document.getElementById('edit_display_order').value = data.display_order || '0';
            document.getElementById('edit_is_active').checked = parseInt(data.is_active) === 1;
            document.getElementById('edit_is_featured').checked = parseInt(data.is_featured) === 1;
            document.getElementById('editModalHeading').textContent = 'Edit ' + data.name;

            editModal.style.display = 'flex';
          } catch (e) {
            alert('Could not parse service data for editing.');
          }
        });
      });
    });
  </script>

</body>
</html>
