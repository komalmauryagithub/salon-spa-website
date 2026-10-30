-- =============================================================================
-- L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
-- Database Schema Definition (Phase 3)
-- Target DBMS: MySQL 5.7+ / MySQL 8.0+ / MariaDB 10.3+ (XAMPP Environment)
-- Engine: InnoDB | Character Set: utf8mb4 | Collation: utf8mb4_unicode_ci
-- =============================================================================

-- Disable foreign key checks during initialization
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

-- -----------------------------------------------------------------------------
-- 1. Database Initialization
-- -----------------------------------------------------------------------------
CREATE DATABASE IF NOT EXISTS `letoile_salon_db`
  DEFAULT CHARACTER SET utf8mb4
  DEFAULT COLLATE utf8mb4_unicode_ci;

USE `letoile_salon_db`;

-- -----------------------------------------------------------------------------
-- 2. Drop existing tables if re-importing (ordered to satisfy dependencies)
-- -----------------------------------------------------------------------------
DROP TABLE IF EXISTS `salon_settings`;
DROP TABLE IF EXISTS `contact_enquiries`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `gallery_images`;
DROP TABLE IF EXISTS `appointments`;
DROP TABLE IF EXISTS `offers`;
DROP TABLE IF EXISTS `staff_services`;
DROP TABLE IF EXISTS `services`;
DROP TABLE IF EXISTS `service_categories`;
DROP TABLE IF EXISTS `staff`;
DROP TABLE IF EXISTS `customers`;
DROP TABLE IF EXISTS `users`;

-- -----------------------------------------------------------------------------
-- 3. Table: users
-- Core system authentication for administrators, managers, and stylists
-- -----------------------------------------------------------------------------
CREATE TABLE `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL COMMENT 'BCrypt hash (PASSWORD_BCRYPT) - never plaintext',
  `role` ENUM('admin', 'manager', 'receptionist', 'stylist', 'customer') NOT NULL DEFAULT 'customer',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` DATETIME NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_users_username` (`username`),
  UNIQUE KEY `uk_users_email` (`email`),
  KEY `idx_users_role` (`role`),
  KEY `idx_users_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Administrative and staff user credentials';

