<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Customer Reviews & Reflections Moderation Console
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 1. Guard check: Administrator clearance required
Auth::requireRole(['admin', 'manager', 'receptionist'], 'admin-login.php');

$currentUser = Auth::user();
$activeAdminPage = 'reviews';
$statusMessage = null;
$statusError   = null;

try {
    $pdo = Database::getConnection();

    // 2. Handle POST Moderation Actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? null;
        if (!Csrf::verify($token)) {
            $statusError = "Security token validation failed. Please refresh the page.";
        } else {
            $action   = $_POST['action'] ?? '';
            $reviewId = (int)($_POST['review_id'] ?? 0);

            if ($reviewId > 0) {
                if ($action === 'approve') {
                    $up = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = :id");
                    $up->execute(['id' => $reviewId]);
                    $statusMessage = "Review #{$reviewId} approved and is now visible on public testimonials.";
                    AppLogger::info("Admin #{$currentUser['id']} approved review #{$reviewId}");

                } elseif ($action === 'reject') {
                    $up = $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = :id");
                    $up->execute(['id' => $reviewId]);
                    $statusMessage = "Review #{$reviewId} rejected. It will not appear on public pages.";
                    AppLogger::info("Admin #{$currentUser['id']} rejected review #{$reviewId}");

                } elseif ($action === 'toggle_featured') {
                    $newFeat = !empty($_POST['is_featured']) ? 1 : 0;
                    $up = $pdo->prepare("UPDATE reviews SET is_featured = :feat WHERE id = :id");
                    $up->execute(['feat' => $newFeat, 'id' => $reviewId]);
                    $statusMessage = "Featured testimonial highlight status updated.";
                    AppLogger::info("Admin #{$currentUser['id']} toggled featured on review #{$reviewId}");

                } elseif ($action === 'delete') {
                    $del = $pdo->prepare("DELETE FROM reviews WHERE id = :id");
                    $del->execute(['id' => $reviewId]);
                    $statusMessage = "Review #{$reviewId} removed from database.";
                    AppLogger::info("Admin #{$currentUser['id']} deleted review #{$reviewId}");
                }
            }
        }
    }

    // 3. Status Filter & Search
    $filterStatus = trim((string)($_GET['status'] ?? 'all'));
    $searchQ      = trim((string)($_GET['q'] ?? ''));

    $where = ["1=1"];
    $params = [];

    if ($filterStatus !== 'all' && in_array($filterStatus, ['pending', 'approved', 'rejected'], true)) {
        $where[] = "r.status = :st";
        $params['st'] = $filterStatus;
    }

    if (!empty($searchQ)) {
        $where[] = "(r.guest_name LIKE :search OR r.guest_email LIKE :search OR r.review_title LIKE :search OR r.review_text LIKE :search)";
        $params['search'] = "%{$searchQ}%";
    }

    $whereSql = implode(' AND ', $where);

    // 4. Load Reviews with Service & Staff Joins
    $sql = "
        SELECT 
            r.id,
            r.customer_id,
            r.service_id,
            r.staff_id,
            r.guest_name,
            r.guest_email,
            r.rating,
            r.review_title,
            r.review_text,
            r.status,
            r.is_featured,
            r.created_at,
            s.name AS service_name,
            stf.full_name AS staff_name
        FROM reviews r
        LEFT JOIN services s ON r.service_id = s.id
        LEFT JOIN staff stf ON r.staff_id = stf.id
        WHERE {$whereSql}
        ORDER BY 
            CASE WHEN r.status = 'pending' THEN 0 ELSE 1 END,
            r.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reviews = $stmt->fetchAll();

    // 5. Counters
    $kpis = $pdo->query("
        SELECT 
            COUNT(*) AS total_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved_count,
            SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected_count,
            AVG(CASE WHEN status = 'approved' THEN rating ELSE NULL END) AS avg_rating
        FROM reviews
    ")->fetch();

    $totalReviews   = (int)($kpis['total_count'] ?? 0);
    $pendingReviews = (int)($kpis['pending_count'] ?? 0);
    $approvedReviews= (int)($kpis['approved_count'] ?? 0);
    $rejectedReviews= (int)($kpis['rejected_count'] ?? 0);
    $avgRating      = (float)($kpis['avg_rating'] ?? 5.0);

} catch (Exception $e) {
    AppLogger::error("Reviews administration error: " . $e->getMessage());
    $reviews = [];
    $totalReviews = 0; $pendingReviews = 0; $approvedReviews = 0; $rejectedReviews = 0; $avgRating = 0;
    $statusError = "Could not load customer reflections from database.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Customer Reflections &amp; Reviews Moderation — L'ÉTOILE Luxury Hair Salon &amp; Spa</title>
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
    .status-badge {
      display: inline-block;
      padding: 2px 8px;
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      border-radius: 12px;
    }
    .status-approved {
      background: #F4FAF5;
      color: #1E5128;
      border: 1px solid #8FD19E;
    }
    .status-pending {
      background: #FFFDF0;
      color: #926C15;
      border: 1px solid #E2CA7B;
    }
    .status-rejected {
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
      max-width: 580px;
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
          <span class="eyebrow eyebrow-no-line">Quality Assurance &amp; Feedback</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.2rem; color: var(--color-charcoal); margin: 4px 0 6px;">
            Guest Reflections &amp; Reviews Moderation
          </h1>
          <p style="color: var(--color-charcoal-muted); margin: 0; font-size: 0.95rem;">
            Review incoming patron feedback. Only approved testimonials are published to the public experience page.
          </p>
        </div>
        <div>
          <a href="reviews.html" target="_blank" class="btn btn-outline btn-sm">Public Testimonials Page &rarr;</a>
        </div>
      </div>

      <!-- KPI METRICS -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:var(--space-md); margin-top:var(--space-xl);">
        
        <div class="kpi-card">
          <div class="kpi-label">Pending Moderation</div>
          <div class="kpi-val" style="color:var(--color-champagne-dark);"><?php echo $pendingReviews; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Awaiting concierge approval</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Approved &amp; Live</div>
          <div class="kpi-val" style="color:#1E5128;"><?php echo $approvedReviews; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Published to public website</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Average Guest Rating</div>
          <div class="kpi-val" style="color:var(--color-charcoal);">
            &#9733; <?php echo number_format($avgRating, 1); ?>
          </div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Based on approved reflections</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Rejected / Moderated</div>
          <div class="kpi-val" style="color:#9E2A2B;"><?php echo $rejectedReviews; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Suppressed from public display</div>
        </div>

      </div>

    </div>
  </section>

  <!-- MAIN REVIEWS CONTENT -->
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

      <!-- FILTER TABS & SEARCH -->
      <div style="background:#FFFFFF; border:1px solid var(--color-charcoal-border); padding:var(--space-md) var(--space-lg); border-radius:var(--radius-xs); margin-bottom:var(--space-xl); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px;">
        <div style="display:flex; gap:6px; flex-wrap:wrap;">
          <a href="admin-reviews.php" class="btn btn-sm <?php echo $filterStatus === 'all' ? 'btn-champagne' : 'btn-outline'; ?>">
            All (<?php echo $totalReviews; ?>)
          </a>
          <a href="admin-reviews.php?status=pending" class="btn btn-sm <?php echo $filterStatus === 'pending' ? 'btn-champagne' : 'btn-outline'; ?>" style="<?php echo $pendingReviews > 0 ? 'font-weight:700;' : ''; ?>">
            Pending Queue (<?php echo $pendingReviews; ?>)
          </a>
          <a href="admin-reviews.php?status=approved" class="btn btn-sm <?php echo $filterStatus === 'approved' ? 'btn-champagne' : 'btn-outline'; ?>">
            Approved (<?php echo $approvedReviews; ?>)
          </a>
          <a href="admin-reviews.php?status=rejected" class="btn btn-sm <?php echo $filterStatus === 'rejected' ? 'btn-champagne' : 'btn-outline'; ?>">
            Rejected (<?php echo $rejectedReviews; ?>)
          </a>
        </div>

        <form action="admin-reviews.php" method="GET" style="display:flex; gap:8px;">
          <?php if ($filterStatus !== 'all'): ?>
            <input type="hidden" name="status" value="<?php echo e($filterStatus); ?>">
          <?php endif; ?>
          <input type="text" name="q" placeholder="Search author, title..." value="<?php echo e($searchQ); ?>" class="form-control" style="font-size:0.85rem; padding:6px 12px; width:200px;">
          <button type="submit" class="btn btn-outline btn-sm">Search</button>
        </form>
      </div>

      <!-- REVIEWS TABLE -->
      <div class="table-responsive" style="background:var(--color-white); border:1px solid var(--color-charcoal-border); border-radius:var(--radius-xs); box-shadow:var(--shadow-subtle); overflow-x:auto;">
        <?php if (empty($reviews)): ?>
          <div style="text-align:center; padding:40px 20px;">
            <p style="color:var(--color-charcoal-muted); margin-bottom:12px;">No customer reflections match this filter.</p>
            <a href="admin-reviews.php" class="btn btn-outline btn-sm">Reset Filter</a>
          </div>
        <?php else: ?>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Rating &amp; Title</th>
                <th>Guest Author</th>
                <th>Ritual &amp; Specialist</th>
                <th>Feedback Excerpt</th>
                <th>Date</th>
                <th>Status</th>
                <th>Featured</th>
                <th>Moderation Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reviews as $rev): ?>
                <?php 
                  $isAppr = $rev['status'] === 'approved';
                  $isPend = $rev['status'] === 'pending';
                  $isRej  = $rev['status'] === 'rejected';
                  $isFeat = (int)$rev['is_featured'] === 1;

                  $revPayload = [
                      'id'           => $rev['id'],
                      'guest_name'   => $rev['guest_name'],
                      'guest_email'  => $rev['guest_email'],
                      'rating'       => $rev['rating'],
                      'review_title' => $rev['review_title'],
                      'review_text'  => $rev['review_text'],
                      'service_name' => $rev['service_name'] ?? 'General Atelier Experience',
                      'staff_name'   => $rev['staff_name'] ?? 'Not Specified',
                      'status'       => $rev['status'],
                      'created_at'   => date('F j, Y, g:i A', strtotime($rev['created_at']))
                  ];
                  $jsonPayload = htmlspecialchars(json_encode($revPayload), ENT_QUOTES, 'UTF-8');
                ?>
                <tr>
                  <td>
                    <div style="color:var(--color-champagne-dark); font-size:0.95rem; font-weight:700;">
                      <?php echo str_repeat('&#9733;', (int)$rev['rating']) . str_repeat('&#9734;', 5 - (int)$rev['rating']); ?>
                    </div>
                    <div style="font-weight:600; color:var(--color-charcoal); margin-top:2px;">
                      <?php echo e($rev['review_title'] ?: 'Untitled Reflection'); ?>
                    </div>
                  </td>
                  <td>
                    <strong style="color:var(--color-charcoal);"><?php echo e($rev['guest_name']); ?></strong>
                    <div style="font-size:0.75rem; color:var(--color-charcoal-muted); font-family:monospace;">
                      <?php echo e($rev['guest_email']); ?>
                    </div>
                  </td>
                  <td>
                    <div style="font-size:0.85rem; color:var(--color-charcoal);">
                      <?php echo e($rev['service_name'] ?: 'General Atelier Experience'); ?>
                    </div>
                    <?php if (!empty($rev['staff_name'])): ?>
                      <div style="font-size:0.75rem; color:var(--color-champagne-dark);">
                        With: <?php echo e($rev['staff_name']); ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="max-width:320px; font-size:0.84rem; color:var(--color-charcoal-muted); line-height:1.4;">
                      <?php echo e(mb_strimwidth($rev['review_text'], 0, 110, '...')); ?>
                    </div>
                  </td>
                  <td>
                    <span style="font-size:0.78rem; color:var(--color-charcoal-muted); white-space:nowrap;">
                      <?php echo date('M j, Y', strtotime($rev['created_at'])); ?>
                    </span>
                  </td>
                  <td>
                    <span class="status-badge status-<?php echo e($rev['status']); ?>">
                      <?php echo ucfirst($rev['status']); ?>
                    </span>
                  </td>
                  <td>
                    <form action="admin-reviews.php" method="POST" style="display:inline;">
                      <?php echo csrf_field(); ?>
                      <input type="hidden" name="action" value="toggle_featured">
                      <input type="hidden" name="review_id" value="<?php echo (int)$rev['id']; ?>">
                      <input type="hidden" name="is_featured" value="<?php echo $isFeat ? '0' : '1'; ?>">
                      <button type="submit" style="background:none; border:none; cursor:pointer; font-size:1.1rem; color:<?php echo $isFeat ? 'var(--color-champagne-dark)' : '#CBD5E1'; ?>;" title="Toggle homepage highlight">
                        &#9733;
                      </button>
                    </form>
                  </td>
                  <td>
                    <div style="display:flex; gap:6px;">
                      <button type="button" class="btn-action-sm btn-inspect-review" data-review="<?php echo $jsonPayload; ?>">
                        Inspect
                      </button>

                      <?php if ($rev['status'] !== 'approved'): ?>
                        <form action="admin-reviews.php" method="POST" style="display:inline;">
                          <?php echo csrf_field(); ?>
                          <input type="hidden" name="action" value="approve">
                          <input type="hidden" name="review_id" value="<?php echo (int)$rev['id']; ?>">
                          <button type="submit" class="btn-action-sm" style="color:#1E5128; border-color:#8FD19E;">
                            Approve
                          </button>
                        </form>
                      <?php endif; ?>

                      <?php if ($rev['status'] !== 'rejected'): ?>
                        <form action="admin-reviews.php" method="POST" style="display:inline;">
                          <?php echo csrf_field(); ?>
                          <input type="hidden" name="action" value="reject">
                          <input type="hidden" name="review_id" value="<?php echo (int)$rev['id']; ?>">
                          <button type="submit" class="btn-action-sm" style="color:#9E2A2B; border-color:rgba(201,83,83,0.3);">
                            Reject
                          </button>
                        </form>
                      <?php endif; ?>
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

  <!-- INSPECT REVIEW MODAL -->
  <div id="inspectReviewModal" class="modal-overlay">
    <div class="modal-dialog">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Patron Reflection Dossier</span>
          <h3 style="font-family:var(--font-serif); font-size:1.5rem; margin:2px 0 0;" id="inspTitle">Review Inspection</h3>
        </div>
        <button type="button" class="btn-close-modal" data-target="inspectReviewModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
      </div>

      <div style="margin-bottom:14px; background:var(--color-ivory); padding:12px; border-radius:2px; border:1px solid var(--color-champagne-border); display:flex; justify-content:space-between; align-items:center;">
        <div>
          <div style="font-size:0.75rem; text-transform:uppercase; color:var(--color-charcoal-muted);">Rating</div>
          <div id="inspRating" style="font-size:1.15rem; color:var(--color-champagne-dark); font-weight:700;"></div>
        </div>
        <div>
          <div style="font-size:0.75rem; text-transform:uppercase; color:var(--color-charcoal-muted);">Status</div>
          <span id="inspStatusBadge" class="status-badge"></span>
        </div>
        <div>
          <div style="font-size:0.75rem; text-transform:uppercase; color:var(--color-charcoal-muted);">Submitted</div>
          <span id="inspDate" style="font-size:0.82rem; color:var(--color-charcoal);"></span>
        </div>
      </div>

      <div style="margin-bottom:12px;">
        <h5 style="font-family:var(--font-serif); font-size:1.1rem; margin:0 0 4px;">Patron Information</h5>
        <div><strong>Name:</strong> <span id="inspGuestName"></span></div>
        <div><strong>Private Email:</strong> <span id="inspGuestEmail" style="font-family:monospace;"></span></div>
      </div>

      <div style="margin-bottom:12px;">
        <h5 style="font-family:var(--font-serif); font-size:1.1rem; margin:0 0 4px;">Atelier Experience</h5>
        <div><strong>Service:</strong> <span id="inspService"></span></div>
        <div><strong>Specialist:</strong> <span id="inspStaff"></span></div>
      </div>

      <div style="margin-bottom:16px;">
        <h5 style="font-family:var(--font-serif); font-size:1.1rem; margin:0 0 4px;">Full Reflection</h5>
        <div id="inspText" style="background:#FBF9F5; border-left:3px solid var(--color-champagne); padding:12px; font-size:0.9rem; line-height:1.6; color:var(--color-charcoal); white-space:pre-wrap;"></div>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:8px; border-top:1px solid var(--color-charcoal-border); padding-top:var(--space-md);">
        <button type="button" class="btn btn-outline btn-sm btn-close-modal" data-target="inspectReviewModal">Close</button>
      </div>
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

      const inspectModal = document.getElementById('inspectReviewModal');
      document.querySelectorAll('.btn-inspect-review').forEach(btn => {
        btn.addEventListener('click', () => {
          try {
            const data = JSON.parse(btn.getAttribute('data-review'));
            document.getElementById('inspTitle').textContent = data.review_title || 'Guest Reflection';
            document.getElementById('inspRating').innerHTML = '&#9733;'.repeat(data.rating) + '&#9734;'.repeat(5 - data.rating);
            
            const badge = document.getElementById('inspStatusBadge');
            badge.className = 'status-badge status-' + data.status;
            badge.textContent = data.status.charAt(0).toUpperCase() + data.status.slice(1);

            document.getElementById('inspDate').textContent = data.created_at;
            document.getElementById('inspGuestName').textContent = data.guest_name;
            document.getElementById('inspGuestEmail').textContent = data.guest_email;
            document.getElementById('inspService').textContent = data.service_name;
            document.getElementById('inspStaff').textContent = data.staff_name;
            document.getElementById('inspText').textContent = data.review_text;

            inspectModal.style.display = 'flex';
          } catch (e) {
            alert('Could not open review details.');
          }
        });
      });
    });
  </script>

</body>
</html>
