# L'ÉTOILE — Luxury Unisex Hair Salon & Spa (Phase 8 Complete Salon Management Suite)

> **Phase 8 Status:** Complete. Production-grade **Salon Management Modules (Services Catalog & Categories CRUD, Specialists Roster & Service Eligibility, Promotional Offers & Server-Side Discounts, Secure Gallery Uploads & Moderation, Customer Reviews Moderation, Confidential Concierge Enquiries Triage, Dynamic Salon Settings & Hours, and Financial Analytics Reports with CSV Export)** fully implemented. Built with Core PHP, PDO, MySQL, HTML5, CSS3, and Vanilla JavaScript adhering strictly to the **Ivory (`#F5EFE6`)**, **Champagne (`#C5A46D`)**, **Charcoal (`#252321`)**, and **White (`#FFFFFF`)** luxury brand aesthetic.

---

## 1. Strict Brand Palette & CSS Tokens

This project strictly adheres to the approved 4-color palette across all public, customer sanctuary, and administrative concierge pages:

| Token Name | Hex Code | Purpose & Application |
|---|---|---|
| **Ivory** | `#F5EFE6` | Primary background, warm neutral surfaces, card contrast accents |
| **Champagne** | `#C5A46D` | Editorial accents, badges, subtle borders, icons, button details |
| **Charcoal** | `#252321` | Primary typography, navigation links, dark contrast sections, footer |
| **White** | `#FFFFFF` | Content cards, form backgrounds, elevated surfaces |

All CSS tokens are declared in `assets/css/style.css`. *Zero arbitrary colors (pink, purple, blue, green, or generic gradients) are introduced.*

---

## 2. Complete Project Architecture & File Inventory

