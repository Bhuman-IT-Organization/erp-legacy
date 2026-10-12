-- ============================================================
-- Attendance Phase 3 - MASTER DATA for the policy-driven rules.
--
-- These tables hold rules as DATA so they can be configured and changed without
-- code: overtime rates, compensatory off, and leave entitlement / opening
-- balances. Nothing here invents a policy - the tables ship empty (or with a
-- clearly-marked inactive default) and are filled in from the admin screens.
--
-- Safe to run more than once.
-- Apply with:
--   php application/sql/apply_sql.php application/sql/attendance_phase3_master_data.sql
-- ============================================================


-- ============================================================
-- 1. OVERTIME RULES
--
-- Created as tbltimesheets_overtime_setting - the name the timesheets module
-- already expects. That table was never created, yet
-- Timesheets_model::get_overtime_setting() / add / update / delete all reference
-- it (Timesheets_model.php around 3928-3970), and two tables already carry FK
-- columns to it: tbltimesheets_timesheet.overtime_setting and
-- tbltimesheets_additional_timesheet.overtime_setting. Any call to that model
-- method is a fatal error today. Creating it with this name fixes that instead of
-- adding a second, competing overtime table.
--
-- The module's own CRUD is generic (insert($data) / update($data, $id)), so the
-- extra columns below do not break it. Its settings branch exists
-- (Timesheets.php: group == 'overtime_setting') but has no view file and is not
-- in the tab list, so the feature was abandoned half-built - the admin screen
-- added alongside this file is what makes it usable.
--
-- HOW A RULE IS CHOSEN: the narrowest match wins, ordered by priority. day_type
-- and the time window decide applicability; coefficient is the multiplier applied
-- to the hourly rate.
-- ============================================================
CREATE TABLE IF NOT EXISTS `tbltimesheets_overtime_setting` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(191) NOT NULL COMMENT 'e.g. Weekday overtime, Rest day, Public holiday, Night premium',

    -- The multiplier. 1.25 = time and a quarter, 2 = double time. Named
    -- "coefficient" to match the module's existing language string.
    `coefficient` DECIMAL(6, 3) NOT NULL DEFAULT 1.000,

    -- Which kind of day the rule covers. 'weekly_off' resolves against the weekly
    -- off days configured on the overtime screen; 'public_holiday' resolves
    -- against tblday_off, which already holds the holiday calendar.
    `day_type` ENUM('any', 'working_day', 'weekly_off', 'public_holiday') NOT NULL DEFAULT 'any',

    -- Optional time-of-day window, for night or unsocial-hours premiums. Both
    -- NULL means the rule applies at any time. from_time > to_time is a window
    -- that crosses midnight (e.g. 22:00 to 06:00).
    `from_time` TIME DEFAULT NULL,
    `to_time` TIME DEFAULT NULL,

    -- Overtime below this is ignored, so a few minutes' overrun is not paid as OT.
    `min_minutes` INT(11) NOT NULL DEFAULT 0,
    -- 0 = uncapped.
    `max_hours_per_day` DECIMAL(6, 2) NOT NULL DEFAULT 0,

    -- Scope. Empty means every employee. Comma separated ids, matching the
    -- convention the timesheets module already uses for departments/job_positions.
    `departments` TEXT DEFAULT NULL,
    `job_positions` TEXT DEFAULT NULL,

    -- Lower number is evaluated first, so a specific rule can beat a general one.
    `priority` INT(11) NOT NULL DEFAULT 10,

    -- Dated, so changing a rate does not silently restate history.
    `effective_from` DATE DEFAULT NULL,
    `effective_to` DATE DEFAULT NULL,

    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `note` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_lookup` (`active`, `day_type`, `priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Deliberately NO seeded rates. A wrong multiplier silently mispays people, so
-- overtime stays switched off until someone enters the company's actual rules.


-- ============================================================
-- 2. COMPENSATORY OFF (comp-off)
--
-- Comp-off is paid time off granted INSTEAD OF overtime pay, for hours worked
-- beyond normal hours or on a rest day / holiday.
--
-- Single configuration row, because these are company-wide policy choices. The
-- row shipped below is INACTIVE with neutral values, so nothing takes effect
-- until it is reviewed and enabled.
-- ============================================================
CREATE TABLE IF NOT EXISTS `tblattendance_comp_off_setting` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,

    `active` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Off until the policy is confirmed',

    -- Hours of time off granted per hour of extra work. 1.000 = hour for hour;
    -- 1.500 = an hour and a half off per hour worked.
    `accrual_ratio` DECIMAL(6, 3) NOT NULL DEFAULT 1.000,

    -- How long earned comp-off survives. 0 = never expires.
    `expiry_days` INT(11) NOT NULL DEFAULT 0,

    -- Who decides: the employee requests it, or the employer grants it.
    `election_mode` ENUM('employee', 'employer') NOT NULL DEFAULT 'employee',

    -- The safeguard that stops the same hours being paid twice: when set, hours
    -- taken as comp-off are excluded from the overtime calculation.
    `exclude_from_overtime` TINYINT(1) NOT NULL DEFAULT 1,

    -- Extra work below this does not earn comp-off.
    `min_minutes_to_accrue` INT(11) NOT NULL DEFAULT 60,

    -- Which days earn comp-off at all.
    `earn_on_working_day` TINYINT(1) NOT NULL DEFAULT 0,
    `earn_on_weekly_off` TINYINT(1) NOT NULL DEFAULT 1,
    `earn_on_public_holiday` TINYINT(1) NOT NULL DEFAULT 1,

    `note` TEXT DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One inactive row so the settings screen always has something to edit.
