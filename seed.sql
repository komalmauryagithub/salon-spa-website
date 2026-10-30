-- =============================================================================
-- L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
-- Database Seed Data (Phase 3)
-- Sample realistic records for local testing and future PHP backend integration
-- IMPORTANT: All customer names, reviews, and credentials are safe demonstration records.
-- =============================================================================

USE `letoile_salon_db`;

-- Temporarily disable foreign keys for clean sequential inserts
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------------------------
-- 1. Seed: users
-- Passwords are encrypted with standard BCrypt hash (PASSWORD_BCRYPT).
-- Sample default password for all seed accounts: Letoile@2026
-- Hash: $2y$10$wO3r2Lp8tK5W4lQ8FqG4heoFkU3P9M1sBvR5A7dE9eF1gH2iJ3kLm
-- -----------------------------------------------------------------------------
INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(1, 'admin', 'admin@gmail.com', '$2y$12$TLDEukNMJV9RlXf12JWKP.jYdOxveCAWtg93ksqj9aHjW1M8dbHR2', 'admin', 1, NOW()),
(2, 'concierge', 'concierge@letoile-salon-placeholder.com', '$2y$10$wO3r2Lp8tK5W4lQ8FqG4heoFkU3P9M1sBvR5A7dE9eF1gH2iJ3kLm', 'receptionist', 1, NOW()),
(3, 'clara_valmont', 'clara@letoile-salon-placeholder.com', '$2y$10$wO3r2Lp8tK5W4lQ8FqG4heoFkU3P9M1sBvR5A7dE9eF1gH2iJ3kLm', 'stylist', 1, NOW()),
(4, 'julian_moreau', 'julian@letoile-salon-placeholder.com', '$2y$10$wO3r2Lp8tK5W4lQ8FqG4heoFkU3P9M1sBvR5A7dE9eF1gH2iJ3kLm', 'stylist', 1, NOW()),
(5, 'marcus_vance', 'marcus@letoile-salon-placeholder.com', '$2y$10$wO3r2Lp8tK5W4lQ8FqG4heoFkU3P9M1sBvR5A7dE9eF1gH2iJ3kLm', 'stylist', 1, NOW()),
(6, 'elena_rostova', 'elena@letoile-salon-placeholder.com', '$2y$10$wO3r2Lp8tK5W4lQ8FqG4heoFkU3P9M1sBvR5A7dE9eF1gH2iJ3kLm', 'stylist', 1, NOW()),
(7, 'user', 'user@gmail.com', '$2y$12$ifLH7xpmywTaSZZlSvLLceoA983IvYXS1xxr5BM2dPATuXPX5SlaW', 'customer', 1, NOW());