```
c:/xampp/htdocs/Hair salon and spa/
├── .htaccess                   # Root Apache security rules, headers, file protections
├── database.sql                # Complete MySQL DDL schema (12 InnoDB tables with foreign keys & indexes)
├── seed.sql                    # Seed demonstration data with staff, services, and sample appointments
├── index.html                  # Public Homepage
├── about.html                  # About Atelier & Unisex Ethos
├── services.html               # Full Unisex Service Menu
├── hair-services.html          # Hair Architecture & Comparison Table
├── spa-services.html           # Japanese Head Spa & Dermal Facials
├── packages.html               # Curated Seasonal Multi-Service Packages
├── gallery.html                # Visual Portfolio with Lightbox Modal
├── team.html                   # Master Specialists & Bios
├── reviews.html                # Guest Testimonials & Reflection Form
├── contact.html                # Atelier Information & Concierge Enquiry Form
├── book-appointment.html       # 4-Step Interactive Booking Engine (live slots & auto-fill)
├── login.php                   # Customer & Concierge Sign In (Routes staff to admin-dashboard.php)
├── register.php                # Customer Registration with Password Hashing & Auto-Login
├── logout.php                  # Secure Session Destruction & Cookie Expiration
├── customer-profile.php        # Protected Customer Sanctuary: Itinerary, Profile Edit & 24h Cancel
├── admin-dashboard.php         # Central Executive Concierge Dashboard & Management Modules Hub
├── admin-appointments.php      # Full Appointments Roster, Filters & Modal Dossier
├── admin-customers.php         # Patron Directory, Search, VIP Filters & Spend Metrics
├── admin-customer-detail.php   # Detailed Patron Dossier, Sensory Notes & Treatment History
├── admin-staff.php             # [ENHANCED Phase 8] Specialist Roster, Service Eligibility Matrix & CRUD
├── admin-services.php          # [ENHANCED Phase 8] Services Catalog, Category Management & CRUD
├── admin-offers.php            # [NEW Phase 8] Promotional Packages, Validity Windows & Discounts
├── admin-gallery.php           # [NEW Phase 8] Visual Portfolio Showcase & Secure Upload Management
├── admin-reviews.php           # [NEW Phase 8] Customer Reflections & Reviews Moderation Console
├── admin-enquiries.php         # [NEW Phase 8] Private Contact Inquiries Triage & Confidential Notes
├── admin-reports.php           # [NEW Phase 8] Appointment Volume, Realized Revenue & CSV Export
├── admin-settings.php          # [NEW Phase 8] Dynamic Salon Business Settings, Hours & Policies
├── setup-admin.php             # Initial Administrator Account Initialization Utility
├── assets/
│   ├── css/
│   │   ├── style.css           # Luxury design system & design tokens
│   │   └── responsive.css      # Fluid layouts & mobile off-canvas drawer
│   ├── js/
│   │   ├── main.js             # Nav, dynamic auth status updates, category filters, lightbox
│   │   └── booking.js          # Live slot calculation, dynamic slots, 409 conflict handling
│   └── images/
│       ├── logo.svg            # Champagne & Charcoal brand wordmark
│       └── favicon.svg         # Étoile monogram favicon
├── config/
│   ├── .htaccess               # Blocks direct browser access to configuration
│   ├── config.example.php      # Safe local configuration template
│   ├── config.php              # Active local environment settings
│   └── database.php            # Reusable PDO singleton with credential masking
├── includes/
│   ├── .htaccess               # Blocks direct browser access to core logic
│   ├── admin-nav.php           # [NEW Phase 7] Shared Luxury Admin Navigation Bar & Role Badge
│   ├── bootstrap.php           # Core orchestrator: sessions, error/exception handling, autoloader
│   ├── logger.php              # Structured logging utility (logs/app.log) with credential redaction
│   ├── validator.php           # Strict input validation, data sanitization, and output escaping
│   ├── response.php            # JSON response envelopes, flash messages, and redirects
│   ├── csrf.php                # Cryptographic CSRF token generation & constant-time validation
│   ├── auth.php                # Enhanced authentication engine: registration, login, IDOR & role guards
│   └── scheduler.php           # Scheduling engine: rosters, salon hours, slots, locking & rules
├── api/
│   ├── test-db.php             # Diagnostic endpoint checking MySQL connectivity
│   ├── csrf.php                # Returns CSRF token and active authentication status
│   ├── book.php                # Booking API with concurrency locking (FOR UPDATE)
│   ├── available-slots.php     # Live dynamic slot calculation endpoint
│   ├── cancel-appointment.php  # Customer self-cancellation API with 24-hour policy
│   ├── admin-appointment-status.php # Administrative status change API (confirm, complete, cancel)
│   ├── admin-appointment-detail.php # [NEW Phase 7] Detailed JSON appointment dossier inspection API
│   ├── contact.php             # Contact enquiry submission API
│   ├── review.php              # Guest reflection submission API (queued for moderation)
│   ├── services.php            # Categorized services catalog endpoint
│   └── staff.php               # Master stylist & specialist roster endpoint
├── actions/
│   ├── contact.php             # Standard synchronous POST fallback with flash redirects
│   └── book.php                # Synchronous booking fallback with scheduler verification
└── logs/
    ├── .htaccess               # Blocks direct browser access to logs
    └── app.log                 # Secure application log output
```

---

---

## 3. Phase 6 Core Scheduling Engine Specifications

### A. Salon Operating Hours & Schedules
Enforced by `Scheduler::getSalonHours($date)`:
- **Monday:** Closed (*"Our atelier is closed on Mondays for masterclasses and internal education."*)
- **Tuesday through Friday:** 09:00 AM – 08:30 PM (`09:00:00` to `20:30:00`)
- **Saturday and Sunday:** 09:30 AM – 07:00 PM (`09:30:00` to `19:00:00`)
- **Slot Granularity:** 30-minute intervals.
- **Duration Boundary Rule:** The last appointment slot must finish on or before closing time:
  $$\text{slot\_start} + (\text{duration\_minutes} \times 60) \le \text{close\_timestamp}$$

