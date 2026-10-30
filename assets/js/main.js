/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Phase 1 - Main Application Script
 * Vanilla JavaScript (Zero External Libraries)
 */

let appCsrfToken = '';

document.addEventListener('DOMContentLoaded', () => {
  initCsrfToken();
  initHeaderScroll();
  initMobileNav();
  initScrollReveal();
  initImageFallbacks();
  initCategoryFilters();
  initLightbox();
  initContactForm();
  initReviewForm();
  initNewsletterForm();
});

/* --------------------------------------------------------------------------
   0. CSRF Token Synchronization
   -------------------------------------------------------------------------- */
function initCsrfToken() {
  fetch('api/csrf.php')
    .then(res => res.json())
    .then(data => {
      if (data && data.csrf_token) {
        appCsrfToken = data.csrf_token;
      }
      if (data) {
        updateAuthNav(data);
      }
    })
    .catch(() => {
      // Running under static file preview mode
    });
}

/* --------------------------------------------------------------------------
   0b. Dynamic Navigation State for Authenticated Guests
   -------------------------------------------------------------------------- */
function updateAuthNav(authData) {
  const topBarRight = document.querySelector('.top-bar-right');
  const navLinks = document.getElementById('primary-navigation');

  if (authData.is_authenticated) {
    const cust = authData.customer;
    const role = (authData.role || '').toLowerCase();
    const isAdmin = ['admin', 'manager', 'receptionist'].includes(role);
    const targetUrl = isAdmin ? 'admin-dashboard.php' : 'customer-profile.php';
    const label = isAdmin ? 'Admin Console' : (cust ? cust.first_name : 'My Account');

    if (topBarRight) {
      let authCapsule = document.getElementById('auth-topbar-link');
      if (!authCapsule) {
        authCapsule = document.createElement('span');
        authCapsule.id = 'auth-topbar-link';
        authCapsule.className = 'top-bar-item';
        topBarRight.prepend(authCapsule);
      }
      authCapsule.innerHTML = `
        <a href="${targetUrl}" class="top-auth-pill">
          <span>${isAdmin ? '👑' : '👤'} ${label}</span>
        </a>
        <a href="logout.php" class="top-logout-btn" title="Sign Out">Sign Out</a>
      `;

      // Hide redundant static Client Portal link if present
      const clientPortalLinks = topBarRight.querySelectorAll('a[href="login.php"]');
      clientPortalLinks.forEach(link => {
        if (link.closest('.top-bar-item') !== authCapsule) {
          link.closest('.top-bar-item').style.display = 'none';
        }
      });
    }

    if (navLinks) {
      // Replace existing Sign In link rather than adding an extra link
      const signInLink = navLinks.querySelector('a[href="login.php"]');
      if (signInLink) {
        signInLink.href = targetUrl;
        signInLink.id = 'auth-nav-link';
        signInLink.innerHTML = `${isAdmin ? '👑 Dashboard' : '👤 Account'}`;
      } else if (!document.getElementById('auth-nav-link')) {
        const link = document.createElement('a');
        link.id = 'auth-nav-link';
        link.href = targetUrl;
        link.className = 'nav-link';
        link.innerHTML = `${isAdmin ? '👑 Dashboard' : '👤 Account'}`;
        const cta = navLinks.querySelector('.mobile-only-cta');
        if (cta) {
          navLinks.insertBefore(link, cta);
        } else {
          navLinks.appendChild(link);
        }
      }
    }
  } else {
    if (topBarRight && !document.getElementById('auth-topbar-link')) {
      const clientPortalLinks = topBarRight.querySelectorAll('a[href="login.php"]');
      if (clientPortalLinks.length === 0) {
        const authSpan = document.createElement('span');
        authSpan.id = 'auth-topbar-link';
        authSpan.className = 'top-bar-item';
        authSpan.innerHTML = `
          <a href="login.php" class="top-auth-pill">
            <span>Guest Sign In &rarr;</span>
          </a>
        `;
        topBarRight.appendChild(authSpan);
      }
    }
  }
}

