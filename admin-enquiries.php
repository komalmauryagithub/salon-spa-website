<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Concierge Contact Inquiries & Guest Correspondence Management
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

require_once __DIR__ . '/includes/bootstrap.php';

// 1. Guard check: Strictly restricted to administrative staff
Auth::requireRole(['admin', 'manager', 'receptionist'], 'admin-login.php');

$currentUser = Auth::user();
$activeAdminPage = 'enquiries';
$statusMessage = null;
$statusError   = null;

try {
    $pdo = Database::getConnection();

    // 2. Handle POST Updates
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? null;
        if (!Csrf::verify($token)) {
            $statusError = "Security token validation failed. Please refresh the page.";
        } else {
            $action = $_POST['action'] ?? '';
            $enquiryId = (int)($_POST['enquiry_id'] ?? 0);

            if ($enquiryId > 0) {
                // A. Update Status & Internal Notes
                if ($action === 'update_status') {
                    $newStatus  = trim((string)($_POST['status'] ?? 'new'));
                    $adminNotes = trim((string)($_POST['admin_notes'] ?? ''));

                    $validStatuses = ['new', 'in_progress', 'responded', 'archived'];
                    if (in_array($newStatus, $validStatuses, true)) {
                        $respondedSql = ($newStatus === 'responded') ? "responded_at = COALESCE(responded_at, NOW())," : "";
                        $up = $pdo->prepare("
                            UPDATE contact_enquiries SET
                                status      = :st,
                                {$respondedSql}
                                admin_notes = :notes
                            WHERE id = :id
                        ");
                        $up->execute([
                            'st'    => $newStatus,
                            'notes' => !empty($adminNotes) ? $adminNotes : null,
                            'id'    => $enquiryId
                        ]);

                        $statusMessage = "Enquiry #{$enquiryId} status updated to " . ucfirst(str_replace('_', ' ', $newStatus)) . ".";
                        AppLogger::info("Admin #{$currentUser['id']} updated enquiry #{$enquiryId} to {$newStatus}");
                    } else {
                        $statusError = "Invalid status specified.";
                    }

                // B. Archive Enquiry
                } elseif ($action === 'archive') {
                    $up = $pdo->prepare("UPDATE contact_enquiries SET status = 'archived' WHERE id = :id");
                    $up->execute(['id' => $enquiryId]);
                    $statusMessage = "Enquiry #{$enquiryId} moved to archive.";
                    AppLogger::info("Admin #{$currentUser['id']} archived enquiry #{$enquiryId}");

                // C. Delete Enquiry
                } elseif ($action === 'delete') {
                    $del = $pdo->prepare("DELETE FROM contact_enquiries WHERE id = :id");
                    $del->execute(['id' => $enquiryId]);
                    $statusMessage = "Enquiry #{$enquiryId} permanently deleted.";
                    AppLogger::info("Admin #{$currentUser['id']} deleted enquiry #{$enquiryId}");
                }
            }
        }
    }

    // 3. Filters & Search
    $filterStatus = trim((string)($_GET['status'] ?? 'all'));
    $searchQ      = trim((string)($_GET['q'] ?? ''));

    $where = ["1=1"];
    $params = [];

    if ($filterStatus !== 'all' && in_array($filterStatus, ['new', 'in_progress', 'responded', 'archived'], true)) {
        $where[] = "status = :st";
        $params['st'] = $filterStatus;
    }

    if (!empty($searchQ)) {
        $where[] = "(full_name LIKE :search OR email LIKE :search OR phone LIKE :search OR subject LIKE :search OR message LIKE :search)";
        $params['search'] = "%{$searchQ}%";
    }

    $whereSql = implode(' AND ', $where);

    // 4. Load Enquiries
    $stmt = $pdo->prepare("
        SELECT id, full_name, email, phone, preferred_contact_method, subject, message, status, admin_notes, created_at, responded_at
        FROM contact_enquiries
        WHERE {$whereSql}
        ORDER BY 
            CASE WHEN status = 'new' THEN 0 WHEN status = 'in_progress' THEN 1 ELSE 2 END,
            created_at DESC
    ");
    $stmt->execute($params);
    $enquiries = $stmt->fetchAll();

    // 5. Counters
    $kpiRow = $pdo->query("
        SELECT 
            COUNT(*) AS total_count,
            SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) AS new_count,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) AS progress_count,
            SUM(CASE WHEN status = 'responded' THEN 1 ELSE 0 END) AS responded_count,
            SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) AS archived_count
        FROM contact_enquiries
    ")->fetch();

    $totalEnquiries = (int)($kpiRow['total_count'] ?? 0);
    $newEnquiries   = (int)($kpiRow['new_count'] ?? 0);
    $progEnquiries  = (int)($kpiRow['progress_count'] ?? 0);
    $respEnquiries  = (int)($kpiRow['responded_count'] ?? 0);

} catch (Exception $e) {
    AppLogger::error("Enquiries administration error: " . $e->getMessage());
    $enquiries = [];
    $totalEnquiries = 0; $newEnquiries = 0; $progEnquiries = 0; $respEnquiries = 0;
    $statusError = "Could not load contact enquiries from database.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Concierge Enquiries &amp; Correspondence — L'ÉTOILE Luxury Hair Salon &amp; Spa</title>
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
    .status-new {
      background: #FFFDF0;
      color: #926C15;
      border: 1px solid #E2CA7B;
    }
    .status-in_progress {
      background: #EFF6FF;
      color: #1D4ED8;
      border: 1px solid #BFDBFE;
    }
    .status-responded {
      background: #F4FAF5;
      color: #1E5128;
      border: 1px solid #8FD19E;
    }
    .status-archived {
      background: #F1F5F9;
      color: #64748B;
      border: 1px solid #CBD5E1;
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
      max-width: 600px;
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
          <span class="eyebrow eyebrow-no-line">Guest Communications</span>
          <h1 style="font-family: var(--font-serif); font-size: 2.2rem; color: var(--color-charcoal); margin: 4px 0 6px;">
            Concierge Enquiries &amp; Messages
          </h1>
          <p style="color: var(--color-charcoal-muted); margin: 0; font-size: 0.95rem;">
            Private guest inquiries, bridal suite requests, and consultation notes. Confidentiality protected.
          </p>
        </div>
      </div>

      <!-- KPI METRICS -->
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:var(--space-md); margin-top:var(--space-xl);">
        
        <div class="kpi-card">
          <div class="kpi-label">New Enquiries</div>
          <div class="kpi-val" style="color:var(--color-champagne-dark);"><?php echo $newEnquiries; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Awaiting concierge contact</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">In Progress</div>
          <div class="kpi-val" style="color:#1D4ED8;"><?php echo $progEnquiries; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Under active consultation</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Responded</div>
          <div class="kpi-val" style="color:#1E5128;"><?php echo $respEnquiries; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Consultations addressed</div>
        </div>

        <div class="kpi-card">
          <div class="kpi-label">Total Volume</div>
          <div class="kpi-val"><?php echo $totalEnquiries; ?></div>
          <div style="font-size:0.75rem; color:var(--color-charcoal-muted); margin-top:4px;">Messages on record</div>
        </div>

      </div>

    </div>
  </section>

  <!-- MAIN ENQUIRIES CONTENT -->
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
          <a href="admin-enquiries.php" class="btn btn-sm <?php echo $filterStatus === 'all' ? 'btn-champagne' : 'btn-outline'; ?>">
            All (<?php echo $totalEnquiries; ?>)
          </a>
          <a href="admin-enquiries.php?status=new" class="btn btn-sm <?php echo $filterStatus === 'new' ? 'btn-champagne' : 'btn-outline'; ?>" style="<?php echo $newEnquiries > 0 ? 'font-weight:700;' : ''; ?>">
            New Inbox (<?php echo $newEnquiries; ?>)
          </a>
          <a href="admin-enquiries.php?status=in_progress" class="btn btn-sm <?php echo $filterStatus === 'in_progress' ? 'btn-champagne' : 'btn-outline'; ?>">
            In Progress (<?php echo $progEnquiries; ?>)
          </a>
          <a href="admin-enquiries.php?status=responded" class="btn btn-sm <?php echo $filterStatus === 'responded' ? 'btn-champagne' : 'btn-outline'; ?>">
            Responded (<?php echo $respEnquiries; ?>)
          </a>
          <a href="admin-enquiries.php?status=archived" class="btn btn-sm <?php echo $filterStatus === 'archived' ? 'btn-champagne' : 'btn-outline'; ?>">
            Archived
          </a>
        </div>

        <form action="admin-enquiries.php" method="GET" style="display:flex; gap:8px;">
          <?php if ($filterStatus !== 'all'): ?>
            <input type="hidden" name="status" value="<?php echo e($filterStatus); ?>">
          <?php endif; ?>
          <input type="text" name="q" placeholder="Search guest, email, phone..." value="<?php echo e($searchQ); ?>" class="form-control" style="font-size:0.85rem; padding:6px 12px; width:220px;">
          <button type="submit" class="btn btn-outline btn-sm">Search</button>
        </form>
      </div>

      <!-- ENQUIRIES TABLE -->
      <div class="table-responsive" style="background:var(--color-white); border:1px solid var(--color-charcoal-border); border-radius:var(--radius-xs); box-shadow:var(--shadow-subtle); overflow-x:auto;">
        <?php if (empty($enquiries)): ?>
          <div style="text-align:center; padding:40px 20px;">
            <p style="color:var(--color-charcoal-muted); margin-bottom:12px;">No contact inquiries match this filter.</p>
            <a href="admin-enquiries.php" class="btn btn-outline btn-sm">Reset Filter</a>
          </div>
        <?php else: ?>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Guest Client</th>
                <th>Subject &amp; Method</th>
                <th>Message Excerpt</th>
                <th>Received</th>
                <th>Status</th>
                <th>Internal Notes</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($enquiries as $enq): ?>
                <?php 
                  $enqPayload = [
                      'id'                       => $enq['id'],
                      'full_name'                => $enq['full_name'],
                      'email'                    => $enq['email'],
                      'phone'                    => $enq['phone'] ?: 'None provided',
                      'preferred_contact_method' => $enq['preferred_contact_method'],
                      'subject'                  => $enq['subject'],
                      'message'                  => $enq['message'],
                      'status'                   => $enq['status'],
                      'admin_notes'              => $enq['admin_notes'] ?: '',
                      'created_at'               => date('F j, Y, g:i A', strtotime($enq['created_at'])),
                      'responded_at'             => $enq['responded_at'] ? date('F j, Y, g:i A', strtotime($enq['responded_at'])) : null
                  ];
                  $jsonPayload = htmlspecialchars(json_encode($enqPayload), ENT_QUOTES, 'UTF-8');
                ?>
                <tr>
                  <td>
                    <strong style="color:var(--color-charcoal);"><?php echo e($enq['full_name']); ?></strong>
                    <div style="font-size:0.75rem; color:var(--color-charcoal-muted); font-family:monospace;">
                      <?php echo e($enq['email']); ?>
                    </div>
                    <?php if (!empty($enq['phone'])): ?>
                      <div style="font-size:0.75rem; color:var(--color-champagne-dark);">
                        <?php echo e($enq['phone']); ?>
                      </div>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div style="font-weight:600; color:var(--color-charcoal); font-size:0.88rem;">
                      <?php echo e($enq['subject']); ?>
                    </div>
                    <span style="font-size:0.72rem; color:var(--color-charcoal-muted);">
                      Prefers: <?php echo e($enq['preferred_contact_method']); ?>
                    </span>
                  </td>
                  <td>
                    <div style="max-width:320px; font-size:0.84rem; color:var(--color-charcoal-muted); line-height:1.4;">
                      <?php echo e(mb_strimwidth($enq['message'], 0, 100, '...')); ?>
                    </div>
                  </td>
                  <td>
                    <span style="font-size:0.78rem; color:var(--color-charcoal-muted); white-space:nowrap;">
                      <?php echo date('M j, Y', strtotime($enq['created_at'])); ?>
                    </span>
                  </td>
                  <td>
                    <span class="status-badge status-<?php echo e($enq['status']); ?>">
                      <?php echo ucfirst(str_replace('_', ' ', $enq['status'])); ?>
                    </span>
                  </td>
                  <td>
                    <div style="font-size:0.78rem; color:var(--color-charcoal-muted); max-width:180px;">
                      <?php echo !empty($enq['admin_notes']) ? e(mb_strimwidth($enq['admin_notes'], 0, 45, '...')) : '<em>None</em>'; ?>
                    </div>
                  </td>
                  <td>
                    <div style="display:flex; gap:6px;">
                      <button type="button" class="btn-action-sm btn-inspect-enquiry" data-enquiry="<?php echo $jsonPayload; ?>">
                        Respond / Edit
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

  <!-- INSPECT & RESPOND MODAL -->
  <div id="inspectEnquiryModal" class="modal-overlay">
    <div class="modal-dialog">
      <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid var(--color-charcoal-border); padding-bottom:var(--space-sm); margin-bottom:var(--space-md);">
        <div>
          <span class="eyebrow eyebrow-no-line">Concierge Correspondence Dossier</span>
          <h3 style="font-family:var(--font-serif); font-size:1.5rem; margin:2px 0 0;" id="modalSubject">Inquiry Details</h3>
        </div>
        <button type="button" class="btn-close-modal" data-target="inspectEnquiryModal" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
      </div>

      <div style="margin-bottom:14px; background:var(--color-ivory); padding:14px; border-radius:2px; border:1px solid var(--color-champagne-border); display:grid; grid-template-columns:1fr 1fr; gap:12px;">
        <div>
          <div style="font-size:0.75rem; text-transform:uppercase; color:var(--color-charcoal-muted);">Guest Client</div>
          <strong id="modalGuestName" style="color:var(--color-charcoal); font-size:1rem;"></strong>
          <div id="modalGuestEmail" style="font-size:0.82rem; font-family:monospace; color:var(--color-charcoal-muted);"></div>
          <div id="modalGuestPhone" style="font-size:0.82rem; color:var(--color-champagne-dark);"></div>
        </div>
        <div>
          <div style="font-size:0.75rem; text-transform:uppercase; color:var(--color-charcoal-muted);">Communication Preference</div>
          <div id="modalMethod" style="font-size:0.88rem; font-weight:600; color:var(--color-charcoal);"></div>
          <div style="font-size:0.75rem; text-transform:uppercase; color:var(--color-charcoal-muted); margin-top:6px;">Received</div>
          <div id="modalCreated" style="font-size:0.8rem; color:var(--color-charcoal-muted);"></div>
        </div>
      </div>

      <div style="margin-bottom:14px;">
        <h5 style="font-family:var(--font-serif); font-size:1.1rem; margin:0 0 6px;">Client Message</h5>
        <div id="modalMessage" style="background:#FBF9F5; border-left:3px solid var(--color-champagne); padding:12px; font-size:0.88rem; line-height:1.6; color:var(--color-charcoal); white-space:pre-wrap;"></div>
      </div>

      <!-- STATUS & NOTES FORM -->
      <form action="admin-enquiries.php" method="POST" style="border-top:1px dashed var(--color-charcoal-border); padding-top:14px;">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="enquiry_id" id="modalEnquiryId" value="">

        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
          <div>
            <label class="form-label">Workflow Status *</label>
            <select name="status" id="modalStatus" class="form-control" required>
              <option value="new">New Inquiry</option>
              <option value="in_progress">In Progress / Under Review</option>
              <option value="responded">Responded &amp; Solved</option>
              <option value="archived">Archived</option>
            </select>
          </div>
          <div>
            <label class="form-label">Quick Mail Client</label>
            <a href="#" id="btnMailto" class="btn btn-outline btn-sm" style="display:block; text-align:center; padding:8px 10px;">
              Compose Email &rarr;
            </a>
          </div>
        </div>

        <div style="margin-bottom:14px;">
          <label class="form-label">Internal Concierge Notes (Confidential)</label>
          <textarea name="admin_notes" id="modalAdminNotes" class="form-control" rows="2" placeholder="Record date contacted, quote given, or follow-up notes..."></textarea>
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center;">
          <button type="button" class="btn btn-outline btn-sm btn-close-modal" data-target="inspectEnquiryModal">Close</button>
          <button type="submit" class="btn btn-champagne btn-sm">Save Status &amp; Notes</button>
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

      const inspectModal = document.getElementById('inspectEnquiryModal');
      document.querySelectorAll('.btn-inspect-enquiry').forEach(btn => {
        btn.addEventListener('click', () => {
          try {
            const data = JSON.parse(btn.getAttribute('data-enquiry'));
            document.getElementById('modalEnquiryId').value = data.id;
            document.getElementById('modalSubject').textContent = data.subject || 'Inquiry Details';
            document.getElementById('modalGuestName').textContent = data.full_name;
            document.getElementById('modalGuestEmail').textContent = data.email;
            document.getElementById('modalGuestPhone').textContent = data.phone;
            document.getElementById('modalMethod').textContent = data.preferred_contact_method;
            document.getElementById('modalCreated').textContent = data.created_at;
            document.getElementById('modalMessage').textContent = data.message;
            document.getElementById('modalStatus').value = data.status;
            document.getElementById('modalAdminNotes').value = data.admin_notes || '';

            // Setup mailto quick link
            const mailtoLink = `mailto:${encodeURIComponent(data.email)}?subject=${encodeURIComponent("Re: " + data.subject + " — L'Étoile Atelier")}`;
            document.getElementById('btnMailto').href = mailtoLink;

            inspectModal.style.display = 'flex';
          } catch (e) {
            alert('Could not open inquiry details.');
          }
        });
      });
    });
  </script>

</body>
</html>