### B. Master Stylist Rosters (`staff.working_days`)
- **Clara Valmont (Founder & Director):** `Tue,Wed,Thu,Sat`
- **Julian Moreau (Lead Colourist):** `Wed,Thu,Fri,Sun`
- **Marcus Vance (Master Barber):** `Tue,Fri,Sat,Sun`
- **Elena Rostova (Spa & Dermal Director):** `Tue,Wed,Fri,Sat`
- **"Any Available Specialist" Mode:**
  Dynamically queries all active stylists working on that calendar day and assigns an available specialist who has no overlapping appointments for the required treatment duration.

### C. Concurrency Control & Double-Booking Prevention
Implemented in `Scheduler::verifySlotAndAssignStaff()` and `api/book.php`:
1. Runs inside an atomic database transaction: `$pdo->beginTransaction()`.
2. Applies row-level pessimistic locking:
   ```sql
   SELECT id, staff_id, appointment_time, duration_minutes 
   FROM appointments 
   WHERE staff_id = :sid 
     AND appointment_date = :dt 
     AND status IN ('pending', 'confirmed', 'in_progress')
   FOR UPDATE
   ```
3. Checks interval overlap condition:
   Two intervals $[A_{\text{start}}, A_{\text{end}})$ and $[B_{\text{start}}, B_{\text{end}})$ conflict if and only if:
   $$A_{\text{start}} < B_{\text{end}} \quad \text{AND} \quad B_{\text{start}} < A_{\text{end}}$$
4. If a conflict occurs, the transaction immediately rolls back and returns **HTTP 409 Conflict**:
   *"We apologize, but this time slot was just reserved by another visitor. Please select an alternate time slot."*
5. The frontend wizard (`assets/js/booking.js`) intercepts HTTP 409, informs the visitor, navigates back to Step 3, and dynamically refreshes available slots.

### D. 24-Hour Salon Cancellation Policy & IDOR Security
Enforced by `Scheduler::canCustomerCancel()` and `api/cancel-appointment.php`:
- **Eligibility Window:** Guests may cancel online up to 24 hours prior to their appointment time.
- **Late Notice (< 24 Hours):** The online cancellation button is deactivated, displaying an advisory notice with the concierge telephone line (`+1 800 555-0192`).
- **Terminal States:** Completed and already-cancelled appointments cannot be cancelled.
- **IDOR Protection:** The cancellation API verifies that `$appointment['customer_id'] === Auth::customerId()`. Any attempt to cancel another user's booking is blocked with HTTP 403 Forbidden and logged as a security alert.

### E. Administrative Concierge Management (`admin-appointments.php`)
- **Authorization Guard:** Restricted to `admin`, `manager`, and `receptionist` roles via `Auth::requireRole()`.
- **Live KPI Counters:** Pending Review, Today's Appointments, Confirmed Active, Total Bookings, and Revenue Forecast.
- **Multidimensional Filters:** Status (Pending, Confirmed, In Progress, Completed, Cancelled, No Show), Specialist, Timeline (Today, Upcoming, Past), and Search Query (Guest Name, Phone, Email, Reference).
- **Concierge Actions:** Instant status updating (`Confirm`, `Complete`, `No-Show`) and modal-assisted `Cancel` with mandatory administrative reason recording.

---

## 4. Walkthrough: Testing Phase 6 Features Locally in XAMPP

### Test 1: Live Available Slot Calculation on Date Selection
1. Open your browser to:
   ```
   http://localhost/Hair%20salon%20and%20spa/book-appointment.html
   ```
2. In **Step 1 (Select Service)**, choose *"Atelier Precision Haircut"* (60 mins).
3. In **Step 2 (Select Specialist)**, leave as *"Any Available Master Specialist"*.
4. In **Step 3 (Select Date & Time)**:
   - Pick an upcoming Tuesday or Wednesday date.
   - Notice the live hours indicator displays: `Tuesday: 09:00 AM – 08:30 PM`.
   - The available slots populate dynamically into **Morning Sessions**, **Afternoon Sessions**, and **Evening Twilight Sessions**.
5. Change the date to an upcoming **Monday**:
   - Notice the scheduling engine displays the atelier advisory:
     *"Our atelier is closed on Mondays for masterclasses and internal education."*
   - Slot buttons are suppressed.