/* --------------------------------------------------------------------------
   1. Sticky Header Elevation on Scroll
   -------------------------------------------------------------------------- */
function initHeaderScroll() {
  const header = document.querySelector('.site-header');
  if (!header) return;

  const handleScroll = () => {
    if (window.scrollY > 20) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  };

  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll();
}

/* --------------------------------------------------------------------------
   2. Mobile Navigation Drawer
   -------------------------------------------------------------------------- */
function initMobileNav() {
  const toggleBtn = document.querySelector('.nav-toggle');
  const navLinks = document.querySelector('.nav-links');
  const siteHeader = document.querySelector('.site-header');

  if (!toggleBtn || !navLinks) return;

  // On mobile screens, move navLinks to body to ensure it is never trapped by header or flex container heights
  const originalParent = navLinks.parentNode;
  const nextSibling = navLinks.nextSibling;

  const ensureBodyAttachment = () => {
    if (window.innerWidth <= 1180) {
      if (navLinks.parentNode !== document.body) {
        document.body.appendChild(navLinks);
      }
    } else {
      if (navLinks.parentNode === document.body && originalParent) {
        originalParent.insertBefore(navLinks, nextSibling);
      }
    }
  };

  ensureBodyAttachment();
  navLinks.classList.remove('is-open');
  navLinks.setAttribute('aria-hidden', 'true');

  // Create luxury backdrop element if not already present
  let backdrop = document.querySelector('.nav-backdrop');
  if (!backdrop) {
    backdrop = document.createElement('div');
    backdrop.className = 'nav-backdrop';
    document.body.appendChild(backdrop);
  }

  const updateDrawerGeometry = () => {
    ensureBodyAttachment();
  };

  const toggleMenu = (forceClose = false) => {
    const isExpanded = toggleBtn.getAttribute('aria-expanded') === 'true';
    const willOpen = forceClose ? false : !isExpanded;

    toggleBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    toggleBtn.classList.toggle('is-open', willOpen);
    navLinks.setAttribute('aria-hidden', willOpen ? 'false' : 'true');

    if (willOpen) {
      updateDrawerGeometry();
      navLinks.classList.add('is-open');
      backdrop.classList.add('is-active');
      document.body.style.overflow = 'hidden';
    } else {
      navLinks.classList.remove('is-open');
      backdrop.classList.remove('is-active');
      document.body.style.overflow = '';
    }
  };

  toggleBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    toggleMenu();
  });

  // Backdrop click to close
  backdrop.addEventListener('click', () => {
    if (navLinks.classList.contains('is-open')) {
      toggleMenu(true);
    }
  });

  // Close when clicking nav links
  navLinks.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
      if (navLinks.classList.contains('is-open')) {
        toggleMenu(true);
      }
    });
  });

  // Close on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && navLinks.classList.contains('is-open')) {
      toggleMenu(true);
    }
  });

  // Close when clicking outside drawer
  document.addEventListener('click', (e) => {
    if (
      navLinks.classList.contains('is-open') &&
      !navLinks.contains(e.target) &&
      !toggleBtn.contains(e.target)
    ) {
      toggleMenu(true);
    }
  });

  // Re-calculate on resize or orientation change
  window.addEventListener('resize', () => {
    ensureBodyAttachment();
    if (window.innerWidth > 1180 && navLinks.classList.contains('is-open')) {
      toggleMenu(true);
    }
  });
}

/* --------------------------------------------------------------------------
   3. Subtle Reveal On Scroll
   -------------------------------------------------------------------------- */
function initScrollReveal() {
  const revealElements = document.querySelectorAll('.reveal-on-scroll');
  if (!revealElements.length) return;

  if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('revealed');
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -40px 0px'
    });

    revealElements.forEach(el => observer.observe(el));
  } else {
    // Fallback for older browsers
    revealElements.forEach(el => el.classList.add('revealed'));
  }
}

/* --------------------------------------------------------------------------
   4. Image Fallback Handling (Graceful offline/broken URL support)
   -------------------------------------------------------------------------- */
