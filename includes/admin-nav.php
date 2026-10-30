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
<div class="top-bar admin-top-bar">
  <div class="container top-bar-inner">
    <div class="top-bar-left">
      <span class="top-bar-item">
        <strong>L'Étoile Atelier Concierge Desk</strong> &bull; Administrative Operations
      </span>
    </div>
    <div class="top-bar-right admin-top-bar-right">
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

    <!-- Mobile Header Actions (Quick Sign Out & Menu Hamburger) -->
    <div class="admin-mobile-header-actions">
      <a href="logout.php" class="admin-quick-signout-btn" title="Sign Out of Admin" aria-label="Sign Out of Admin Console">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
        <span>Sign Out</span>
      </a>
      <button type="button" class="admin-nav-toggle" id="admin-mobile-toggle" aria-label="Toggle navigation menu">
        <span class="nav-toggle-bar"></span>
        <span class="nav-toggle-bar"></span>
        <span class="nav-toggle-bar"></span>
      </button>
    </div>

    <nav class="nav-links admin-nav-links" id="admin-navigation" aria-label="Administrative Navigation">
      <a href="admin-dashboard.php" class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">Overview</a>
      <a href="admin-appointments.php" class="nav-link <?php echo $activePage === 'appointments' ? 'active' : ''; ?>">Appointments</a>
      <a href="admin-customers.php" class="nav-link <?php echo $activePage === 'customers' ? 'active' : ''; ?>">Patrons</a>
      <a href="admin-staff.php" class="nav-link <?php echo $activePage === 'staff' ? 'active' : ''; ?>">Specialists</a>
      <a href="admin-services.php" class="nav-link <?php echo $activePage === 'services' ? 'active' : ''; ?>">Services</a>
      <a href="admin-offers.php" class="nav-link <?php echo $activePage === 'offers' ? 'active' : ''; ?>">Offers</a>
      <a href="admin-gallery.php" class="nav-link <?php echo $activePage === 'gallery' ? 'active' : ''; ?>">Gallery</a>
      <a href="admin-reviews.php" class="nav-link <?php echo $activePage === 'reviews' ? 'active' : ''; ?>">Reviews</a>
      <a href="admin-enquiries.php" class="nav-link <?php echo $activePage === 'enquiries' ? 'active' : ''; ?>">Enquiries</a>
      <a href="admin-reports.php" class="nav-link <?php echo $activePage === 'reports' ? 'active' : ''; ?>">Reports</a>
      <a href="admin-settings.php" class="nav-link <?php echo $activePage === 'settings' ? 'active' : ''; ?>">Settings</a>
      
      <!-- Admin Mobile Drawer User & Sign Out Footer -->
      <div class="admin-drawer-footer">
        <div class="admin-drawer-user-pill">
          <span>Signed in as <strong><?php echo e($currentUser['username'] ?? 'Staff'); ?></strong> (<?php echo e(ucfirst($currentUser['role'] ?? 'Staff')); ?>)</span>
        </div>
        <a href="logout.php" class="btn btn-outline btn-sm admin-drawer-signout-btn">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
          <span>Sign Out</span>
        </a>
      </div>

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
    const header = document.querySelector('.site-header');

    if (toggle && nav) {
      const originalParent = nav.parentNode;
      const nextSibling = nav.nextSibling;

      const ensureAdminBodyAttachment = () => {
        if (window.innerWidth <= 1024) {
          if (nav.parentNode !== document.body) {
            document.body.appendChild(nav);
          }
        } else {
          if (nav.parentNode === document.body && originalParent) {
            originalParent.insertBefore(nav, nextSibling);
          }
        }
      };

      ensureAdminBodyAttachment();
      nav.classList.remove('nav-active');
      nav.setAttribute('aria-hidden', 'true');

      let backdrop = document.querySelector('.admin-nav-backdrop');
      if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.className = 'admin-nav-backdrop nav-backdrop';
        document.body.appendChild(backdrop);
      }

      const updateAdminDrawer = () => {
        ensureAdminBodyAttachment();
      };

      const closeDrawer = () => {
        nav.classList.remove('nav-active');
        nav.setAttribute('aria-hidden', 'true');
        toggle.classList.remove('is-open');
        toggle.setAttribute('aria-expanded', 'false');
        backdrop.classList.remove('is-active');
        document.body.style.overflow = '';
      };

      toggle.addEventListener('click', (e) => {
        e.stopPropagation();
        const willOpen = !nav.classList.contains('nav-active');
        if (willOpen) {
          updateAdminDrawer();
          nav.classList.add('nav-active');
          nav.setAttribute('aria-hidden', 'false');
          toggle.classList.add('is-open');
          toggle.setAttribute('aria-expanded', 'true');
          backdrop.classList.add('is-active');
          document.body.style.overflow = 'hidden';
        } else {
          closeDrawer();
        }
      });

      backdrop.addEventListener('click', closeDrawer);

      document.addEventListener('click', (e) => {
        if (nav.classList.contains('nav-active') && !nav.contains(e.target) && !toggle.contains(e.target)) {
          closeDrawer();
        }
      });

      document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && nav.classList.contains('nav-active')) {
          closeDrawer();
        }
      });

      nav.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', closeDrawer);
      });

      window.addEventListener('resize', () => {
        ensureAdminBodyAttachment();
        if (window.innerWidth > 1024 && nav.classList.contains('nav-active')) {
          closeDrawer();
        }
      });
    }
  });
</script>