INSERT IGNORE INTO `tblattendance_comp_off_setting` (`id`, `active`, `note`) VALUES
    (1, 0, 'Inactive until the accrual ratio, expiry and election mode are confirmed.');


-- ------------------------------------------------------------
-- Comp-off ledger.
--
-- A movement ledger, not a stored balance: the balance is SUM(hours), so it can
-- never drift out of step with the entries that justify it, and every change is
-- attributable. 'earned' and 'adjustment' are positive; 'taken' and 'expired'
-- are negative.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tblattendance_comp_off_ledger` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `entry_date` DATE NOT NULL COMMENT 'The day the hours were earned or taken',
    `entry_type` ENUM('earned', 'taken', 'expired', 'adjustment') NOT NULL,

    -- Signed: positive adds to the balance, negative reduces it.
    `hours` DECIMAL(8, 2) NOT NULL,

    -- When earned hours lapse. NULL = no expiry.
    `expires_on` DATE DEFAULT NULL,

    -- What caused this entry, so it can be traced back.
    `ref_type` VARCHAR(40) DEFAULT NULL COMMENT 'e.g. check_in_out, leave_request, manual',
    `ref_id` INT(11) DEFAULT NULL,

    `note` VARCHAR(500) DEFAULT NULL,
    `approved_by` INT(11) DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_staff_date` (`staff_id`, `entry_date`),
    KEY `idx_expiry` (`expires_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- 3. LEAVE ENTITLEMENT AND OPENING BALANCES
--
-- This is the "leave balances + effective / cut-off date for opening balances"
-- requirement. The existing module has three competing balance tables
-- (tbltimesheets_day_off, tblmanage_leave, tblleave_of_the_year), all empty, none
-- with a per-employee effective date and none with a ledger. These two replace
-- that with one entitlement record plus an auditable ledger.
-- ============================================================
CREATE TABLE IF NOT EXISTS `tblattendance_leave_entitlement` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,

    -- FK to tbltimesheets_type_of_leave. Kept nullable so a general annual
    -- entitlement can be recorded before leave types are set up.
    `leave_type_id` INT(11) DEFAULT NULL,

    -- The leave year this record covers, e.g. 2026.
    `period_year` SMALLINT(6) NOT NULL,

    -- THE CUT-OFF DATE. Balances brought in from the old system are true as at
    -- this date; nothing before it is recalculated. This is what makes going live
    -- mid-year safe.
    `effective_from` DATE NOT NULL,

    -- Days carried in from whatever was used before.
    `opening_balance` DECIMAL(8, 2) NOT NULL DEFAULT 0,
    -- Days granted for the period.
    `entitlement_days` DECIMAL(8, 2) NOT NULL DEFAULT 0,
    -- Days carried forward from the previous period.
    `carried_forward` DECIMAL(8, 2) NOT NULL DEFAULT 0,
    -- When carried-forward days lapse. NULL = they do not.
    `carry_forward_expires_on` DATE DEFAULT NULL,

    `note` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    -- One entitlement per employee, per leave type, per year.
    UNIQUE KEY `uk_staff_type_year` (`staff_id`, `leave_type_id`, `period_year`),
    KEY `idx_staff` (`staff_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ------------------------------------------------------------
-- Leave ledger. Same reasoning as the comp-off ledger: balance is derived by
-- SUM(days), so it cannot disagree with its own history.
--
-- 'opening', 'accrual', 'carry_forward' and positive 'adjustment' add; 'taken',
-- 'encashed', 'expired' and negative 'adjustment' subtract.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tblattendance_leave_ledger` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `leave_type_id` INT(11) DEFAULT NULL,
    `entry_date` DATE NOT NULL,
    `entry_type` ENUM('opening', 'accrual', 'carry_forward', 'taken', 'encashed', 'expired', 'adjustment') NOT NULL,

    -- Signed, in days. Half days are supported by the two decimal places.
    `days` DECIMAL(8, 2) NOT NULL,

    `ref_type` VARCHAR(40) DEFAULT NULL COMMENT 'e.g. requisition_leave, entitlement, manual',
    `ref_id` INT(11) DEFAULT NULL,

    `note` VARCHAR(500) DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_staff_type` (`staff_id`, `leave_type_id`),
    KEY `idx_entry_date` (`entry_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
