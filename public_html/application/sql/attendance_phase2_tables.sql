-- ============================================================
-- Attendance Phase 2 - forgot-to-check-out alert tracking.
--
-- Safe to run more than once.
-- Apply with: php application/sql/apply_sql.php application/sql/attendance_phase2_tables.sql
-- ============================================================

-- ------------------------------------------------------------
-- Which unclosed punches have already been alerted on.
--
-- The cron runs every few minutes. Without a record of what has already been
-- sent, every run would re-notify the same person about the same forgotten
-- check-out until they closed it, which trains people to ignore the alerts.
--
-- Keyed on the punch, so one reminder per forgotten check-in. Deleting a row
-- makes the alert eligible to send again, which is the manual way to re-notify.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tblattendance_alert_log` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `punch_id` INT(11) NOT NULL COMMENT 'tblcheck_in_out.id of the unclosed check-in',
    `staff_id` INT(11) NOT NULL,
    `alert_type` VARCHAR(40) NOT NULL DEFAULT 'unclosed_punch',
    `punch_date` DATE DEFAULT NULL,
    `hours_open` DECIMAL(6, 2) DEFAULT NULL COMMENT 'How long it had been open when the alert went out',
    `alerted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    -- One alert of a given type per punch.
    UNIQUE KEY `uk_punch_alert` (`punch_id`, `alert_type`),
    KEY `idx_staff_date` (`staff_id`, `punch_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