### Test 2: Specialist Availability Roster Check
1. Go back to Step 2 and select **Elena Rostova** (Spa Director).
2. Note that Elena's working days are `Tue,Wed,Fri,Sat`.
3. Pick an upcoming **Sunday** in Step 3.
4. The system detects Elena is off-duty and displays:
   *"Elena Rostova is not scheduled to work on Sundays."*
5. Change the date to an upcoming **Tuesday** or **Friday**: slots become immediately available!

### Test 3: Double Booking Prevention (HTTP 409 Conflict)
1. Complete a reservation for a specific specialist (e.g., Clara Valmont) at a specific time (e.g., `10:30 AM`).
2. Open an incognito browser window and select the exact same specialist, date, and time slot.
3. Upon submission, the pessimistic lock detects the occupied interval, rolls back the transaction, and returns HTTP 409.
4. The frontend displays the conflict notification and returns the guest to Step 3 with that slot marked unavailable.

### Test 4: Customer Profile & 24-Hour Self-Cancellation
1. Sign in at `login.php` with sample account `user` or `user@gmail.com` (password `user123` or `user`). You can also click the **Auto-Fill User** button on `login.php`.
2. You are directed to `customer-profile.php`.
3. In the **Appointment History** timeline:
   - Note the **Atelier Cancellation Policy** banner explaining the 24-hour advance requirement.
   - For reservations scheduled &ge; 24 hours in advance, click **Cancel Reservation**.
   - An elegant modal prompts for an optional cancellation reason (e.g., *"Schedule conflict"*).
   - Upon clicking **Confirm Cancellation**, the status changes to `Cancelled` and the note is recorded in MySQL.
4. For appointments within 24 hours:
   - The cancel button is replaced with: *"Within 24h • Call Concierge: +1 (800) 555-0192"*.

### Test 5: Concierge Operations Desk & Authentication Routing
1. Sign in at `login.php` with administrator credentials:
   - **Identifier:** `admin` or `admin@gmail.com`
   - **Password:** `admin123` (or simply `admin`)
   *(You can also click the 1-click **Auto-Fill Admin** button on `login.php`).*
2. Staff accounts are automatically routed to the executive portal: `admin-dashboard.php`.
3. Unauthenticated visitors or standard customers attempting to access any admin route receive an **HTTP 403 Forbidden** security screen.

---

## 5. Phase 7 Executive Administrative Concierge Suite Walkthrough

Phase 7 elevates the salon platform with a full-featured, responsive, luxury administrative back-office system:

### A. Core Demonstration & Administrative Credentials
| Role | Username / Identifier | Email | Password | Dedicated Sign-In Portal | Landing Dashboard |
|---|---|---|---|---|---|
| **Managing Director (Admin)** | `admin` | `admin@gmail.com` | `admin123` (or `admin`) | `admin-login.php` | `admin-dashboard.php` |
| **Sanctuary Patron (User/Customer)** | `user` | `user@gmail.com` | `user123` (or `user`) | `login.php` | `customer-profile.php` |
| **Head Concierge (Receptionist)** | `concierge` | `concierge@letoile-salon-placeholder.com` | `Letoile@2026` | `admin-login.php` | `admin-dashboard.php` |

### B. Executive Concierge Dashboard (`admin-dashboard.php`)
- **Real-Time Atelier KPIs:**
  - **Today's Appointments:** Count of rituals scheduled for the current date, plus confirmed tally.
  - **Pending Requests:** Reservations awaiting concierge verification.
  - **Registered Patrons:** Total client registry count, highlighting VIP patrons.
  - **Realized Revenue:** Exact sum of `total_price` for `confirmed` and `completed` appointments (excluding cancelled/no-shows).
  - **Active Services Menu:** Live active treatments in catalog.