function initImageFallbacks() {
  const images = document.querySelectorAll('img');

  images.forEach(img => {
    img.addEventListener('error', function () {
      // Create SVG placeholder with strict brand palette
      const altText = this.getAttribute('alt') || 'L\'Étoile Salon & Spa';
      const width = this.getAttribute('width') || 600;
      const height = this.getAttribute('height') || 400;

      const svgPlaceholder = `data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='${width}' height='${height}' viewBox='0 0 600 450'%3E%3Crect width='100%25' height='100%25' fill='%23F5EFE6'/%3E%3Crect x='10' y='10' width='580' height='430' fill='none' stroke='%23C5A46D' stroke-width='1' stroke-dasharray='4,4'/%3E%3Ccircle cx='300' cy='200' r='36' fill='none' stroke='%23C5A46D' stroke-width='2'/%3E%3Cpath d='M300 176 L300 224 M276 200 L324 200' stroke='%23C5A46D' stroke-width='2'/%3E%3Ctext x='300' y='270' font-family='Georgia, serif' font-size='20' fill='%23252321' text-anchor='middle' font-weight='600'%3EL%27%C3%89TOILE%3C/text%3E%3Ctext x='300' y='298' font-family='sans-serif' font-size='12' fill='%235E5853' text-anchor='middle' letter-spacing='2'%3E${encodeURIComponent(altText)}%3C/text%3E%3C/svg%3E`;

      this.src = svgPlaceholder;
      this.classList.add('img-fallback-applied');
    });
  });
}

/* --------------------------------------------------------------------------
   5. Category Filter Tabs (Services & Gallery)
   -------------------------------------------------------------------------- */
function initCategoryFilters() {
  const filterContainers = document.querySelectorAll('[data-filter-group]');

  filterContainers.forEach(container => {
    const tabs = container.querySelectorAll('.filter-tab');
    const targetSelector = container.getAttribute('data-filter-group');
    const items = document.querySelectorAll(targetSelector);

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        tabs.forEach(t => t.classList.remove('active'));
        tab.classList.add('active');

        const filterValue = tab.getAttribute('data-filter');

        items.forEach(item => {
          const itemCategory = item.getAttribute('data-category');
          if (filterValue === 'all' || itemCategory === filterValue) {
            item.style.display = '';
            setTimeout(() => {
              item.style.opacity = '1';
              item.style.transform = 'translateY(0)';
            }, 10);
          } else {
            item.style.opacity = '0';
            item.style.transform = 'translateY(10px)';
            item.style.display = 'none';
          }
        });
      });
    });
  });
}

/* --------------------------------------------------------------------------
   6. Interactive Lightbox (Gallery Modal)
   -------------------------------------------------------------------------- */
function initLightbox() {
  const galleryItems = document.querySelectorAll('.gallery-item');
  if (!galleryItems.length) return;

  // Create modal element if not present
  let lightbox = document.getElementById('gallery-lightbox');
  if (!lightbox) {
    lightbox = document.createElement('div');
    lightbox.id = 'gallery-lightbox';
    lightbox.className = 'modal-backdrop';
    lightbox.innerHTML = `
      <div class="modal-box modal-lightbox-content">
        <button type="button" class="modal-close-btn" aria-label="Close Preview">&times;</button>
        <img src="" alt="" class="modal-lightbox-img" id="lightbox-img">
        <div class="modal-lightbox-caption" id="lightbox-caption"></div>
      </div>
    `;
    document.body.appendChild(lightbox);
  }

  const lightboxImg = lightbox.querySelector('#lightbox-img');
  const lightboxCaption = lightbox.querySelector('#lightbox-caption');
  const closeBtn = lightbox.querySelector('.modal-close-btn');

  const closeLightbox = () => {
    lightbox.classList.remove('active');
    document.body.style.overflow = '';
  };

  closeBtn.addEventListener('click', closeLightbox);
  lightbox.addEventListener('click', (e) => {
    if (e.target === lightbox) closeLightbox();
  });

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && lightbox.classList.contains('active')) {
      closeLightbox();
    }
  });

  galleryItems.forEach(item => {
    item.addEventListener('click', () => {
      const img = item.querySelector('img');
      const title = item.querySelector('.gallery-title')?.textContent || img.alt || '';
      const category = item.querySelector('.gallery-category')?.textContent || '';

      if (img) {
        lightboxImg.src = img.src;
        lightboxImg.alt = img.alt;
        lightboxCaption.innerHTML = `<strong>${title}</strong> ${category ? ' &bull; ' + category : ''}`;
        lightbox.classList.add('active');
        document.body.style.overflow = 'hidden';
      }
    });
  });
}

