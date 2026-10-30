<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Visual Portfolio & Gallery Showcase Administration
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 1. Guard check: Administrator clearance required
Auth::requireRole(['admin', 'manager', 'receptionist'], 'admin-login.php');

$currentUser = Auth::user();
$activeAdminPage = 'gallery';
$statusMessage = null;
$statusError   = null;

$categories = [
    'haircuts' => 'Haircuts & Architecture',
    'colour'   => 'Couture Colour & Balayage',
    'spa'      => 'Japanese Head Spa & Facials',
    'interior' => 'Atelier Interior & Suites'
];

try {
    $pdo = Database::getConnection();

    // 2. Handle POST Actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? null;
        if (!Csrf::verify($token)) {
            $statusError = "Security token validation failed. Please refresh the page.";
        } else {
            $action = $_POST['action'] ?? '';

            // A. Upload / Add Gallery Image
            if ($action === 'create_image') {
                $title      = trim((string)($_POST['title'] ?? ''));
                $category   = trim((string)($_POST['category'] ?? 'haircuts'));
                $altText    = trim((string)($_POST['alt_text'] ?? ''));
                $imageUrl   = trim((string)($_POST['image_url'] ?? ''));
                $order      = (int)($_POST['display_order'] ?? 0);
                $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;
                $isActive   = !empty($_POST['is_active']) ? 1 : 0;

                $uploadedPath = null;

                // Handle file upload if provided
                if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $file = $_FILES['image_file'];

                    if ($file['error'] !== UPLOAD_ERR_OK) {
                        $statusError = "File upload error occurred (Code: {$file['error']}).";
                    } else {
                        // 1. File size limit: 5MB
                        $maxBytes = 5 * 1024 * 1024;
                        if ($file['size'] > $maxBytes) {
                            $statusError = "Image file size exceeds the 5MB luxury limit.";
                        } else {
                            // 2. Extension validation
                            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
                            if (!in_array($ext, $allowedExts, true)) {
                                $statusError = "Invalid file extension. Only JPG, PNG, WEBP, and GIF images are accepted.";
                            } else {
                                // 3. MIME type inspection via Fileinfo
                                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                                $mime = finfo_file($finfo, $file['tmp_name']);
                                finfo_close($finfo);

                                $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                                if (!in_array($mime, $allowedMimes, true)) {
                                    $statusError = "Security verification failed. Uploaded file is not a valid image.";
                                } else {
                                    // 4. getimagesize check (verifies real image bytes, prevents executable polyglots)
                                    $imgInfo = @getimagesize($file['tmp_name']);
                                    if ($imgInfo === false) {
                                        $statusError = "File corrupt or not an authentic image.";
                                    } else {
                                        // 5. Generate secure random filename
                                        $randomFilename = 'letoile_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
                                        $uploadDir = __DIR__ . '/uploads/gallery/';
                                        if (!is_dir($uploadDir)) {
                                            @mkdir($uploadDir, 0755, true);
                                        }
                                        $targetDest = $uploadDir . $randomFilename;

                                        if (move_uploaded_file($file['tmp_name'], $targetDest)) {
                                            $uploadedPath = 'uploads/gallery/' . $randomFilename;
                                        } else {
                                            $statusError = "Could not save uploaded image to gallery storage.";
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $finalImageUrl = $uploadedPath ?: $imageUrl;

                if (!$statusError) {
                    if (empty($title)) {
                        $statusError = "Please specify an image title.";
                    } elseif (empty($finalImageUrl)) {
                        $statusError = "Please upload an image file or provide an image URL.";
                    } else {
                        if (empty($altText)) {
                            $altText = $title . ' - L\'Étoile Atelier';
                        }

                        $ins = $pdo->prepare("
                            INSERT INTO gallery_images (
                                category, title, image_url, alt_text, display_order, is_featured, is_active, created_at
                            ) VALUES (
                                :cat, :title, :url, :alt, :ord, :feat, :act, NOW()
                            )
                        ");
                        $ins->execute([
                            'cat'   => in_array($category, array_keys($categories), true) ? $category : 'haircuts',
                            'title' => $title,
                            'url'   => $finalImageUrl,
                            'alt'   => $altText,
                            'ord'   => $order,
                            'feat'  => $isFeatured,
                            'act'   => $isActive
                        ]);

                        $statusMessage = "Gallery photograph '{$title}' added successfully.";
                        AppLogger::info("Admin #{$currentUser['id']} added gallery image '{$title}' (#" . $pdo->lastInsertId() . ")");
                    }
                }

            // B. Edit Gallery Image Details
            } elseif ($action === 'edit_image') {
                $imageId    = (int)($_POST['image_id'] ?? 0);
                $title      = trim((string)($_POST['title'] ?? ''));
                $category   = trim((string)($_POST['category'] ?? 'haircuts'));
                $altText    = trim((string)($_POST['alt_text'] ?? ''));
                $imageUrl   = trim((string)($_POST['image_url'] ?? ''));
                $order      = (int)($_POST['display_order'] ?? 0);
                $isFeatured = !empty($_POST['is_featured']) ? 1 : 0;
                $isActive   = !empty($_POST['is_active']) ? 1 : 0;

                if ($imageId <= 0) {
                    $statusError = "Invalid image target specified.";
                } elseif (empty($title)) {
                    $statusError = "Title is required.";
                } else {
                    $up = $pdo->prepare("
                        UPDATE gallery_images SET
                            category      = :cat,
                            title         = :title,
                            image_url     = COALESCE(NULLIF(:url, ''), image_url),
                            alt_text      = :alt,
                            display_order = :ord,
                            is_featured   = :feat,
                            is_active     = :act
                        WHERE id = :id
                    ");
                    $up->execute([
                        'cat'   => in_array($category, array_keys($categories), true) ? $category : 'haircuts',
                        'title' => $title,
                        'url'   => $imageUrl,
                        'alt'   => $altText,
                        'ord'   => $order,
                        'feat'  => $isFeatured,
                        'act'   => $isActive,
                        'id'    => $imageId
                    ]);

                    $statusMessage = "Gallery photograph details updated.";
                    AppLogger::info("Admin #{$currentUser['id']} updated gallery image #{$imageId}");
                }

            // C. Toggle Active
            } elseif ($action === 'toggle_active') {
                $imageId = (int)($_POST['image_id'] ?? 0);
                $newActive = !empty($_POST['is_active']) ? 1 : 0;
                if ($imageId > 0) {
                    $upStmt = $pdo->prepare("UPDATE gallery_images SET is_active = :act WHERE id = :id");
                    $upStmt->execute(['act' => $newActive, 'id' => $imageId]);
                    $statusMessage = "Gallery image visibility updated.";
                }

            // D. Delete Gallery Image
            } elseif ($action === 'delete_image') {
                $imageId = (int)($_POST['image_id'] ?? 0);
                if ($imageId > 0) {
                    // Check if local file to unlink
                    $sel = $pdo->prepare("SELECT image_url FROM gallery_images WHERE id = :id LIMIT 1");
                    $sel->execute(['id' => $imageId]);
                    $row = $sel->fetch();
                    if ($row && !empty($row['image_url']) && str_starts_with($row['image_url'], 'uploads/gallery/')) {
                        $localPath = __DIR__ . '/' . $row['image_url'];
                        if (file_exists($localPath)) {
                            @unlink($localPath);
                        }
                    }

                    $del = $pdo->prepare("DELETE FROM gallery_images WHERE id = :id");
                    $del->execute(['id' => $imageId]);
                    $statusMessage = "Photograph removed from portfolio.";
                    AppLogger::info("Admin #{$currentUser['id']} deleted gallery image #{$imageId}");
                }
            }
        }
    }

    // 3. Load Gallery Images with Filters
    $filterCat = trim((string)($_GET['category'] ?? 'all'));

    $where = ["1=1"];
    $params = [];
    if ($filterCat !== 'all' && in_array($filterCat, array_keys($categories), true)) {
        $where[] = "category = :cat";
        $params['cat'] = $filterCat;
    }
    $whereSql = implode(' AND ', $where);

    $stmt = $pdo->prepare("
        SELECT id, category, title, image_url, alt_text, display_order, is_featured, is_active, created_at
        FROM gallery_images
        WHERE {$whereSql}
        ORDER BY display_order ASC, created_at DESC
    ");
    $stmt->execute($params);
    $images = $stmt->fetchAll();

    // Stats
    $totalCount = (int)$pdo->query("SELECT COUNT(*) FROM gallery_images")->fetchColumn();
    $activeCount = (int)$pdo->query("SELECT COUNT(*) FROM gallery_images WHERE is_active = 1")->fetchColumn();
    $featuredCount = (int)$pdo->query("SELECT COUNT(*) FROM gallery_images WHERE is_featured = 1")->fetchColumn();

} catch (Exception $e) {
    AppLogger::error("Gallery administration error: " . $e->getMessage());
    $images = [];
    $totalCount = 0; $activeCount = 0; $featuredCount = 0;
    $statusError = "Could not load gallery photographs from database.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gallery &amp; Portfolio Administration — L'ÉTOILE Luxury Hair Salon &amp; Spa</title>
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
    .gallery-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: var(--space-xl);
    }
    .gallery-card {
      background: var(--color-white);
      border: 1px solid var(--color-charcoal-border);
      border-radius: var(--radius-xs);
      overflow: hidden;
      box-shadow: var(--shadow-subtle);
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .gallery-thumb {
      width: 100%;
      height: 220px;
      object-fit: cover;
      background: #F1F1F1;
      display: block;
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
      max-width: 560px;
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
          <span class="eyebrow eyebrow-no-line">Portfolio Curation</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.2rem; color: var(--color-charcoal); margin: 4px 0 6px;">
            Gallery &amp; Visual Showcase
          </h1>
          <p style="color: var(--color-charcoal-muted); margin: 0; font-size: 0.95rem;">
            Upload and moderate high-resolution photography. Validated by MIME type, file size and extension.
          </p>
        </div>
        <div>
          <button type="button" id="btnOpenNewImage" class="btn btn-champagne btn-sm">+ Upload Photograph</button>
        </div>
      </div>

      <!-- KPI METRICS -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:var(--space-md); margin-top:var(--space-xl);">
        
        <div class="kpi-card">
          <div class="kpi-label">Active Showcase</div>
          <div class="kpi-val" style="color:#1E5128;"><?php echo $activeCount; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Visible on public portfolio page</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Featured Highlights</div>
          <div class="kpi-val" style="color:var(--color-champagne-dark);"><?php echo $featuredCount; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Homepage editorial highlights</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Total Portfolio Items</div>
          <div class="kpi-val"><?php echo $totalCount; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Across 4 creative categories</div>
        </div>

      </div>

    </div>
  </section>

  <!-- MAIN GALLERY CONTENT -->
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

      <!-- CATEGORY FILTER TABS -->
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:var(--space-xl);">
        <div style="display:flex; gap:6px; flex-wrap:wrap;">
          <a href="admin-gallery.php" class="btn btn-sm <?php echo $filterCat === 'all' ? 'btn-champagne' : 'btn-outline'; ?>">
            All (<?php echo $totalCount; ?>)
          </a>
          <?php foreach ($categories as $catKey => $catLabel): ?>
            <a href="admin-gallery.php?category=<?php echo $catKey; ?>" class="btn btn-sm <?php echo $filterCat === $catKey ? 'btn-champagne' : 'btn-outline'; ?>">
              <?php echo e($catLabel); ?>
            </a>
          <?php endforeach; ?>
        </div>

        <div style="font-size:0.85rem; color:var(--color-charcoal-muted);">
          Displaying <strong><?php echo count($images); ?></strong> photograph<?php echo count($images) === 1 ? '' : 's'; ?>
        </div>
      </div>

      <!-- GALLERY GRID -->
      <?php if (empty($images)): ?>
        <div style="background:var(--color-white); border:1px solid var(--color-charcoal-border); padding:40px 20px; text-align:center; border-radius:var(--radius-xs);">
          <p style="color:var(--color-charcoal-muted); margin-bottom:12px;">No photographs found in this category.</p>
          <a href="admin-gallery.php" class="btn btn-outline btn-sm">Reset Filter</a>
        </div>
      <?php else: ?>
        <div class="gallery-grid">
          <?php foreach ($images as $img): ?>
            <?php 
              $isActive = (int)$img['is_active'] === 1;
              $isFeat   = (int)$img['is_featured'] === 1;
              $jsonPayload = htmlspecialchars(json_encode($img), ENT_QUOTES, 'UTF-8');
            ?>
            <div class="gallery-card">
              <div>
                <img src="<?php echo e($img['image_url']); ?>" alt="<?php echo e($img['alt_text']); ?>" class="gallery-thumb" loading="lazy">
                <div style="padding:var(--space-md);">
                  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                    <span style="font-size:0.72rem; text-transform:uppercase; letter-spacing:0.06em; color:var(--color-champagne-dark); font-weight:700;">
                      <?php echo e($categories[$img['category']] ?? $img['category']); ?>
                    </span>
                    <div>
                      <?php if ($isFeat): ?>
                        <span style="color:var(--color-champagne-dark); font-size:1.1rem;" title="Featured on Homepage">&#9733;</span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <h4 style="font-family:var(--font-serif); font-size:1.2rem; margin:0 0 4px; color:var(--color-charcoal);">
                    <?php echo e($img['title']); ?>
                  </h4>
                  <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-bottom:6px;">
                    Alt: <?php echo e($img['alt_text']); ?>
                  </div>
                </div>
              </div>

              <!-- CARD ACTIONS -->
              <div style="padding:var(--space-md); border-top:1px solid var(--color-charcoal-border); display:flex; justify-content:space-between; align-items:center; background:var(--color-ivory);">
                <div>
                  <form action="admin-gallery.php" method="POST" style="display:inline;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="toggle_active">
                    <input type="hidden" name="image_id" value="<?php echo (int)$img['id']; ?>">
                    <input type="hidden" name="is_active" value="<?php echo $isActive ? '0' : '1'; ?>">
                    <button type="submit" class="badge-status <?php echo $isActive ? 'badge-active' : 'badge-inactive'; ?>" style="border:none; cursor:pointer;" title="Toggle visibility">
                      <?php echo $isActive ? 'Active' : 'Hidden'; ?>
                    </button>
                  </form>
                </div>

                <div style="display:flex; gap:6px;">
                  <button type="button" class="btn-action-sm btn-edit-image" data-image="<?php echo $jsonPayload; ?>">
                    Edit
                  </button>

                  <form action="admin-gallery.php" method="POST" style="display:inline;" onsubmit="return confirm('Remove this image from the gallery portfolio?');">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="delete_image">
                    <input type="hidden" name="image_id" value="<?php echo (int)$img['id']; ?>">
                    <button type="submit" class="btn-action-sm" style="color:#9E2A2B; border-color:#9E2A2B;">
                      Delete
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

    </div>
  </main>

  <!-- UPLOAD IMAGE MODAL -->
  <div id="createImageModal" class="modal-overlay">
    <div class="modal-dialog">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Portfolio Asset</span>
          <h3 style="font-family:var(--font-serif); font-size:1.5rem; margin:2px 0 0;">Upload Salon Photograph</h3>
        </div>
        <button type="button" class="btn-close-modal" data-target="createImageModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
      </div>

      <form action="admin-gallery.php" method="POST" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="create_image">

        <div style="margin-bottom:12px;">
          <label class="form-label">Photograph Title *</label>
          <input type="text" name="title" class="form-control" required placeholder="e.g. Soft Champagne Balayage Showcase">
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Category *</label>
            <select name="category" class="form-control" required>
              <?php foreach ($categories as $ck => $cv): ?>
                <option value="<?php echo $ck; ?>"><?php echo e($cv); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Display Order</label>
            <input type="number" name="display_order" class="form-control" value="0">
          </div>
        </div>

        <div style="margin-bottom:12px; background:var(--color-ivory); padding:12px; border-radius:2px; border:1px dashed var(--color-champagne-border);">
          <label class="form-label" style="margin-bottom:4px;">Upload Image File (Max 5MB)</label>
          <input type="file" name="image_file" class="form-control" accept="image/jpeg,image/png,image/webp,image/gif" style="padding:6px; font-size:0.85rem;">
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">
            MIME types verified via Fileinfo. Script execution blocked.
          </div>
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Or External Image URL</label>
          <input type="url" name="image_url" class="form-control" placeholder="https://images.unsplash.com/...">
        </div>

        <div style="margin-bottom:14px;">
          <label class="form-label">Accessible Alt Description</label>
          <input type="text" name="alt_text" class="form-control" placeholder="Descriptive text for accessibility & SEO">
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center;">
          <div style="display:flex; gap:16px;">
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
              <input type="checkbox" name="is_active" value="1" checked> Active in Portfolio
            </label>
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
              <input type="checkbox" name="is_featured" value="1"> Feature on Homepage
            </label>
          </div>
          <div style="display:flex; gap:8px;">
            <button type="button" class="btn btn-outline btn-sm btn-close-modal" data-target="createImageModal">Cancel</button>
            <button type="submit" class="btn btn-champagne btn-sm">Save Photograph</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT IMAGE MODAL -->
  <div id="editImageModal" class="modal-overlay">
    <div class="modal-dialog">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Portfolio Asset</span>
          <h3 style="font-family:var(--font-serif); font-size:1.5rem; margin:2px 0 0;" id="editImageHeading">Edit Photograph</h3>
        </div>
        <button type="button" class="btn-close-modal" data-target="editImageModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
      </div>

      <form action="admin-gallery.php" method="POST">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="edit_image">
        <input type="hidden" name="image_id" id="edit_image_id" value="">

        <div style="margin-bottom:12px;">
          <label class="form-label">Photograph Title *</label>
          <input type="text" name="title" id="edit_title" class="form-control" required>
        </div>

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Category *</label>
            <select name="category" id="edit_category" class="form-control" required>
              <?php foreach ($categories as $ck => $cv): ?>
                <option value="<?php echo $ck; ?>"><?php echo e($cv); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Display Order</label>
            <input type="number" name="display_order" id="edit_display_order" class="form-control">
          </div>
        </div>

        <div style="margin-bottom:12px;">
          <label class="form-label">Image URL</label>
          <input type="text" name="image_url" id="edit_image_url" class="form-control">
        </div>

        <div style="margin-bottom:14px;">
          <label class="form-label">Accessible Alt Description</label>
          <input type="text" name="alt_text" id="edit_alt_text" class="form-control">
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center;">
          <div style="display:flex; gap:16px;">
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
              <input type="checkbox" name="is_active" id="edit_is_active" value="1"> Active in Portfolio
            </label>
            <label style="display:flex; align-items:center; gap:6px; font-size:0.85rem; cursor:pointer;">
              <input type="checkbox" name="is_featured" id="edit_is_featured" value="1"> Feature on Homepage
            </label>
          </div>
          <div style="display:flex; gap:8px;">
            <button type="button" class="btn btn-outline btn-sm btn-close-modal" data-target="editImageModal">Cancel</button>
            <button type="submit" class="btn btn-champagne btn-sm">Update Image</button>
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
      const btnNew = document.getElementById('btnOpenNewImage');
      const createModal = document.getElementById('createImageModal');
      if (btnNew && createModal) {
        btnNew.addEventListener('click', () => createModal.style.display = 'flex');
      }

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

      const editModal = document.getElementById('editImageModal');
      document.querySelectorAll('.btn-edit-image').forEach(btn => {
        btn.addEventListener('click', () => {
          try {
            const data = JSON.parse(btn.getAttribute('data-image'));
            document.getElementById('edit_image_id').value = data.id;
            document.getElementById('edit_title').value = data.title || '';
            document.getElementById('edit_category').value = data.category || 'haircuts';
            document.getElementById('edit_image_url').value = data.image_url || '';
            document.getElementById('edit_alt_text').value = data.alt_text || '';
            document.getElementById('edit_display_order').value = data.display_order || '0';
            document.getElementById('edit_is_active').checked = parseInt(data.is_active) === 1;
            document.getElementById('edit_is_featured').checked = parseInt(data.is_featured) === 1;
            document.getElementById('editImageHeading').textContent = 'Edit ' + data.title;

            editModal.style.display = 'flex';
          } catch (e) {
            alert('Could not parse image data.');
          }
        });
      });
    });
  </script>

</body>
</html>
