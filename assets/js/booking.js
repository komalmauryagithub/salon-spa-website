/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Interactive Appointment Booking Engine
 * Vanilla JavaScript (Zero External Libraries)
 */

let bookingCsrfToken = '';
let isUserAuthenticated = false;
let authenticatedCustomer = null;
const PENDING_BOOKING_KEY = 'letoile_pending_booking';

document.addEventListener('DOMContentLoaded', () => {
  initBookingEngine();
});

function autoFillGuestDetails(customer) {
  const nameInput = document.getElementById('guest-name');
  const emailInput = document.getElementById('guest-email');
  const phoneInput = document.getElementById('guest-phone');
  const notesInput = document.getElementById('guest-notes');

  if (nameInput) {
    nameInput.value = `${customer.first_name || ''} ${customer.last_name || ''}`.trim();
  }
  if (emailInput) {
    emailInput.value = customer.email || '';
  }
  if (phoneInput && customer.phone) {
    phoneInput.value = customer.phone || '';
  }
  if (notesInput && !notesInput.value && customer.allergies_notes) {
    notesInput.value = customer.allergies_notes;
  }
}

function initBookingEngine() {
  const bookingForm = document.getElementById('booking-form');
  if (!bookingForm) return;

  // Booking State Object
  const bookingState = {
    serviceId: '',
    serviceName: 'No Service Selected',
    servicePrice: '$0',
    serviceDuration: '—',
    stylistId: 'any',
    stylistName: 'Any Master Stylist Available',
    date: '',
    time: '',
    guestName: '',
    guestPhone: '',
    guestEmail: '',
    genderPref: 'Unspecified',
    notes: ''
  };

  // Steps handling
  let currentStep = 1;
  const totalSteps = 4;
  const stepPanels = document.querySelectorAll('.booking-step-panel');
  const stepIndicators = document.querySelectorAll('.step-indicator');
  const btnNext = document.getElementById('booking-next-btn');
  const btnPrev = document.getElementById('booking-prev-btn');
  const btnSubmit = document.getElementById('booking-submit-btn');

  // Summary elements
  const sumService = document.getElementById('summary-service-name');
  const sumDuration = document.getElementById('summary-duration');
  const sumStylist = document.getElementById('summary-stylist-name');
  const sumDate = document.getElementById('summary-date');
  const sumTime = document.getElementById('summary-time');
  const sumTotal = document.getElementById('summary-total-price');

  // 1. Check URL parameters for pre-selection
  const urlParams = new URLSearchParams(window.location.search);
  const preSelectedService = urlParams.get('service');
  const preSelectedStylist = urlParams.get('stylist');

  // Initialize Date Input with today's minimum
  const dateInput = document.getElementById('booking-date');
  if (dateInput) {
    const today = new Date().toISOString().split('T')[0];
    dateInput.setAttribute('min', today);
  }

  // Update Summary display
  function updateSummary() {
    if (sumService) sumService.textContent = bookingState.serviceName;
    if (sumDuration) sumDuration.textContent = bookingState.serviceDuration;
    if (sumStylist) sumStylist.textContent = bookingState.stylistName;
    if (sumDate) sumDate.textContent = bookingState.date || 'Not Selected';
    if (sumTime) sumTime.textContent = bookingState.time || 'Not Selected';
    if (sumTotal) sumTotal.textContent = bookingState.servicePrice;
  }

  // Live Slot Availability Fetcher
  function fetchLiveSlots() {
    if (!bookingState.date) return;

    const noticeEl = document.getElementById('time-slots-notice');
    const containerEl = document.getElementById('time-slots-container');
    const indicatorEl = document.getElementById('salon-hours-indicator');

    if (indicatorEl) indicatorEl.textContent = 'Checking availability...';

    const params = new URLSearchParams({
      date: bookingState.date,
      service_slug: bookingState.serviceId,
      staff_slug: bookingState.stylistId
    });

    fetch(`api/available-slots.php?${params.toString()}`)
      .then(r => r.json())
      .then(res => {
        if (!res || !res.success || !res.data) return;
        const data = res.data;

        if (indicatorEl) {
          indicatorEl.textContent = data.is_open ? `${data.day_name}: ${data.salon_hours || 'Open'}` : 'Closed';
        }

        if (data.service) {
          bookingState.serviceDuration = `${data.service.duration_minutes} mins`;
          bookingState.servicePrice = data.service.formatted_price;
          updateSummary();
        }

        if (!data.is_open) {
          if (noticeEl) {
            noticeEl.style.display = 'block';
            noticeEl.innerHTML = `<strong>Atelier Schedule Note:</strong> ${data.reason || 'Our atelier is closed on this date.'}`;
          }
          if (containerEl) {
            containerEl.innerHTML = '<p style="font-size:0.85rem; color:var(--color-charcoal-muted); padding:10px 0;">No appointments can be reserved on this date. Please select another calendar date above.</p>';
          }
          bookingState.time = '';
          updateSummary();
          return;
        }

        if (data.reason && (!data.slots || data.slots.length === 0)) {
          if (noticeEl) {
            noticeEl.style.display = 'block';
            noticeEl.innerHTML = `<strong>Specialist Schedule:</strong> ${data.reason}`;
          }
          if (containerEl) {
            containerEl.innerHTML = '<p style="font-size:0.85rem; color:var(--color-charcoal-muted); padding:10px 0;">No available slots with this specialist on this date. Please select another specialist or date.</p>';
          }
          bookingState.time = '';
          updateSummary();
          return;
        }

        if (noticeEl) {
          noticeEl.style.display = 'none';
          noticeEl.textContent = '';
        }

        // Group slots into Morning, Afternoon, Evening
        const morning = [];
        const afternoon = [];
        const evening = [];

        (data.slots || []).forEach(slot => {
          const hour = parseInt(slot.time.split(':')[0], 10);
          if (hour < 12) {
            morning.push(slot);
          } else if (hour < 17) {
            afternoon.push(slot);
          } else {
            evening.push(slot);
          }
        });

        function renderGroup(title, slots) {
          if (slots.length === 0) return '';
          let html = `<p style="font-size:0.75rem; text-transform:uppercase; letter-spacing:0.1em; color:var(--color-champagne-dark); margin: 8px 0 4px 0;">${title}</p>`;
          html += '<div class="time-slots-grid" style="margin-bottom: var(--space-md);">';
          slots.forEach(s => {
            const isAvail = s.available;
            const isSelected = (bookingState.time === s.label || bookingState.time === s.time);
            html += `<button type="button" class="time-slot-btn ${isAvail ? '' : 'unavailable'} ${isSelected ? 'selected' : ''}" data-time="${s.label}" data-time-24="${s.time}" ${!isAvail ? 'disabled title="' + (s.reason || 'Reserved') + '"' : ''}>${s.label}</button>`;
          });
          html += '</div>';
          return html;
        }

        if (containerEl) {
          containerEl.innerHTML = renderGroup('Morning Sessions', morning) + 
                                  renderGroup('Afternoon Sessions', afternoon) + 
                                  renderGroup('Evening Twilight Sessions', evening);

          // Re-bind click event on all newly rendered slot buttons
          containerEl.querySelectorAll('.time-slot-btn').forEach(btn => {
            btn.addEventListener('click', () => {
              if (btn.classList.contains('unavailable')) return;
              containerEl.querySelectorAll('.time-slot-btn').forEach(b => b.classList.remove('selected'));
              btn.classList.add('selected');
              bookingState.time = btn.getAttribute('data-time') || btn.textContent.trim();
              updateSummary();
            });
          });
        }
      })
      .catch(() => {
        if (indicatorEl) indicatorEl.textContent = '';
      });
  }

  // Handle Step Switching
  function goToStep(step) {
    if (step < 1 || step > totalSteps) return;

    // Validate current step before advancing
    if (step > currentStep) {
      if (!validateStep(currentStep)) return;
    }

    currentStep = step;

    // Toggle panels
    stepPanels.forEach(panel => {
      const panelStep = parseInt(panel.getAttribute('data-step'), 10);
      panel.style.display = panelStep === currentStep ? 'block' : 'none';
    });

    // Update Indicators
    stepIndicators.forEach(ind => {
      const indStep = parseInt(ind.getAttribute('data-step'), 10);
      ind.classList.remove('active', 'completed');
      if (indStep === currentStep) {
        ind.classList.add('active');
      } else if (indStep < currentStep) {
        ind.classList.add('completed');
      }
    });

    // Button states
    if (btnPrev) {
      btnPrev.style.display = currentStep === 1 ? 'none' : 'inline-flex';
    }
    if (btnNext) {
      btnNext.style.display = currentStep === totalSteps ? 'none' : 'inline-flex';
    }
    if (btnSubmit) {
      btnSubmit.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
    }

    // Scroll to top of booking card
    const bookingContainer = document.querySelector('.booking-card-wrapper');
    if (bookingContainer) {
      bookingContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // When entering step 3 (Date/Time), trigger live slot availability check
    if (currentStep === 3) {
      if (!bookingState.date && dateInput && dateInput.value) {
        bookingState.date = dateInput.value;
      }
      if (bookingState.date) {
        fetchLiveSlots();
      }
    }

    // When entering step 4 (Client Information), reflect live authentication state
    if (currentStep === 4) {
      const authCard = document.getElementById('auth-required-step4');
      const badge = document.getElementById('auth-user-badge');
      if (isUserAuthenticated) {
        if (authCard) authCard.style.display = 'none';
        if (badge) badge.style.display = 'flex';
      } else {
        if (authCard) authCard.style.display = 'block';
        if (badge) badge.style.display = 'none';
      }
    }
  }

  // Step Validation logic
  function validateStep(step) {
    let isValid = true;
    const currentPanel = document.querySelector(`.booking-step-panel[data-step="${step}"]`);
    if (!currentPanel) return true;

    if (step === 1) {
      if (!bookingState.serviceId) {
        alert('Please select a salon or spa service to continue.');
        return false;
      }
    } else if (step === 3) {
      const dateVal = dateInput ? dateInput.value : '';
      if (!dateVal) {
        alert('Please choose an appointment date.');
        if (dateInput) dateInput.focus();
        return false;
      }
      if (!bookingState.time) {
        alert('Please select a preferred available time slot.');
        return false;
      }
    }

    return isValid;
  }

  // Bind Service Selection
  const serviceCards = document.querySelectorAll('.service-select-card');
  serviceCards.forEach(card => {
    card.addEventListener('click', () => {
      serviceCards.forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');

      const radio = card.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;

      bookingState.serviceId = card.getAttribute('data-service-id') || radio?.value || '';
      bookingState.serviceName = card.getAttribute('data-service-name') || 'Selected Service';
      bookingState.servicePrice = card.getAttribute('data-service-price') || '$0';
      bookingState.serviceDuration = card.getAttribute('data-service-duration') || '45 mins';

      updateSummary();
      if (currentStep >= 3 && bookingState.date) {
        fetchLiveSlots();
      }
    });
  });

  // Pre-select service from URL
  if (preSelectedService) {
    const matchingCard = document.querySelector(`.service-select-card[data-service-id="${preSelectedService}"]`);
    if (matchingCard) {
      matchingCard.click();
    }
  } else if (serviceCards.length > 0) {
    // Default to first service
    serviceCards[0].click();
  }

  // Bind Stylist Selection
  const stylistCards = document.querySelectorAll('.stylist-select-card');
  stylistCards.forEach(card => {
    card.addEventListener('click', () => {
      stylistCards.forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');

      const radio = card.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;

      bookingState.stylistId = card.getAttribute('data-stylist-id') || radio?.value || 'any';
      bookingState.stylistName = card.getAttribute('data-stylist-name') || 'Any Master Stylist';

      updateSummary();
      if (currentStep >= 3 && bookingState.date) {
        fetchLiveSlots();
      }
    });
  });

  // Pre-select stylist from URL
  if (preSelectedStylist) {
    const matchingStylist = document.querySelector(`.stylist-select-card[data-stylist-id="${preSelectedStylist}"]`);
    if (matchingStylist) {
      matchingStylist.click();
    }
  }

  // Date input change
  if (dateInput) {
    dateInput.addEventListener('change', (e) => {
      bookingState.date = e.target.value;
      updateSummary();
      fetchLiveSlots();
    });
  }

  // Initial Time slot buttons binding (fallback)
  const timeSlotButtons = document.querySelectorAll('.time-slot-btn');
  timeSlotButtons.forEach(slot => {
    slot.addEventListener('click', () => {
      if (slot.classList.contains('unavailable')) return;
      timeSlotButtons.forEach(s => s.classList.remove('selected'));
      slot.classList.add('selected');

      bookingState.time = slot.getAttribute('data-time') || slot.textContent.trim();
      updateSummary();
    });
  });

  // Step Indicators Navigation Click
  stepIndicators.forEach(ind => {
    ind.addEventListener('click', () => {
      const targetStep = parseInt(ind.getAttribute('data-step'), 10);
      if (targetStep < currentStep) {
        goToStep(targetStep);
      } else if (targetStep > currentStep) {
        goToStep(targetStep);
      }
    });
  });

  // Next / Prev Buttons
  if (btnNext) {
    btnNext.addEventListener('click', () => {
      goToStep(currentStep + 1);
    });
  }

  if (btnPrev) {
    btnPrev.addEventListener('click', () => {
      goToStep(currentStep - 1);
    });
  }

  // Pending booking persistence
  function savePendingBooking() {
    try {
      sessionStorage.setItem(PENDING_BOOKING_KEY, JSON.stringify(bookingState));
    } catch (e) {}
  }

  function restorePendingBooking() {
    try {
      const raw = sessionStorage.getItem(PENDING_BOOKING_KEY);
      if (!raw) return;
      const saved = JSON.parse(raw);
      if (saved) {
        if (saved.serviceId) {
          const card = document.querySelector(`.service-select-card[data-service-id="${saved.serviceId}"]`);
          if (card) card.click();
        }
        if (saved.stylistId && saved.stylistId !== 'any') {
          const stfCard = document.querySelector(`.stylist-select-card[data-stylist-id="${saved.stylistId}"]`);
          if (stfCard) stfCard.click();
        }
        if (saved.date) {
          if (dateInput) {
            dateInput.value = saved.date;
            bookingState.date = saved.date;
            fetchLiveSlots();
          }
        }
        if (saved.time) {
          bookingState.time = saved.time;
        }
        updateSummary();
      }
      sessionStorage.removeItem(PENDING_BOOKING_KEY);
    } catch (e) {}
  }

  function showAuthRequiredModal() {
    const modal = document.getElementById('auth-required-modal');
    if (modal) {
      modal.style.display = 'flex';
      document.body.style.overflow = 'hidden';
    } else {
      window.location.href = 'login.php?redirect=book-appointment.html&reason=booking';
    }
  }

  function closeAuthModal() {
    const modal = document.getElementById('auth-required-modal');
    if (modal) {
      modal.style.display = 'none';
      document.body.style.overflow = '';
    }
  }

  const modalCloseBtn = document.getElementById('modal-auth-close-btn');
  if (modalCloseBtn) {
    modalCloseBtn.addEventListener('click', closeAuthModal);
  }
  const modalEl = document.getElementById('auth-required-modal');
  if (modalEl) {
    modalEl.addEventListener('click', (e) => {
      if (e.target === modalEl) closeAuthModal();
    });
  }

  // Bind auth link clicks to persist booking choices across login redirect
  document.querySelectorAll('#step4-auth-login-btn, #step4-auth-reg-btn, #modal-auth-login-link, #modal-auth-reg-link, #booking-auth-banner a').forEach(el => {
    el.addEventListener('click', () => {
      savePendingBooking();
    });
  });

  // Fetch CSRF and Customer Authentication Status
  function syncAuthStatus() {
    fetch('api/csrf.php')
      .then(r => r.json())
      .then(d => {
        if (d && d.csrf_token) bookingCsrfToken = d.csrf_token;
        if (d && d.is_authenticated) {
          isUserAuthenticated = true;
          authenticatedCustomer = d.customer;

          // Hide unauthenticated banner
          const authBanner = document.getElementById('booking-auth-banner');
          if (authBanner) authBanner.style.display = 'none';

          // Step 4 verified badge & auto-fill
          const badgeEl = document.getElementById('auth-user-badge');
          const nameEl = document.getElementById('auth-badge-name');
          const emailEl = document.getElementById('auth-badge-email');
          const step4AuthCard = document.getElementById('auth-required-step4');

          if (step4AuthCard) step4AuthCard.style.display = 'none';
          if (badgeEl) badgeEl.style.display = 'flex';
          if (nameEl) nameEl.textContent = d.customer ? `${d.customer.first_name || ''} ${d.customer.last_name || ''}`.trim() : 'Atelier Client';
          if (emailEl) emailEl.textContent = d.customer ? (d.customer.email || '') : '';

          if (d.customer) {
            autoFillGuestDetails(d.customer);
          }

          // Restore previously saved booking selections if returning from login
          restorePendingBooking();
        } else {
          isUserAuthenticated = false;
          authenticatedCustomer = null;

          // Show unauthenticated banner
          const authBanner = document.getElementById('booking-auth-banner');
          if (authBanner) authBanner.style.display = 'block';

          // Show Step 4 sign-in prompt card
          const badgeEl = document.getElementById('auth-user-badge');
          const step4AuthCard = document.getElementById('auth-required-step4');
          if (badgeEl) badgeEl.style.display = 'none';
          if (step4AuthCard) step4AuthCard.style.display = 'block';
        }
      })
      .catch(() => {
        isUserAuthenticated = false;
        const authBanner = document.getElementById('booking-auth-banner');
        if (authBanner) authBanner.style.display = 'block';
        const step4AuthCard = document.getElementById('auth-required-step4');
        if (step4AuthCard) step4AuthCard.style.display = 'block';
      });
  }
  syncAuthStatus();

  // Final Submission Handling
  bookingForm.addEventListener('submit', (e) => {
    e.preventDefault();

    // Enforce authentication before submission
    if (!isUserAuthenticated) {
      savePendingBooking();
      showAuthRequiredModal();
      return;
    }

    const fullNameInput = document.getElementById('guest-name');
    const phoneInput = document.getElementById('guest-phone');
    const emailInput = document.getElementById('guest-email');
    const notesInput = document.getElementById('guest-notes');
    const genderInput = document.querySelector('input[name="gender"]:checked');
    const silentCheck = document.getElementById('silent-appointment');

    let isFinalValid = true;

    [fullNameInput, phoneInput, emailInput].forEach(inp => {
      if (inp && !inp.value.trim()) {
        inp.classList.add('is-invalid');
        isFinalValid = false;
      } else if (inp) {
        inp.classList.remove('is-invalid');
      }
    });

    if (emailInput && emailInput.value.trim()) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(emailInput.value.trim())) {
        emailInput.classList.add('is-invalid');
        isFinalValid = false;
      }
    }

    if (!isFinalValid) {
      alert('Please complete all required fields marked with an asterisk (*).');
      return;
    }

    bookingState.guestName = fullNameInput ? fullNameInput.value.trim() : '';
    bookingState.guestPhone = phoneInput ? phoneInput.value.trim() : '';
    bookingState.guestEmail = emailInput ? emailInput.value.trim() : '';
    bookingState.genderPref = genderInput ? genderInput.value : 'Unisex / Flexible';
    bookingState.notes = notesInput ? notesInput.value.trim() : '';
    const silentVal = silentCheck && silentCheck.checked ? 1 : 0;

    const payload = {
      service_id: bookingState.serviceId,
      service_slug: bookingState.serviceId,
      stylist_id: bookingState.stylistId,
      stylist_slug: bookingState.stylistId,
      date: bookingState.date,
      time: bookingState.time,
      guest_name: bookingState.guestName,
      guest_phone: bookingState.guestPhone,
      guest_email: bookingState.guestEmail,
      gender_pref: bookingState.genderPref,
      notes: bookingState.notes,
      silent_appointment: silentVal,
      csrf_token: bookingCsrfToken || (typeof appCsrfToken !== 'undefined' ? appCsrfToken : '')
    };

    if (btnSubmit) {
      btnSubmit.disabled = true;
      btnSubmit.innerHTML = '<span>Verifying Schedule...</span>';
    }

    // Submit to Core PHP Booking Endpoint with Concurrency Checks
    fetch('api/book.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': bookingCsrfToken || (typeof appCsrfToken !== 'undefined' ? appCsrfToken : '')
      },
      body: JSON.stringify(payload)
    })
    .then(async (res) => {
      const data = await res.json().catch(() => null);

      if (btnSubmit) {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<span>Confirm Reservation</span><span class="btn-icon">&rarr;</span>';
      }

      // Check for authentication requirement response
      if (res.status === 401 || (data && data.auth_required)) {
        savePendingBooking();
        showAuthRequiredModal();
        return;
      }

      // Handle Concurrency Conflict (Double booking prevention)
      if (res.status === 409) {
        alert(data?.error || 'We apologize, but this time slot was just reserved by another visitor. Please choose an alternate slot.');
        goToStep(3);
        fetchLiveSlots();
        return;
      }

      if (res.ok && data && data.success) {
        if (typeof showPrototypeModal === 'function') {
          showPrototypeModal({
            title: `Reservation Confirmed (Ref: ${data.booking_reference})`,
            message: data.message || 'Your appointment has been registered in the atelier calendar with pending concierge confirmation.',
            details: [
              { label: 'Booking Ref', value: data.booking_reference },
              { label: 'Guest Name', value: data.details?.guest_name || bookingState.guestName },
              { label: 'Ritual', value: data.details?.service_name || bookingState.serviceName },
              { label: 'Specialist', value: data.details?.stylist_name || bookingState.stylistName },
              { label: 'Schedule', value: `${data.details?.date || bookingState.date} at ${data.details?.time || bookingState.time}` },
              { label: 'Service Duration', value: data.details?.duration || bookingState.serviceDuration },
              { label: 'Total Price', value: data.details?.estimated_price || bookingState.servicePrice },
              { label: 'Atmosphere', value: data.details?.silent_service || 'Standard Consultation' },
              { label: 'Status', value: 'Pending Concierge Review' }
            ]
          });
        } else {
          alert(`Reservation Confirmed! Reference: ${data.booking_reference}`);
        }
        goToStep(1);
        bookingForm.reset();
      } else {
        throw new Error(data?.error || 'Booking reservation could not be processed.');
      }
    })
    .catch((err) => {
      if (btnSubmit) {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<span>Confirm Reservation</span><span class="btn-icon">&rarr;</span>';
      }

      if (err && err.message && (err.message.includes('401') || err.message.toLowerCase().includes('sign in') || err.message.toLowerCase().includes('auth'))) {
        savePendingBooking();
        showAuthRequiredModal();
        return;
      }

      alert(err.message || 'Booking reservation could not be processed. Please check your connection and try again.');
    });
  });

  // Initial step setup
  goToStep(1);
  updateSummary();
}
