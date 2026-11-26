-- RealEstate CRM - MySQL Database Schema
-- Import this file in phpMyAdmin or MySQL CLI:
--   mysql -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS `realestate_crm`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `realestate_crm`;

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(150) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `leads` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `phone` VARCHAR(40) NOT NULL,
  `email` VARCHAR(150) DEFAULT '',
  `source` VARCHAR(60) NOT NULL DEFAULT 'Walk-in',
  `budget_min` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `budget_max` DECIMAL(15,2) NOT NULL DEFAULT 0,
  `requirement` VARCHAR(255) DEFAULT '',
  `property_type` VARCHAR(80) NOT NULL DEFAULT 'Any',
  `location_pref` VARCHAR(150) DEFAULT '',
  `status` ENUM('New','Contacted','Site Visit','Negotiation','Closed','Lost') NOT NULL DEFAULT 'New',
  `notes` TEXT,
  `followup_date` DATE DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_leads_status` (`status`),
  INDEX `idx_leads_followup` (`followup_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `properties` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(200) NOT NULL,
  `type` VARCHAR(80) NOT NULL,
  `location` VARCHAR(150) NOT NULL,
  `area_sqft` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `price` DECIMAL(15,2) NOT NULL,
  `status` ENUM('Available','Sold','Rented') NOT NULL DEFAULT 'Available',
  `description` TEXT,
  `bedrooms` INT NOT NULL DEFAULT 0,
  `bathrooms` INT NOT NULL DEFAULT 0,
  `listed_date` DATE DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_prop_status` (`status`),
  INDEX `idx_prop_type` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `followups` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `lead_id` INT UNSIGNED NOT NULL,
  `followup_date` DATE NOT NULL,
  `note` VARCHAR(255) DEFAULT '',
  `status` ENUM('Pending','Done') NOT NULL DEFAULT 'Pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_fu_date_status` (`followup_date`, `status`),
  CONSTRAINT `fk_followups_lead`
    FOREIGN KEY (`lead_id`) REFERENCES `leads`(`id`)
    ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `activities` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `type` VARCHAR(50) NOT NULL,
  `description` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `jv_projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `jv_id` VARCHAR(60) NOT NULL UNIQUE,
  `feasibility_by` VARCHAR(120) DEFAULT '',
  `updated_by` VARCHAR(120) DEFAULT '',
  `rea_no` VARCHAR(40) DEFAULT '',
  `ratings` VARCHAR(20) DEFAULT '',
  `google_pin` VARCHAR(500) DEFAULT '',
  `location` VARCHAR(255) DEFAULT '',
  `area` VARCHAR(150) DEFAULT '',
  `road_width` VARCHAR(100) DEFAULT '',
  `goodwill` VARCHAR(100) DEFAULT '',
  `sharing` VARCHAR(100) DEFAULT '',
  `loi_sent` VARCHAR(60) DEFAULT '',
  `mou` VARCHAR(60) DEFAULT '',
  `jda_llp` VARCHAR(60) DEFAULT '',
  `status` VARCHAR(60) DEFAULT 'Active',
  `notes` TEXT,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