- **Daily Schedule:** Today's appointments table with appointment time, duration, guest name, service, specialist, status badge, and immediate actions (`Inspect`, `Confirm`, `Complete`, `Cancel`, `No Show`).
- **Pending Booking Requests Queue:** Immediate triage queue with one-click **Confirm** or **Cancel**.
- **Upcoming Confirmed Rituals:** Preview horizon of upcoming treatments with specialist assignment.
- **Recent Activity Feed:** Real multi-source stream aggregating recent reservations, customer registrations, guest reviews, and contact form submissions from actual database rows.
- **Instant Dossier Inspection Modal:** Click **Inspect** on any appointment to asynchronously fetch the complete booking, guest contact details, VIP status, styling preferences, allergy notes, quiet session requests, and treatment specifics without a page reload.

### C. Patron Directory & VIP Dossier (`admin-customers.php` & `admin-customer-detail.php`)
- **Directory Overview:** Search patrons by name, email, or telephone. Filter by VIP status or styling focus.
- **Patron Dossier (`admin-customer-detail.php`):**
  - **Patron Value Metrics:** Lifetime spend, total treatments booked, active rituals, and cancellation rate.
  - **VIP Status Toggle:** One-click VIP elevation or revocation with CSRF protection.
  - **Sensory & Allergy Notes:** Editable consultation notes for scent sensitivities, scalp allergies, or beverage preferences.
  - **Treatment Ledger:** Complete chronological history of every appointment booked by the patron with status badges and quick inspection.

### D. Specialist Roster Administration (`admin-staff.php`)
- **Duty Schedule Overview:** Specialist cards featuring role title, experience years, specialties, working days, and bio.
- **Live "On Duty Today" Detection:** Evaluates `staff.working_days` against the active server day (`date('D')`) and displays an *On Duty Today* champagne badge or an *Off Duty Today* indicator.
- **Workload Indicators:** Today's rituals count and upcoming active bookings per specialist.
- **Availability Toggle:** Concierge can switch specialists between *Available* and *On Leave / Sabbatical* with one click.

### E. Services & Package Catalog Oversight (`admin-services.php`)
- **Categorized Treatment Menus:** Grouped by category (`Hair Architecture`, `Japanese Head Spa`, `Dermal Facials`, etc.).
- **Performance Metrics:** Each service displays duration, price, total booking count, and realized revenue generated.
- **Active & Featured Toggles:** Easily enable/disable services or mark them as homepage featured highlights.
- **Seasonal Packages:** Overview of package offerings from the `offers` table.

### F. Shared Luxury Navigation Bar (`includes/admin-nav.php`)
- **Consistent Luxury Aesthetic:** Champagne top bar, active page indicators, role badge, quick "Walk-in Booking" shortcut, and mobile drawer toggle.
- **Strict Server-Side Access Control:** Every administrative page enforces `Auth::requireRole(['admin', 'manager', 'receptionist'])`. Unauthorized access is logged and halted before any markup renders.

---

## 6. Phase 8 Complete Salon Management Modules

Phase 8 implements the complete back-office operational modules for L'ÉTOILE salon directors and concierges:

### 1. Services & Treatment Catalog Management (`admin-services.php`)
- **Full CRUD:** Create new services, modify existing records, update prices, durations, descriptions, and suitability (`Unisex`, `Women`, `Men`, `Customized`).
- **Category Classification:** Create new categories or classify services into Haircuts & Precision Architecture, Couture Balayage, Japanese Head Spa, Facials, Barbering, and Bridal Couture.
- **Historical Appointment Preservation:** When a service is deactivated (`is_active = 0`), it is hidden from public booking selection, while all historical appointments, financials, customer profiles, and reports referencing the service remain 100% intact.
- **Category Management Modal:** Create and organize category display orders.

### 2. Specialists Roster & Service Eligibility (`admin-staff.php`)
- **Staff Profiles:** Full name, editorial role title, biographic narrative, specialties, experience years, working schedule days, and avatar URL.
- **Service Eligibility Matrix (`staff_services`):** Checkbox matrix linking each specialist to the specific catalog rituals they are qualified to perform.
- **Safe Deactivation / Sabbatical:** Deactivating staff (`is_available = 0`) gracefully excludes them from future slot allocations, while all historical appointments in `appointments.staff_id` remain permanently linked.

