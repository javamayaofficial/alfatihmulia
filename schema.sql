-- =====================================================
-- Al Fatih Impact Platform (AIP) - Database Schema
-- MySQL / MariaDB - Shared Hosting Compatible
-- =====================================================
SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ---------- USERS (Admin, Donatur, Relawan) ----------
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(25) DEFAULT NULL,
  `password_hash` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('superadmin','admin_program','admin_keuangan','donatur','relawan') NOT NULL DEFAULT 'donatur',
  `referral_code` VARCHAR(40) DEFAULT NULL,
  `avatar` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_email` (`email`),
  UNIQUE KEY `uniq_referral` (`referral_code`),
  KEY `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- PROGRAMS ----------
CREATE TABLE IF NOT EXISTS `programs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(160) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `category` VARCHAR(80) NOT NULL DEFAULT 'umum',
  `excerpt` VARCHAR(300) DEFAULT NULL,
  `description` TEXT,
  `image` VARCHAR(255) DEFAULT NULL,
  `target_amount` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `collected_amount` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `beneficiaries` INT UNSIGNED NOT NULL DEFAULT 0,
  `video_url` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active','draft','closed') NOT NULL DEFAULT 'active',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_slug` (`slug`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- PROGRAM LOCATIONS (titik sebaran untuk peta) ----------
CREATE TABLE IF NOT EXISTS `program_points` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `program_id` INT UNSIGNED DEFAULT NULL,
  `name` VARCHAR(160) NOT NULL,
  `province` VARCHAR(80) DEFAULT NULL,
  `lat` DECIMAL(10,6) DEFAULT NULL,
  `lng` DECIMAL(10,6) DEFAULT NULL,
  `beneficiaries` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_program` (`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- DONATIONS ----------
CREATE TABLE IF NOT EXISTS `donations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `invoice` VARCHAR(40) NOT NULL,
  `donor_name` VARCHAR(120) DEFAULT NULL,
  `donor_phone` VARCHAR(25) DEFAULT NULL,
  `donor_email` VARCHAR(150) DEFAULT NULL,
  `is_anonymous` TINYINT(1) NOT NULL DEFAULT 0,
  `amount` BIGINT UNSIGNED NOT NULL DEFAULT 0,
  `category` VARCHAR(60) NOT NULL DEFAULT 'sedekah',
  `program_id` INT UNSIGNED DEFAULT NULL,
  `prayer` VARCHAR(500) DEFAULT NULL,
  `payment_method` VARCHAR(40) NOT NULL DEFAULT 'transfer',
  `frequency` ENUM('once','monthly') NOT NULL DEFAULT 'once',
  `referral_code` VARCHAR(40) DEFAULT NULL,
  `proof_file` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('pending','verified','rejected') NOT NULL DEFAULT 'pending',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `verified_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_invoice` (`invoice`),
  KEY `idx_status` (`status`),
  KEY `idx_program` (`program_id`),
  KEY `idx_referral` (`referral_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- ARTICLES (CMS) ----------
CREATE TABLE IF NOT EXISTS `articles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug` VARCHAR(180) NOT NULL,
  `title` VARCHAR(220) NOT NULL,
  `excerpt` VARCHAR(300) DEFAULT NULL,
  `content` TEXT,
  `image` VARCHAR(255) DEFAULT NULL,
  `author` VARCHAR(120) DEFAULT 'Admin Yayasan',
  `category` VARCHAR(80) DEFAULT 'Berita',
  `status` ENUM('published','draft') NOT NULL DEFAULT 'published',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- PARTNERS ----------
CREATE TABLE IF NOT EXISTS `partners` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(160) NOT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `category` VARCHAR(80) DEFAULT 'Mitra',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- TESTIMONIALS ----------
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(120) NOT NULL,
  `role` VARCHAR(120) DEFAULT NULL,
  `message` VARCHAR(500) NOT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- SETTINGS (key-value, termasuk kunci API) ----------
CREATE TABLE IF NOT EXISTS `settings` (
  `skey` VARCHAR(80) NOT NULL,
  `svalue` TEXT,
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- IMPACT STATS (counter manual yang tak terderivasi) ----------
CREATE TABLE IF NOT EXISTS `impact_stats` (
  `skey` VARCHAR(80) NOT NULL,
  `label` VARCHAR(120) NOT NULL,
  `svalue` BIGINT NOT NULL DEFAULT 0,
  `icon` VARCHAR(40) DEFAULT 'star',
  `sort` INT NOT NULL DEFAULT 0,
  PRIMARY KEY (`skey`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------- AUDIT LOG ----------
CREATE TABLE IF NOT EXISTS `audit_log` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED DEFAULT NULL,
  `user_name` VARCHAR(120) DEFAULT NULL,
  `action` VARCHAR(120) NOT NULL,
  `detail` VARCHAR(400) DEFAULT NULL,
  `ip` VARCHAR(45) DEFAULT NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET foreign_key_checks = 1;