-- -----------------------------------------------------------------------------
-- 4. Table: customers
-- Dedicated guest profiles (Normalized to prevent redundant data across appointments)
-- -----------------------------------------------------------------------------
CREATE TABLE `customers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Optional link if customer creates self-service account in future',
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(25) NOT NULL,
  `gender_preference` ENUM('Unisex / Flexible', 'Women\'s Styling Focus', 'Men\'s Barbering Focus') NOT NULL DEFAULT 'Unisex / Flexible',
  `allergies_notes` TEXT NULL DEFAULT NULL COMMENT 'Chemical sensitivities, product allergies, or silent appointment preference',
  `is_vip` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_customers_email` (`email`),
  KEY `idx_customers_phone` (`phone`),
  KEY `idx_customers_name` (`last_name`, `first_name`),
  KEY `fk_customers_user_id` (`user_id`),
  CONSTRAINT `fk_customers_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registered guests and salon clients';

-- -----------------------------------------------------------------------------
-- 5. Table: staff
-- Master stylists, colorists, barbers, and holistic spa directors
-- -----------------------------------------------------------------------------
CREATE TABLE `staff` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Staff login user account link',
  `slug` VARCHAR(60) NOT NULL COMMENT 'URL identifier e.g. clara-valmont, matches frontend query params',
  `full_name` VARCHAR(80) NOT NULL,
  `role_title` VARCHAR(100) NOT NULL COMMENT 'e.g. Founder & Creative Director, Lead Colourist & Balayage',
  `bio` TEXT NOT NULL,
  `specialties` VARCHAR(255) NOT NULL COMMENT 'Comma-separated tags e.g. French Bob, Precision Dry Cutting',
  `experience_years` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `avatar_url` VARCHAR(255) NULL DEFAULT NULL,
  `working_days` VARCHAR(100) NOT NULL DEFAULT 'Tue,Wed,Thu,Fri,Sat',
  `is_available` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_slug` (`slug`),
  UNIQUE KEY `uk_staff_user_id` (`user_id`),
  KEY `idx_staff_available` (`is_available`),
  KEY `idx_staff_display_order` (`display_order`),
  CONSTRAINT `fk_staff_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Salon stylists, colorists, and spa specialists';

-- -----------------------------------------------------------------------------
-- 6. Table: service_categories
-- Relational category hierarchy matching frontend tabs
-- -----------------------------------------------------------------------------
CREATE TABLE `service_categories` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(50) NOT NULL COMMENT 'Category identifier matching data-filter tabs',
  `name` VARCHAR(80) NOT NULL,
  `description` TEXT NULL DEFAULT NULL,
  `display_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_categories_slug` (`slug`),
  KEY `idx_categories_active` (`is_active`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Service categorization';

-- -----------------------------------------------------------------------------
-- 7. Table: services
-- Hair, spa, skincare, and barbering service catalog with sample pricing
-- -----------------------------------------------------------------------------
CREATE TABLE `services` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category_id` INT UNSIGNED NOT NULL,
  `slug` VARCHAR(80) NOT NULL COMMENT 'Service slug e.g. haircut, balayage, matches frontend booking links',
  `name` VARCHAR(120) NOT NULL,
  `short_description` VARCHAR(255) NOT NULL,
  `full_description` TEXT NULL DEFAULT NULL,
  `gender_suitability` ENUM('Unisex', 'Women', 'Men', 'Customized') NOT NULL DEFAULT 'Unisex',
  `duration_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Sample base price in USD',
  `image_url` VARCHAR(255) NULL DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `display_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_services_slug` (`slug`),
  KEY `idx_services_category` (`category_id`),
  KEY `idx_services_featured` (`is_featured`),
  KEY `idx_services_active` (`is_active`),
  CONSTRAINT `fk_services_category` FOREIGN KEY (`category_id`) REFERENCES `service_categories` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Salon and spa treatment menu';

-- -----------------------------------------------------------------------------
-- 8. Table: staff_services
-- Junction table: maps which stylists provide which services with optional customized rates
-- -----------------------------------------------------------------------------
CREATE TABLE `staff_services` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `staff_id` INT UNSIGNED NOT NULL,
  `service_id` INT UNSIGNED NOT NULL,
  `custom_price` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Allows Master Director custom rate if applicable',
  `custom_duration_minutes` SMALLINT UNSIGNED NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_staff_service` (`staff_id`, `service_id`),
  KEY `idx_staff_services_service` (`service_id`),
  CONSTRAINT `fk_staff_services_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_staff_services_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stylist service assignments and custom rates';

-- -----------------------------------------------------------------------------
-- 9. Table: offers
-- Seasonal packages, bridal suites, and promotional duos matching packages.html
-- -----------------------------------------------------------------------------
CREATE TABLE `offers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(80) NOT NULL COMMENT 'e.g. executive-package, radiance-package',
  `title` VARCHAR(120) NOT NULL,
  `badge_text` VARCHAR(50) NULL DEFAULT NULL COMMENT 'e.g. Most Requested, Guest Favourite',
  `short_description` VARCHAR(255) NOT NULL,
  `original_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `package_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estimated_duration_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 90,
  `included_features` TEXT NOT NULL COMMENT 'JSON or newline-delimited checklist of included treatments',
  `start_date` DATE NULL DEFAULT NULL,
  `end_date` DATE NULL DEFAULT NULL,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_offers_slug` (`slug`),
  KEY `idx_offers_active` (`is_active`, `is_featured`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Curated seasonal multi-service packages';

-- -----------------------------------------------------------------------------
-- 10. Table: appointments
-- Booking engine core connecting customers, stylists, services, and packages
-- -----------------------------------------------------------------------------
CREATE TABLE `appointments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `booking_reference` VARCHAR(20) NOT NULL COMMENT 'Human-friendly reference code e.g. LETOILE-2026-X892',
  `customer_id` INT UNSIGNED NOT NULL,
  `staff_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL signifies Any Available Master Specialist',
  `service_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Linked service or NULL if booked as standalone package offer',
  `offer_id` INT UNSIGNED NULL DEFAULT NULL COMMENT 'Linked package offer if applicable',
  `appointment_date` DATE NOT NULL,
  `appointment_time` TIME NOT NULL,
  `duration_minutes` SMALLINT UNSIGNED NOT NULL DEFAULT 60,
  `total_price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('pending', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show') NOT NULL DEFAULT 'pending',
  `silent_appointment` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Guest preference for quiet service without conversational dialogue',
  `notes` TEXT NULL DEFAULT NULL COMMENT 'Guest notes, color history, product sensitivities',
  `cancellation_reason` VARCHAR(255) NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_appointments_ref` (`booking_reference`),
  KEY `idx_appointments_customer` (`customer_id`),
  KEY `idx_appointments_staff` (`staff_id`),
  KEY `idx_appointments_service` (`service_id`),
  KEY `idx_appointments_offer` (`offer_id`),
  KEY `idx_appointments_date_time` (`appointment_date`, `appointment_time`),
  KEY `idx_appointments_status` (`status`),
  CONSTRAINT `fk_appointments_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_appointments_offer` FOREIGN KEY (`offer_id`) REFERENCES `offers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Client appointment scheduling records';

-- -----------------------------------------------------------------------------
-- 11. Table: gallery_images
-- Portfolio records matching gallery.html
-- -----------------------------------------------------------------------------
CREATE TABLE `gallery_images` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(50) NOT NULL COMMENT 'haircuts, colour, spa, interior',
  `title` VARCHAR(120) NOT NULL,
  `image_url` VARCHAR(255) NOT NULL,
  `alt_text` VARCHAR(255) NOT NULL,
  `display_order` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_gallery_category` (`category`),
  KEY `idx_gallery_active` (`is_active`, `display_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Portfolio photo showcase';

-- -----------------------------------------------------------------------------
-- 12. Table: reviews
-- Customer feedback and editorial testimonials matching reviews.html
-- -----------------------------------------------------------------------------
CREATE TABLE `reviews` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `customer_id` INT UNSIGNED NULL DEFAULT NULL,
  `service_id` INT UNSIGNED NULL DEFAULT NULL,
  `staff_id` INT UNSIGNED NULL DEFAULT NULL,
  `guest_name` VARCHAR(80) NOT NULL,
  `guest_email` VARCHAR(100) NOT NULL,
  `rating` TINYINT UNSIGNED NOT NULL DEFAULT 5,
  `review_title` VARCHAR(150) NULL DEFAULT NULL,
  `review_text` TEXT NOT NULL,
  `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
  `is_featured` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reviews_customer` (`customer_id`),
  KEY `idx_reviews_service` (`service_id`),
  KEY `idx_reviews_staff` (`staff_id`),
  KEY `idx_reviews_status` (`status`),
  KEY `idx_reviews_rating` (`rating`),
  CONSTRAINT `fk_reviews_customer` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_service` FOREIGN KEY (`service_id`) REFERENCES `services` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_reviews_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Guest reviews and moderation';

-- -----------------------------------------------------------------------------
-- 13. Table: contact_enquiries
-- Contact submissions from contact.html and homepage
-- -----------------------------------------------------------------------------
CREATE TABLE `contact_enquiries` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `full_name` VARCHAR(80) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(25) NULL DEFAULT NULL,
  `preferred_contact_method` ENUM('Email', 'Phone Call', 'WhatsApp') NOT NULL DEFAULT 'Email',
  `subject` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('new', 'in_progress', 'responded', 'archived') NOT NULL DEFAULT 'new',
  `admin_notes` TEXT NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `responded_at` DATETIME NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_enquiries_status` (`status`),
  KEY `idx_enquiries_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Guest concierge inquiries and messages';

-- -----------------------------------------------------------------------------
-- 14. Table: salon_settings
-- Key-value metadata configuration for salon business parameters
-- -----------------------------------------------------------------------------
CREATE TABLE `salon_settings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `setting_key` VARCHAR(60) NOT NULL,
  `setting_value` TEXT NULL DEFAULT NULL,
  `setting_group` VARCHAR(40) NOT NULL DEFAULT 'general' COMMENT 'general, contact, hours, booking, social',
  `description` VARCHAR(255) NULL DEFAULT NULL,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_settings_key` (`setting_key`),
  KEY `idx_settings_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Dynamic salon business settings and metadata';

-- Re-enable foreign key constraints
SET FOREIGN_KEY_CHECKS = 1;