### 3. Promotional Packages & Server-Side Discounts (`admin-offers.php`)
- **Package Curation:** Create and edit promotional packages with title, badge tag, original value vs package price, duration, and newline-delimited included feature treatments (stored cleanly as JSON).
- **Validity Windows:** Set optional `start_date` and `end_date` validity boundaries.
- **Server-Side Discount & Validity Enforcement:** `api/book.php` verifies that package bookings and promotional discount codes check `is_active = 1` and `start_date <= CURDATE() AND end_date >= CURDATE()`. All discount mathematics are performed strictly server-side.

### 4. Visual Portfolio & Secure Image Uploads (`admin-gallery.php`)
- **Safe Upload Architecture (`uploads/gallery/`):**
  - **MIME Type Inspection:** Validated via PHP `finfo` (`image/jpeg`, `image/png`, `image/webp`, `image/gif`).
  - **Extension Whitelist:** Strict whitelist (`jpg`, `jpeg`, `png`, `webp`, `gif`).
  - **Integrity Verification:** Uses `getimagesize()` to confirm authentic image dimensions, preventing malicious polyglots.
  - **Randomized Naming:** Cryptographically random filenames (`letoile_YYYYMMDD_HHMMSS_[token].[ext]`).
  - **Execution Prevention:** `uploads/.htaccess` enforces `php_flag engine off` and denies execution of all scripts (`.php`, `.phtml`, `.cgi`, `.sh`, `.exe`).
- **Showcase Management:** Manage title, category (`haircuts`, `colour`, `spa`, `interior`), alt text, display order, featured homepage highlights, and deletions.

### 5. Customer Reflections & Reviews Moderation (`admin-reviews.php`)
- **Moderation Workflow:** Pending moderation triage queue, approved feedback, and rejected feedback.
- **Public Safety Guarantee:** Public reviews API (`api/review.php` GET) strictly queries `WHERE status = 'approved'`, never exposing rejected reviews or private guest email addresses.
- **Review Inspection:** Dossier modal detailing guest name, star rating, service experienced, specialist, and reflection text.

### 6. Confidential Concierge Enquiries Triage (`admin-enquiries.php`)
- **Private Correspondence Inbox:** Filter enquiries by status (`new`, `in_progress`, `responded`, `archived`).
- **Confidentiality:** Strictly protected behind administrative role authorization (`admin`, `manager`, `receptionist`). No public endpoint can query client communications.
- **Internal Notes:** Record confidential notes (e.g. phone call notes, bridal pricing quotes).
- **Workflow Automation:** Automatically updates `responded_at` timestamp and provides one-click `mailto:` composing.

### 7. Salon Settings, Operating Hours & Policies (`admin-settings.php`)
- **Key-Value Storage Engine (`includes/settings.php`):** Backed by MySQL `salon_settings` table.
- **Configurable Parameters:**
  - **Brand & Identity:** Salon Wordmark and Tagline.
  - **Contact Coordinates:** Address, Reception Phone, Support Email, WhatsApp number.
  - **Operating Schedules:** Weekdays (Tue-Fri), Weekends (Sat-Sun), and Monday Education/Masterclass status.
  - **Booking Policies:** Deposit Percentage, Cancellation Notice Limit (e.g., 24 hours), Advance Booking Horizon (Days), Currency Symbol.

### 8. Analytics, Real Revenue & CSV Reports (`admin-reports.php`)
- **Date Range Filters:** Today, Last 7 Days, This Month, Last Month, This Year, All Time, or Custom Date Range.
- **Strict Realized Revenue:** Calculated strictly from `completed` and `confirmed` bookings. Cancelled and No-Show appointments are explicitly excluded from revenue totals.
- **Popular Services Ranking:** Ranks services and package suites by actual booking demand and realized revenue.
- **Specialist Productivity:** Treatments performed, completed tally, and revenue generated per artisan.
- **Ledger Timeline:** Day-by-day activity and financial breakdown.
- **CSV Data Export:** Instant streaming download of comprehensive booking and financial reports (`letoile_salon_report_*.csv`).

