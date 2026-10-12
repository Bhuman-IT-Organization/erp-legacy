-- ============================================================
-- Face Recognition / Mobile Attendance API - Database Tables
--
-- Safe to run more than once: the CREATE statements are IF NOT EXISTS and the
-- ALTER statements are guarded, so this can be applied to a fresh database or to
-- one that already has an older version of these tables.
--
-- Run it against the database named in application/config/app-config.php
-- (APP_DB_NAME) - not against a hardcoded name.
--
-- NOTE ON THE ALTER STATEMENTS
-- "ADD COLUMN IF NOT EXISTS" needs MariaDB 10.0+, which is what both this
-- project's local XAMPP and its cPanel host run. On Oracle MySQL that clause does
-- not exist: drop the "IF NOT EXISTS" and run only the columns you are missing.
--
-- WHY THE ALTERS EXIST
-- The first version of this file created these tables without photo_path,
-- registered_by, source and project_id. The application writes all four
-- (Face_model::register(), Attendance_api_model::punch()), so a database created
-- from that earlier version has tables that exist but reject every insert. The
-- guarded ALTERs below bring such a database up to date.
-- ============================================================

-- ------------------------------------------------------------
-- Face Templates
-- One row per staff member, holding the face embedding used for verification.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tblface_templates` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `embedding` TEXT NOT NULL COMMENT 'JSON array of 128/512 floats',
    `photo_path` VARCHAR(500) DEFAULT NULL COMMENT 'Reference photo, relative to FCPATH',
    `device_id` VARCHAR(255) DEFAULT '' COMMENT 'Device that registered the face',
    `registered_by` INT(11) DEFAULT NULL COMMENT 'Staff id of the admin who registered it, NULL when self-registered',
    `source` ENUM('mobile', 'web') DEFAULT 'mobile' COMMENT 'Where the registration came from',
    `registered_at` DATETIME NOT NULL,
    `updated_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    -- One face per staff member. register() relies on this to upsert.
    UNIQUE KEY `unique_staff_face` (`staff_id`),
    KEY `idx_staff_id` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bring an older tblface_templates up to date.
ALTER TABLE `tblface_templates`
    ADD COLUMN IF NOT EXISTS `photo_path` VARCHAR(500) DEFAULT NULL
        COMMENT 'Reference photo, relative to FCPATH' AFTER `embedding`;

ALTER TABLE `tblface_templates`
    ADD COLUMN IF NOT EXISTS `registered_by` INT(11) DEFAULT NULL
        COMMENT 'Staff id of the admin who registered it, NULL when self-registered' AFTER `device_id`;

ALTER TABLE `tblface_templates`
    ADD COLUMN IF NOT EXISTS `source` ENUM('mobile', 'web') DEFAULT 'mobile'
        COMMENT 'Where the registration came from' AFTER `registered_by`;

-- ------------------------------------------------------------
-- Attendance Log
-- Every punch in and punch out, with GPS and the face similarity it was accepted on.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tblattendance_log` (
    `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `project_id` INT(11) DEFAULT NULL COMMENT 'Project being worked on, NULL when not supplied',
    `type` ENUM('in', 'out') NOT NULL COMMENT 'Punch type',
    `punch_time` DATETIME NOT NULL,
    `punch_date` DATE NOT NULL COMMENT 'For easy date-based queries',
    `latitude` DECIMAL(10, 8) DEFAULT NULL,
    `longitude` DECIMAL(11, 8) DEFAULT NULL,
    `device_id` VARCHAR(255) DEFAULT '' COMMENT 'Mobile device identifier',
    `ip_address` VARCHAR(45) DEFAULT '',
    `face_similarity` DECIMAL(5, 4) DEFAULT NULL COMMENT 'Cosine similarity score 0.0-1.0',
    `note` VARCHAR(500) DEFAULT '',
    `created_at` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_staff_date` (`staff_id`, `punch_date`),
    KEY `idx_punch_date` (`punch_date`),
    KEY `idx_staff_time` (`staff_id`, `punch_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Bring an older tblattendance_log up to date.
ALTER TABLE `tblattendance_log`
    ADD COLUMN IF NOT EXISTS `project_id` INT(11) DEFAULT NULL
        COMMENT 'Project being worked on, NULL when not supplied' AFTER `staff_id`;