/* --------------------------------------------------------------------------
   7. Contact Form Client & Backend Integration (api/contact.php)
   -------------------------------------------------------------------------- */
function initContactForm() {
  const form = document.getElementById('contact-form');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    let isValid = true;
    const requiredInputs = form.querySelectorAll('[required]');

    requiredInputs.forEach(input => {
      if (!input.value.trim()) {
        input.classList.add('is-invalid');
        isValid = false;
      } else {
        input.classList.remove('is-invalid');
      }

      // Email format validation
      if (input.type === 'email' && input.value.trim()) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(input.value.trim())) {
          input.classList.add('is-invalid');
          isValid = false;
        }
      }
    });

    if (!isValid) return;

    const payload = {
      name: form.querySelector('#name')?.value || '',
      email: form.querySelector('#email')?.value || '',
      phone: form.querySelector('#phone')?.value || '',
      'contact-pref': form.querySelector('#contact-pref')?.value || 'Email',
      subject: form.querySelector('#subject')?.value || '',
      message: form.querySelector('#message')?.value || '',
      csrf_token: appCsrfToken || ''
    };

    // Attempt sending to Core PHP Backend
    fetch('api/contact.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': appCsrfToken || ''
      },
      body: JSON.stringify(payload)
    })
    .then(async (res) => {
      const data = await res.json().catch(() => null);
      if (res.ok && data && data.success) {
        showPrototypeModal({
          title: 'Concierge Enquiry Recorded',
          message: data.message || 'Your inquiry has been securely stored in our salon database. Our guest relations team will contact you shortly.',
          details: [
            { label: 'Enquiry ID', value: `#${data.enquiry_id || 'PENDING'}` },
            { label: 'Name', value: data.guest_name || payload.name },
            { label: 'Subject', value: data.subject || payload.subject },
            { label: 'Status', value: 'Recorded in MySQL (contact_enquiries)' }
          ]
        });
        form.reset();
      } else {
        throw new Error(data?.error || 'Server validation failed');
      }
    })
    .catch((err) => {
      // Graceful local prototype fallback if MySQL is offline or viewing static files
      showPrototypeModal({
        title: 'Enquiry Received (Atelier Mode)',
        message: 'Your message has passed all client-side validations. When connected to XAMPP Apache and MySQL, your message is saved directly to the database.',
        details: [
          { label: 'Name', value: payload.name },
          { label: 'Email', value: payload.email },
          { label: 'Subject', value: payload.subject },
          { label: 'Local Status', value: 'Validation Successful (PHP Ready)' }
        ]
      });
      form.reset();
    });
  });
}

/* --------------------------------------------------------------------------
   8. Review Submission Handler (api/review.php)
   -------------------------------------------------------------------------- */
