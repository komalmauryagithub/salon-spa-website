<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Shared Administrative Navigation & Header Component
 * 
 * Strict Brand Theme: Ivory #F5EFE6, Champagne #C5A46D, Charcoal #252321, White #FFFFFF
 */

if (!isset($currentUser)) {
    $currentUser = Auth::user();
}
$activePage = $activeAdminPage ?? 'dashboard';
?>

<!-- TOP CONCIERGE BAR -->
<div class="top-bar">
  <div class="container top-bar-inner">
    <div class="top-bar-left">
      <span class="top-bar-item">
        <strong>L'Étoile Atelier Concierge Desk</strong> &bull; Administrative Operations
      </span>
    </div>
    <div class="top-bar-right">
      <span class="top-bar-item">
        Signed in as: <strong><?php echo e($currentUser['username'] ?? 'Staff'); ?></strong> 
        <span style="display:inline-block; margin-left:4px; padding:2px 8px; font-size:0.7rem; font-weight:700; text-transform:uppercase; letter-spacing:0.06em; background:var(--color-champagne-subtle); color:var(--color-champagne-dark); border-radius:12px; border:1px solid var(--color-champagne-border);">
          <?php echo e(ucfirst($currentUser['role'] ?? 'Staff')); ?>
        </span>
      </span>
      <span class="top-bar-item">
        <a href="index.html" target="_blank" style="color:var(--color-champagne-dark); display:inline-flex; align-items:center; gap:4px;">
          <span>View Public Site</span>
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"></path><polyline points="15 3 21 3 21 9"></polyline><line x1="10" y1="14" x2="21" y2="3"></line></svg>
        </a>
      </span>
      <span class="top-bar-item">
        <a href="logout.php" style="color:#9E2A2B; font-weight:600;">Sign Out</a>
      </span>
    </div>
  </div>
</div>

<!-- SITE HEADER & NAV -->
<header class="site-header" role="banner">
  <div class="container nav-container">
    <a href="admin-dashboard.php" class="brand" aria-label="L'Étoile Concierge Portal">
      <span class="brand-title">L'ÉTOILE</span>
      <span class="brand-subtitle">ADMIN &amp; CONCIERGE</span>
    </a>

    <button type="button" class="mobile-menu-btn" id="admin-mobile-toggle" aria-label="Toggle navigation menu" style="background:none; border:none; cursor:pointer; color:var(--color-charcoal); display:none;">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
    </button>

    <nav class="nav-links" id="admin-navigation" aria-label="Administrative Navigation" style="display:flex; flex-wrap:wrap; gap:2px; align-items:center;">
      <a href="admin-dashboard.php" class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>" style="padding:6px 10px; font-size:0.83rem;">Overview</a>
      <a href="admin-appointments.php" class="nav-link <?php echo $activePage === 'appointments' ? 'active' : ''; ?>" style="padding:6px 10px; font-size:0.83rem;">Appointments</a>
      <a href="admin-customers.php" class="nav-link <?php echo $activePage === 'customers' ? 'active' : ''; ?>" style="padding:6px 10px; font-size:0.83rem;">Patrons</a>
      <a href="admin-staff.php" class="nav-link <?php echo $activePage === 'staff' ? 'active' : ''; ?>" style="padding:6px 10px; font-size:0.83rem;">Specialists</a>
      <a href="admin-services.php" class="nav-link <?php echo $activePage === 'services' ? 'active' : ''; ?>" style="padding:6px 10px; font-size:0.83rem;">Services</a>
      <a href="admin-offers.php" class="nav-link <?php echo $activePage === 'offers' ? 'active' : ''; ?>" style="padding:6px 10px; font-size:0.83rem;">Offers</a>
      <a href="admin-gallery.php" class="nav-link <?php echo $activePage === 'gallery' ? 'active' : ''; ?>" style="padding:6px 10px; font-size:0.83rem;">Gallery</a>
      <a href="admin-reviews.php" class="nav-link <?php echo $activePage === 'reviews' ? 'active' : ''; ?>" style="padding:6px 10px; font-size:0.83rem;">Reviews</a>
      <a href="admin-enquiries.php" class="nav-link <?php echo $activePage === 'enquiries' ? 'active' : ''; ?>" style="padding:6px 10px; font-size:0.83rem;">Enquiries</a>
      <a href="admin-reports.php" class="nav-link <?php echo $activePage === 'reports' ? 'active' : ''; ?>" style="padding:6px 10px; font-size:0.83rem;">Reports</a>
      <a href="admin-settings.php" class="nav-link <?php echo $activePage === 'settings' ? 'active' : ''; ?>" style="padding:6px 10px; font-size:0.83rem;">Settings</a>
      <div class="mobile-only-cta">
        <a href="book-appointment.html" target="_blank" class="btn btn-champagne">Walk-in Reservation</a>
      </div>
    </nav>

    <div class="nav-cta">
      <a href="book-appointment.html" class="btn btn-champagne btn-sm" target="_blank" title="Book a walk-in or concierge phone reservation">
        <span>+ Walk-In Booking</span>
      </a>
    </div>
  </div>
</header>

<script>
  // Mobile nav toggle for admin view
  document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.getElementById('admin-mobile-toggle');
    const nav = document.getElementById('admin-navigation');
    if (toggle && nav) {
      toggle.addEventListener('click', () => {
        nav.classList.toggle('nav-active');
      });
    }
  });
</script>