-- -----------------------------------------------------------------------------
-- 2. Seed: staff
-- Master stylists matching team.html
-- -----------------------------------------------------------------------------
INSERT INTO `staff` (`id`, `user_id`, `slug`, `full_name`, `role_title`, `bio`, `specialties`, `experience_years`, `avatar_url`, `working_days`, `is_available`, `display_order`) VALUES
(1, 3, 'clara-valmont', 'Clara Valmont', 'Founder & Creative Director', 'Trained in Paris and London, Clara spent over a decade styling editorial runway shows. Her signature is dry-cut hair architecture that moves weightlessly.', 'French Bob, Dry Precision, Bespoke Bridal', 14, 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=600&q=80', 'Tue,Wed,Thu,Sat', 1, 1),
(2, 4, 'julian-moreau', 'Julian Moreau', 'Lead Colourist & Balayage Director', 'Master colorist specializing in sunlit multi-dimensional blondes, seamless brunette contouring, and non-toxic chemical formulations.', 'French Balayage, Corrective Tone, Bond Building', 11, 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=600&q=80', 'Wed,Thu,Fri,Sun', 1, 2),
(3, 5, 'marcus-vance', 'Marcus Vance', 'Master Barber & Men\'s Grooming Lead', 'Third-generation master barber dedicated to classic British scissor-over-comb craft, bespoke taper fades, and straight-razor ceremonial shaves.', 'Scissor Fades, Straight-Razor, Beard Architecture', 9, 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=600&q=80', 'Tue,Fri,Sat,Sun', 1, 3),
(4, 6, 'elena-rostova', 'Elena Rostova', 'Spa & Dermal Director', 'Holistic esthetician and trichologist certified in Tokyo. Pioneering our Japanese ultrasonic head spa and micro-current facial contouring rituals.', 'Japanese Head Spa, Lymphatic Lift, Cryo Dermal Care', 12, 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=600&q=80', 'Tue,Wed,Fri,Sat', 1, 4);

-- -----------------------------------------------------------------------------
-- 3. Seed: service_categories
-- Matches data-filter tabs on services.html, hair-services.html, and spa-services.html
-- -----------------------------------------------------------------------------
INSERT INTO `service_categories` (`id`, `slug`, `name`, `description`, `display_order`, `is_active`) VALUES
(1, 'haircuts', 'Haircuts & Precision Architecture', 'Geometric dry and wet haircutting structured to bone morphology.', 1, 1),
(2, 'colour', 'Couture Balayage & Dimensional Colour', 'Freehand painting, gloss toning, and bond building.', 2, 1),
(3, 'head-spa', 'Japanese Head Spa & Scalp Health', 'Micro-mist waterfall hydrotherapy and trichological care.', 3, 1),
(4, 'facials', 'Cellular Facials & Dermal Care', 'Enzyme peels, cryo-sculpting, and lymphatic drainage.', 4, 1),
(5, 'massage', 'Therapeutic Massages & Body Rituals', 'Volcanic hot stones, cedarwood oils, and deep tissue fascial reset.', 5, 1),
(6, 'barber', 'Barbering & Gentlemen\'s Grooming', 'Traditional hot towel straight-razor shaves and scissor fades.', 6, 1),
(7, 'bridal', 'Haute Bridal & Gala Celebrations', 'Editorial up-styling, veil pinning, and milestones.', 7, 1);

-- -----------------------------------------------------------------------------
-- 4. Seed: services
-- Complete catalog with realistic sample pricing matching frontend prototype
-- -----------------------------------------------------------------------------
INSERT INTO `services` (`id`, `category_id`, `slug`, `name`, `short_description`, `full_description`, `gender_suitability`, `duration_minutes`, `price`, `image_url`, `is_featured`, `is_active`, `display_order`) VALUES
(1, 1, 'haircut', 'Atelier Precision Haircut', 'Unisex geometric haircut, wash & velvet blow-dry.', 'Structured dry and wet scissor cutting tailored to natural growth whorls, facial contours, and daily styling routines. Includes botanical scalp exfoliation and hot towel wrap.', 'Unisex', 60, 85.00, 'https://images.unsplash.com/photo-1562322140-8baeececf3df?auto=format&fit=crop&w=800&q=80', 1, 1, 1),
(2, 1, 'restyle', 'Parisian Textured Bob Restyle', 'Weightless razor-sculpted French bob framed to cheekbones.', 'Dramatic length transformation or classic Parisian bob architecture sculpted dry to highlight jawlines and movement with minimal styling effort.', 'Women', 75, 110.00, 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=800&q=80', 0, 1, 2),
(3, 2, 'balayage', 'Couture French Balayage', 'Hand-painted dimensional blonde/brunette with pH gloss.', 'Freehand French surface painting with soft feathered roots. Finished with our champagne shine glaze and Olaplex bond repair infusion.', 'Customized', 150, 195.00, 'https://images.unsplash.com/photo-1519699047748-de8e457a634e?auto=format&fit=crop&w=800&q=80', 1, 1, 3),
(4, 2, 'highlights', 'Full Dimensional Foil Highlighting', 'Micro-weave precision foiling for complete crown brightness.', 'Full head foil weaves delivering seamless blonde or bronze reflections from roots to ends. Includes acidic gloss toner and protein rinse.', 'Customized', 165, 220.00, 'https://images.unsplash.com/photo-1527799820374-dcf8d9d4a388?auto=format&fit=crop&w=800&q=80', 0, 1, 4),
(5, 2, 'glaze', 'Illuminating Glaze & Root Melt', 'Zero-lift demi-permanent gloss that locks down cuticles.', 'Acidic shine toner bath neutralizing brassy reflections while restoring mirror-like reflectivity without altering the natural base.', 'Unisex', 60, 90.00, 'https://images.unsplash.com/photo-1580489944761-15a19d654956?auto=format&fit=crop&w=800&q=80', 0, 1, 5),
(6, 3, 'hair-spa', 'Japanese Ultrasonic Head Spa', 'Micro-mist waterfall hydrotherapy & acupressure massage.', 'Volcanic clay scalp exfoliation, continuous circular waterfall ring, and cervical neck lymph drainage using pure tea tree and cedarwood oils.', 'Unisex', 75, 120.00, 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=800&q=80', 1, 1, 6),
(7, 3, 'peptide', 'Biotech Peptide Bond Cure', 'K18 bio-molecular peptide reversal of chemical damage.', 'Reconnects broken polypeptide keratin chains caused by bleaching and hot tools. Restores natural tensile strength and softness.', 'Unisex', 45, 75.00, 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&w=800&q=80', 0, 1, 7),
(8, 3, 'keratin', 'Clean Botanical Keratin Treatment', 'Fume-free amino acid smoothing and anti-humidity shield.', 'Zero formaldehyde or harsh chemicals. Seals cuticle layer with organic oils and botanical keratin, preventing humidity frizz for up to 4 months.', 'Unisex', 150, 260.00, 'https://images.unsplash.com/photo-1519699047748-de8e457a634e?auto=format&fit=crop&w=800&q=80', 0, 1, 8),
(9, 4, 'facial', 'Cellular Cryo Radiance Facial', 'Enzyme peel, frozen glass globes & gua sha lymph lift.', 'Multi-phase cellular revitalisation featuring enzyme exfoliation, cold-pressed peptides, and cryo-sculpting globes to restore radiant skin tone.', 'Unisex', 60, 145.00, 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=800&q=80', 1, 1, 9),
(10, 5, 'massage', 'Aromatherapy Hot Stone Ritual', 'Volcanic basalt river stones & cedarwood essential oils.', 'Heated basalt stones glide along spinal and shoulder muscle groups to release deep physical tension and untangle somatic fatigue.', 'Unisex', 90, 160.00, 'https://images.unsplash.com/photo-1544161515-4ab6ce6db874?auto=format&fit=crop&w=800&q=80', 1, 1, 10),
(11, 6, 'shave', 'Royal Straight-Razor Shave', 'Triple steamed eucalyptus towels & badger hair lather.', 'Traditional three-towel ritual, single-blade hot lather razor glide, and cold witch hazel compress with soothing botanical balm.', 'Men', 45, 65.00, 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?auto=format&fit=crop&w=800&q=80', 0, 1, 11),
(12, 6, 'barber-cut', 'Executive Barber Cut & Shave', 'Taper fade or scissor cut paired with hot towel straight razor.', 'Full British scissor-over-comb craft or taper fade with clean neck lines, ear contour, and traditional warm straight-razor shave.', 'Men', 75, 115.00, 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?auto=format&fit=crop&w=800&q=80', 0, 1, 12),
(13, 7, 'bridal', 'Haute Bridal & Gala Couture', 'Bespoke up-styling, veil architecture & emergency kit.', 'Intricate chignons, romantic wave architecture, and veil integration engineered to withstand high movement, lighting, and photography.', 'Women', 120, 240.00, 'https://images.unsplash.com/photo-1487412720507-e7ab37603c6f?auto=format&fit=crop&w=800&q=80', 1, 1, 13),
(14, 4, 'nails', 'Royal Russian Manicure & Pedicure', 'E-file diamond bit cuticle care & warm paraffin wrap.', 'Dry medical-grade cuticle detailing, organic paraffin warm bath, hand-foot massage, and non-toxic vegan lacquer or natural buff.', 'Unisex', 75, 95.00, 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=800&q=80', 0, 1, 14);

-- -----------------------------------------------------------------------------
-- 5. Seed: staff_services
-- Stylist capabilities matrix
-- -----------------------------------------------------------------------------
INSERT INTO `staff_services` (`staff_id`, `service_id`, `custom_price`) VALUES
(1, 1, 95.00),  -- Clara (Founder rate for precision cut)
(1, 2, 125.00), -- Clara (Parisian Bob)
(1, 13, 275.00),-- Clara (Bridal Director)
(2, 3, 215.00), -- Julian (Master Balayage)
(2, 4, 235.00), -- Julian (Full Highlights)
(2, 5, 95.00),  -- Julian (Glaze)
(3, 1, 85.00),  -- Marcus (Cuts)
(3, 11, 65.00), -- Marcus (Shaves)
(3, 12, 115.00),-- Marcus (Barber Duo)
(4, 6, 120.00), -- Elena (Head Spa)
(4, 7, 75.00),  -- Elena (Peptide)
(4, 9, 145.00), -- Elena (Facial)
(4, 10, 160.00);-- Elena (Massage)

-- -----------------------------------------------------------------------------
-- 6. Seed: offers
-- Multi-service packages matching packages.html
-- -----------------------------------------------------------------------------
INSERT INTO `offers` (`id`, `slug`, `title`, `badge_text`, `short_description`, `original_price`, `package_price`, `estimated_duration_minutes`, `included_features`, `is_featured`, `is_active`) VALUES
(1, 'executive-package', 'The Executive Revival', 'Gentlemen &bull; Short Hair', 'Complete sharp grooming and deep stress relief for urban professionals.', 160.00, 135.00, 90, '["Precision Scissor or Taper Fade Haircut", "Steamed Eucalyptus Towel Scalp Wrap", "Traditional Straight-Razor Beard Sculpt or Shave", "Cervical Neck Acupressure Tension Release", "Complimentary Espresso or Single Malt Beverage"]', 0, 1),
(2, 'radiance-package', 'The Signature Radiance', 'Most Requested', 'Holistic harmony combining precision hair sculpture, head spa, and cryo facial.', 265.00, 220.00, 135, '["Precision Geometric Haircut & Velvet Blowout", "Japanese Micro-Mist Waterfall Head Spa", "Cellular Cryo Radiance Gua Sha Facial", "Botanical Scalp Essence Take-Home Travel Gift"]', 1, 1),
(3, 'occasion-package', 'The Occasion Suite', 'Gala & Milestone', 'High-glamour preparation for galas, red carpet events, and celebrations.', 360.00, 295.00, 180, '["Haute Couture Chignon or Hollywood Waves", "Cellular Glow Primer Facial & Collagen Eye Patches", "Royal Russian Manicure with Gel Finish", "Chilled Glass of Champagne & Artisanal Macarons"]', 0, 1),
(4, 'couples-package', 'The Duet Harmony Retreat', 'Private Duo Suite', 'Side-by-side restorative pampering in our private double treatment suite.', 420.00, 350.00, 120, '["Side-by-Side 60-Minute Hot Stone Massages", "Dual Japanese Head Spa Waterfall Sessions", "Organic Warm Foot Soak & Botanical Scrub", "Private Lounge Access with Seasonal Elixirs"]', 0, 1);

-- -----------------------------------------------------------------------------
-- 7. Seed: customers
-- Safe demonstration guest profiles (No real personal data)
-- -----------------------------------------------------------------------------
INSERT INTO `customers` (`id`, `user_id`, `first_name`, `last_name`, `email`, `phone`, `gender_preference`, `allergies_notes`, `is_vip`, `created_at`) VALUES
(1, 7, 'Demo', 'User', 'user@gmail.com', '+1 (555) 019-2831', 'Women\'s Styling Focus', 'Prefers sulfate-free lavender wash; loves silent appointments.', 1, '2026-09-15 10:30:00'),
(2, NULL, 'Alexander', 'Laurent', 'alexander.l@example.org', '+1 (555) 019-5482', 'Unisex / Flexible', 'Desk worker; chronic upper trapezius tension.', 0, '2026-10-01 14:15:00'),
(3, NULL, 'Madeleine', 'Beaumont', 'madeleine.b@example.org', '+1 (555) 019-7729', 'Women\'s Styling Focus', 'Past bleach highlights; needs bond repair.', 1, '2026-10-10 11:00:00'),
(4, NULL, 'David', 'Kowalski', 'david.k@example.org', '+1 (555) 019-3384', 'Men\'s Barbering Focus', 'Sensitive neck skin; use pre-shave oil.', 0, '2026-10-18 16:45:00');

-- -----------------------------------------------------------------------------
-- 8. Seed: appointments
-- Sample schedule demonstrating various lifecycle statuses
-- -----------------------------------------------------------------------------
INSERT INTO `appointments` (`id`, `booking_reference`, `customer_id`, `staff_id`, `service_id`, `offer_id`, `appointment_date`, `appointment_time`, `duration_minutes`, `total_price`, `status`, `silent_appointment`, `notes`, `created_at`) VALUES
(1, 'LETOILE-26-A101', 1, 1, 1, NULL, '2026-11-03', '10:30:00', 60, 95.00, 'confirmed', 1, 'Silent appointment requested. Precision cut with dry styling.', '2026-10-25 09:12:00'),
(2, 'LETOILE-26-A102', 2, 4, 6, NULL, '2026-11-03', '14:30:00', 75, 120.00, 'confirmed', 0, 'First time head spa guest.', '2026-10-26 15:40:00'),
(3, 'LETOILE-26-A103', 3, 2, 3, NULL, '2026-11-04', '11:00:00', 150, 215.00, 'pending', 0, 'Champagne balayage touch-up with pH gloss tone.', '2026-10-28 08:30:00'),
(4, 'LETOILE-26-A104', 4, 3, NULL, 1, '2026-11-05', '16:00:00', 90, 135.00, 'confirmed', 0, 'Booked Executive Revival package.', '2026-10-28 17:05:00');

-- -----------------------------------------------------------------------------
-- 9. Seed: gallery_images
-- Matching portfolio categories on gallery.html
-- -----------------------------------------------------------------------------
INSERT INTO `gallery_images` (`id`, `category`, `title`, `image_url`, `alt_text`, `display_order`, `is_featured`, `is_active`) VALUES
(1, 'colour', 'Soft Champagne Balayage', 'https://images.unsplash.com/photo-1519699047748-de8e457a634e?auto=format&fit=crop&w=800&q=80', 'Soft champagne dimensional balayage hair coloring', 1, 1, 1),
(2, 'haircuts', 'Architectural French Bob', 'https://images.unsplash.com/photo-1562322140-8baeececf3df?auto=format&fit=crop&w=800&q=80', 'Modern textured razor bob precision cut', 2, 1, 1),
(3, 'haircuts', 'Tapered Fade & Beard Contour', 'https://images.unsplash.com/photo-1503951914875-452162b0f3f1?auto=format&fit=crop&w=800&q=80', 'Executive men scissor cut and tailored beard trim', 3, 1, 1),
(4, 'spa', 'Ultrasonic Scalp Steam Cure', 'https://images.unsplash.com/photo-1522337360788-8b13dee7a37e?auto=format&fit=crop&w=800&q=80', 'Sensory head spa steam relaxation session', 4, 1, 1),
(5, 'spa', 'Cryo-Sculpting Facial', 'https://images.unsplash.com/photo-1570172619644-dfd03ed5d881?auto=format&fit=crop&w=800&q=80', 'Soothing facial treatment with cold globes', 5, 1, 1),
(6, 'interior', 'Private Styling Atelier', 'https://images.unsplash.com/photo-1560066984-138dadb4c035?auto=format&fit=crop&w=800&q=80', 'L\'Étoile private styling suite with minimalist mirrors', 6, 1, 1);

-- -----------------------------------------------------------------------------
-- 10. Seed: reviews
-- Matching guest feedback on reviews.html
-- -----------------------------------------------------------------------------
INSERT INTO `reviews` (`id`, `customer_id`, `service_id`, `staff_id`, `guest_name`, `guest_email`, `rating`, `review_title`, `review_text`, `status`, `is_featured`, `created_at`) VALUES
(1, 1, 1, 1, 'Sophie Chen', 'sophie.chen@example.org', 5, 'Architectural Masterpiece', 'Clara transformed my unruly thick hair into a piece of architectural art. The quiet, private suite and gentle organic aromatics made it the most peaceful salon experience I have ever had.', 'approved', 1, '2026-10-12 14:20:00'),
(2, 2, 6, 4, 'Alexander Laurent', 'alexander.l@example.org', 5, 'Completely Eliminated Desk Tension', 'The Japanese Head Spa with Elena eliminated six months of accumulated desk tension. My scalp feels completely reset and my hair has a healthy, weightless bounce.', 'approved', 1, '2026-10-15 17:35:00'),
(3, 3, 3, 2, 'Madeleine Beaumont', 'madeleine.b@example.org', 5, 'Unmatched Freehand Balayage', 'Julian\'s balayage technique is unmatched. The colour blends effortlessly with my natural base as it grows out. No harsh roots, just luminous champagne warmth.', 'approved', 1, '2026-10-20 11:15:00'),
(4, 4, 12, 3, 'David Kowalski', 'david.k@example.org', 5, 'Cleanest Taper in the City', 'Marcus gave me the cleanest scissor taper and traditional straight-razor shave I\'ve ever experienced. The eucalyptus hot towels alone are worth the visit.', 'approved', 1, '2026-10-24 16:00:00');

-- -----------------------------------------------------------------------------
-- 11. Seed: contact_enquiries
-- Sample concierge notes matching contact.html
-- -----------------------------------------------------------------------------
INSERT INTO `contact_enquiries` (`id`, `full_name`, `email`, `phone`, `preferred_contact_method`, `subject`, `message`, `status`, `created_at`) VALUES
(1, 'Eleanor Vance', 'eleanor@example.org', '+1 (555) 012-9912', 'Email', 'Bridal & Event Inquiries', 'Inquiring about private atelier suite availability for bride and 4 attendants on June 18th.', 'in_progress', '2026-10-27 10:15:00'),
(2, 'Julianne Sterling', 'julianne@example.org', '+1 (555) 014-4421', 'Phone Call', 'Custom Hair Color Advice', 'Past allergic reaction to ammoniated salon color; seeking ingredient list for your demi-permanent gloss.', 'responded', '2026-10-26 13:45:00');

-- -----------------------------------------------------------------------------
-- 12. Seed: salon_settings
-- System configuration constants
-- -----------------------------------------------------------------------------
INSERT INTO `salon_settings` (`setting_key`, `setting_value`, `setting_group`, `description`) VALUES
('salon_name', 'L\'Étoile Hair Salon & Spa', 'general', 'Salon brand wordmark'),
('salon_tagline', 'Haute Coiffure & Holistic Spa Sanctuary', 'general', 'Editorial brand subtitle'),
('brand_color_ivory', '#F5EFE6', 'theme', 'Approved strict background color'),
('brand_color_champagne', '#C5A46D', 'theme', 'Approved strict accent color'),
('brand_color_charcoal', '#252321', 'theme', 'Approved strict typography and dark section color'),
('brand_color_white', '#FFFFFF', 'theme', 'Approved strict card surface color'),
('contact_address', '482 Boulevard Saint-Honoré, Suite 400, Design Quarter, NY 10021', 'contact', 'Public address placeholder'),
('contact_phone', '+1 (800) 555-0192', 'contact', 'Main receptionist telephone number'),
('contact_email', 'concierge@letoile-salon-placeholder.com', 'contact', 'Customer support and booking email'),
('contact_whatsapp', '+18005550192', 'contact', 'Direct WhatsApp concierge phone number without spaces'),
('hours_weekdays', '09:00 AM – 08:30 PM', 'hours', 'Tuesday through Friday hours'),
('hours_weekends', '09:30 AM – 07:00 PM', 'hours', 'Saturday and Sunday hours'),
('hours_monday', 'Closed for Masterclasses & Private Education', 'hours', 'Monday status'),
('appointment_deposit_percentage', '20', 'booking', 'Percentage required for future online reservation deposit'),
('currency_symbol', '$', 'booking', 'Active salon currency symbol');

-- Re-enable foreign key constraints
SET FOREIGN_KEY_CHECKS = 1;
