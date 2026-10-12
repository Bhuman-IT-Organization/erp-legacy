-- ============================================================
-- Attendance Phase 1 - optional project geolocation, out-of-geofence
-- reasons, and punch context on the EXISTING attendance tables.
--
-- Safe to run more than once. CREATE ... IF NOT EXISTS, ADD COLUMN IF NOT
-- EXISTS and INSERT IGNORE throughout, so re-running changes nothing and does
-- not overwrite anything an administrator has edited.
--
-- Run against the database named in application/config/app-config.php.
--
-- WHY THIS LIVES HERE AND NOT IN modules/timesheets/migrations/
-- The timesheets module is third-party (GreenTech, v1.2.3) and its migrations
-- run to 123. A migration numbered 124 here would collide with the vendor's own
-- next release and be overwritten. Keeping the schema in application/sql leaves
-- the licensed module untouched and upgradeable.
--
-- "ADD COLUMN IF NOT EXISTS" needs MariaDB 10.0+ (this project runs 10.4 locally
-- and MariaDB on cPanel). On Oracle MySQL, drop the IF NOT EXISTS and run only
-- the columns you are missing.
-- ============================================================

-- ------------------------------------------------------------
-- 1. Project / site geolocation - ENTIRELY OPTIONAL
--
-- A project with no row here has no geofence, and attendance against it is
-- never location-restricted. That is what makes the feature opt-in per project:
-- optionality is expressed by the absence of a row rather than by three
-- nullable columns that all have to be read and interpreted.
--
-- Deliberately a side table rather than columns on tblprojects: tblprojects is
-- a core Perfex table, and adding columns to it risks conflicts when the CRM
-- itself is upgraded.
--
-- radius_metres is metres to match Timesheets_model::compute_distance(), which
-- returns metres, and tbltimesheets_workplace.distance, which is already metres.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tblattendance_project_location` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_id` INT(11) NOT NULL,
    `latitude` DECIMAL(10, 8) DEFAULT NULL,
    `longitude` DECIMAL(11, 8) DEFAULT NULL,
    `radius_metres` INT(11) NOT NULL DEFAULT 200 COMMENT 'Accepted distance from the point',
    `address` VARCHAR(400) DEFAULT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Unticked suspends the geofence without losing the coordinates',
    `created_at` DATETIME DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    -- One location per project, so the lookup is unambiguous.
    UNIQUE KEY `uk_project` (`project_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- 2. Reasons accepted when an employee punches outside a project geofence.
--
-- A master table rather than a hardcoded list so HR can add or retire reasons
-- without a code change. requires_description drives the mandatory free-text
-- field; all four seeded reasons require one, as specified.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tblattendance_reason` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL,
    `requires_description` TINYINT(1) NOT NULL DEFAULT 1,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- INSERT IGNORE, so re-running never overwrites a renamed or deactivated reason.
INSERT IGNORE INTO `tblattendance_reason` (`name`, `slug`, `requires_description`, `active`, `sort_order`) VALUES
    ('Client Meeting',                 'client_meeting',      1, 1, 1),
    ('Material Collection',            'material_collection', 1, 1, 2),
    ('Inspection',                     'inspection',          1, 1, 3),
    ('Other Project-Related Activity', 'other_project_work',  1, 1, 4);

-- ------------------------------------------------------------
-- 3. Punch context on the EXISTING punch table.
--
-- tblcheck_in_out is the store the timekeeping screens, timesheet generation
-- and the payroll bridge already read, so the mobile app writes here rather
-- than to a parallel table. Every column below is nullable or defaulted, so the
-- existing Hikvision sync - which inserts only staff_id, date, type_check and
-- type - keeps working untouched.
-- ------------------------------------------------------------
ALTER TABLE `tblcheck_in_out`
    ADD COLUMN IF NOT EXISTS `project_id` INT(11) DEFAULT NULL
        COMMENT 'Project/site punched against, NULL when not supplied' AFTER `workplace_id`;

ALTER TABLE `tblcheck_in_out`
    ADD COLUMN IF NOT EXISTS `latitude` DECIMAL(10, 8) DEFAULT NULL AFTER `project_id`;

ALTER TABLE `tblcheck_in_out`
    ADD COLUMN IF NOT EXISTS `longitude` DECIMAL(11, 8) DEFAULT NULL AFTER `latitude`;

ALTER TABLE `tblcheck_in_out`
    ADD COLUMN IF NOT EXISTS `face_similarity` DECIMAL(5, 4) DEFAULT NULL
        COMMENT 'Cosine similarity the punch was accepted on' AFTER `longitude`;

ALTER TABLE `tblcheck_in_out`
    ADD COLUMN IF NOT EXISTS `device_id` VARCHAR(255) DEFAULT NULL AFTER `face_similarity`;

ALTER TABLE `tblcheck_in_out`
    ADD COLUMN IF NOT EXISTS `out_of_geofence` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT '1 = punched outside the project geofence, reason_id explains why' AFTER `device_id`;

ALTER TABLE `tblcheck_in_out`
    ADD COLUMN IF NOT EXISTS `reason_id` INT(11) DEFAULT NULL
        COMMENT 'FK tblattendance_reason, required when out_of_geofence = 1' AFTER `out_of_geofence`;

ALTER TABLE `tblcheck_in_out`
    ADD COLUMN IF NOT EXISTS `reason_description` VARCHAR(500) DEFAULT NULL AFTER `reason_id`;

ALTER TABLE `tblcheck_in_out`
    ADD COLUMN IF NOT EXISTS `source` VARCHAR(20) DEFAULT NULL
        COMMENT 'mobile | web | biometric | supervisor - where the punch came from' AFTER `reason_description`;

-- Who recorded it, when a supervisor / foreman / driver punches on behalf of a
-- worker who does not use the app. NULL means the employee punched themselves.
ALTER TABLE `tblcheck_in_out`
    ADD COLUMN IF NOT EXISTS `recorded_by` INT(11) DEFAULT NULL AFTER `source`;

-- Free-text note. The mobile punch API has always accepted a `note`, and without
-- somewhere to put it the value would be accepted and silently discarded.
ALTER TABLE `tblcheck_in_out`
    ADD COLUMN IF NOT EXISTS `note` VARCHAR(500) DEFAULT NULL AFTER `recorded_by`;

-- Per-project reporting reads this table by project and date.
ALTER TABLE `tblcheck_in_out`
    ADD INDEX IF NOT EXISTS `idx_project_date` (`project_id`, `date`);

-- ------------------------------------------------------------
-- 4. Project dimension on the day-level timesheet.
--
-- Nullable and additive. The existing generator keeps writing one aggregate row
-- per staff/day/type, so nothing about the timekeeping grid or the payroll
-- bridge changes. Per-project man-hours are derived from the punch pairs in
-- tblcheck_in_out instead of being duplicated here - see
-- Timesheets_model::get_project_hours_by_date().
-- ------------------------------------------------------------
ALTER TABLE `tbltimesheets_timesheet`
    ADD COLUMN IF NOT EXISTS `project_id` INT(11) DEFAULT NULL AFTER `staff_id`;