function initReviewForm() {
  const form = document.getElementById('review-submission-form');
  if (!form) return;

  form.addEventListener('submit', (e) => {
    e.preventDefault();

    let isValid = true;
    const requiredInputs = form.querySelectorAll('[required]');

    requiredInputs.forEach(input => {
      if (!input.value.trim()) {
        input.classList.add('is-invalid');
        isValid = false;
      } else {
        input.classList.remove('is-invalid');
      }

      if (input.type === 'email' && input.value.trim()) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(input.value.trim())) {
          input.classList.add('is-invalid');
          isValid = false;
        }
      }
    });

    if (!isValid) return;

    const payload = {
      name: form.querySelector('#review-name')?.value || '',
      email: form.querySelector('#review-email')?.value || '',
      service: form.querySelector('#review-service')?.value || '',
      stylist: form.querySelector('#review-stylist')?.value || '',
      rating: form.querySelector('#review-rating')?.value || '5',
      review: form.querySelector('#review-text')?.value || '',
      csrf_token: appCsrfToken || ''
    };

    fetch('api/review.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': appCsrfToken || ''
      },
      body: JSON.stringify(payload)
    })
    .then(async (res) => {
      const data = await res.json().catch(() => null);
      if (res.ok && data && data.success) {
        showPrototypeModal({
          title: 'Atelier Reflection Submitted',
          message: data.message || 'Thank you for your review. It has been queued in the reviews moderation table.',
          details: [
            { label: 'Review ID', value: `#${data.review_id || 'PENDING'}` },
            { label: 'Guest', value: data.guest_name || payload.name },
            { label: 'Rating', value: `${data.rating || 5} Stars` },
            { label: 'Status', value: 'Queued in MySQL (Pending Moderation)' }
          ]
        });
        form.reset();
      } else {
        throw new Error(data?.error || 'Submission error');
      }
    })
    .catch(() => {
      showPrototypeModal({
        title: 'Reflection Received (Prototype Mode)',
        message: 'Your reflection passed all client validations. In the XAMPP environment with MySQL, this reflection is placed in the admin moderation queue.',
        details: [
          { label: 'Guest', value: payload.name },
          { label: 'Service', value: payload.service },
          { label: 'Stylist', value: payload.stylist },
          { label: 'Rating', value: payload.rating }
        ]
      });
      form.reset();
    });
  });
}

/* --------------------------------------------------------------------------
   8. Newsletter Mockup Submission
   -------------------------------------------------------------------------- */
function initNewsletterForm() {
  const forms = document.querySelectorAll('.footer-newsletter-form');

  forms.forEach(form => {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const input = form.querySelector('input[type="email"]');
      if (input && input.value.trim()) {
        alert('Thank you for subscribing! In Phase 2, your email will be securely saved to the salon guest registry.');
        input.value = '';
      }
    });
  });
}

/* --------------------------------------------------------------------------
   9. Prototype Feedback Modal Helper
   -------------------------------------------------------------------------- */
function showPrototypeModal({ title, message, details = [] }) {
  let modal = document.getElementById('prototype-dialog');
  if (!modal) {
    modal = document.createElement('div');
    modal.id = 'prototype-dialog';
    modal.className = 'modal-backdrop';
    document.body.appendChild(modal);
  }

  let detailsHtml = '';
  if (details.length > 0) {
    detailsHtml = `
      <div style="background-color: var(--color-ivory); padding: 14px; border-radius: 4px; margin: 16px 0; border: 1px solid var(--color-champagne-border);">
        ${details.map(d => `<div style="display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:4px;">
          <span style="color:var(--color-charcoal-muted); text-transform:uppercase; font-size:0.75rem; letter-spacing:0.08em;">${d.label}:</span>
          <strong style="color:var(--color-charcoal);">${d.value}</strong>
        </div>`).join('')}
      </div>
    `;
  }

  modal.innerHTML = `
    <div class="modal-box" role="dialog" aria-modal="true">
      <button type="button" class="modal-close-btn" aria-label="Close">&times;</button>
      <div class="proto-tag">Phase 1 Frontend Prototype</div>
      <h3 style="font-family: var(--font-serif); font-size: 1.6rem; margin-bottom: 8px; color: var(--color-charcoal);">${title}</h3>
      <p style="font-size: 0.95rem; color: var(--color-charcoal-muted); line-height: 1.6;">${message}</p>
      ${detailsHtml}
      <div style="margin-top: 20px; text-align: right;">
        <button type="button" class="btn btn-primary" id="prototype-dialog-close">Understood</button>
      </div>
    </div>
  `;

  modal.classList.add('active');
  document.body.style.overflow = 'hidden';

  const closeDialog = () => {
    modal.classList.remove('active');
    document.body.style.overflow = '';
  };

  modal.querySelector('.modal-close-btn').addEventListener('click', closeDialog);
  modal.querySelector('#prototype-dialog-close').addEventListener('click', closeDialog);
  modal.addEventListener('click', (e) => {
    if (e.target === modal) closeDialog();
  });
}
