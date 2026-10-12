-- ============================================================================
-- HR AND PROJECT EXTENSIONS - MASTER DATA AND FEATURE TABLES
--
-- Covers requirements 10 to 13: project scope/activity management, loan
-- applications, salary increment requests, vacation requests and the employee
-- personal-documents folder.
--
-- WHY THIS FILE EXISTS AS IT IS
--
-- 1. Everything policy-shaped is a table, not a constant. Approval level counts,
--    the 50/30/20 salary split, loan ceilings, document reminder lead times and
--    activity statuses are all rows an administrator edits. Where a figure was
--    specified (2 approval levels for staff, 3 for labour, 50/30/20) it is
--    seeded. Where it was not (loan ceilings, which allowance types receive the
--    30 and the 20) the row ships INACTIVE so an unconfigured system cannot
--    quietly apply a guessed rule to somebody's pay.
--
-- 2. New tables, never new columns on core Perfex tables. tblstaff, tblprojects
--    and tbltasks are upgraded by the CRM itself; tblhr_* and tblhrp_* belong to
--    the licensed hr_profile and hr_payroll modules. Extending any of them means
--    losing the change on their next release. tblemp_profile is therefore a 1:1
--    side table rather than columns on tblstaff.
--
-- 3. Balances are derived, never stored. Outstanding loan balance is a SUM over
--    the instalment schedule, exactly as leave balance is a SUM over
--    tblattendance_leave_ledger. A stored balance drifts from its own history.
--
-- 4. Prefixes avoid every vendor namespace already in use (tblhr_ = hr_profile,
--    tblhrp_ = hr_payroll, tbltimesheets_, tblwh_, tblmrp_, tblpur_, tblfe_).
--
-- Idempotent: CREATE TABLE IF NOT EXISTS / INSERT IGNORE throughout, so it is
-- safe to re-run. Apply with:
--   php application/sql/apply_sql.php application/sql/hr_extensions_master_data.sql
-- ============================================================================


-- ============================================================
-- SECTION 1 - EMPLOYEE CLASSIFICATION
--
-- Nothing in this codebase distinguishes Staff from Labour today. tblstaff has
-- role (a permission role), job_position (an hr_profile position) and
-- status_work, none of which carry that meaning, and no Labour or Foreman rows
-- are seeded anywhere. Two requirements depend on the distinction:
--   - loans need 3 approval levels for labour and 2 for staff
--   - increments split 50/30/20 for staff grades but go entirely to Other
--     Allowances for labour
-- So the category is modelled explicitly, and both rules read from it.
-- ============================================================
CREATE TABLE IF NOT EXISTS `tblemp_category` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,

    -- How many approval levels a request from this category passes through.
    -- Used as the default when an approval flow does not define its own levels.
    `approval_levels` TINYINT(2) NOT NULL DEFAULT 2,

    -- Drives the increment split and the vacation-encashment notice. Kept as a
    -- flag rather than inferred from the name so renaming a category is safe.
    `is_labour` TINYINT(1) NOT NULL DEFAULT 0,

    -- Requirement 13: staff whose vacation balance exceeds this are notified,
    -- because encashment is not available. Configurable rather than a literal 30.
    `vacation_balance_alert_days` DECIMAL(6, 2) DEFAULT NULL,

    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Level counts and the labour flag come straight from the requirement. The
-- 30-day vacation alert is specified for staff only, hence NULL for labour.
INSERT IGNORE INTO `tblemp_category`
    (`name`, `slug`, `approval_levels`, `is_labour`, `vacation_balance_alert_days`, `active`, `sort_order`) VALUES
    ('Staff',            'staff',            2, 0, 30, 1, 1),
    ('Site Supervisor',  'site_supervisor',  2, 0, 30, 1, 2),
    ('Foreman',          'foreman',          2, 0, 30, 1, 3),
    ('Labour',           'labour',           3, 1, NULL, 1, 4);


-- ------------------------------------------------------------
-- Visa type master. tblstaff.visa_validity holds an expiry but there is no visa
-- TYPE field anywhere, and the increment request has to display one.
-- These are editable labels, not policy - rename or deactivate freely.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tblemp_visa_type` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tblemp_visa_type` (`name`, `slug`, `active`, `sort_order`) VALUES
    ('Employment Visa', 'employment', 1, 1),
    ('Mission Visa',    'mission',    1, 2),
    ('Visit Visa',      'visit',      1, 3),
    ('Family Visa',     'family',     1, 4),
    ('Golden Visa',     'golden',     1, 5);


-- ------------------------------------------------------------
-- Employee HR profile - a 1:1 side table on tblstaff.
--
-- joining_date and salary_on_joining are here because neither exists today:
-- hr_payroll labels tblstaff.datecreated as "Date of Joining", but that is the
-- record-creation timestamp, which is wrong for anyone migrated in. When
-- joining_date is set it wins; when NULL the code falls back to datecreated, so
-- this is additive and nothing breaks before it is populated.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tblemp_profile` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `category_id` INT(11) UNSIGNED DEFAULT NULL,
    `visa_type_id` INT(11) UNSIGNED DEFAULT NULL,

    -- Overrides tblstaff.datecreated for seniority. NULL = use datecreated.
    `joining_date` DATE DEFAULT NULL,
    `probation_end_date` DATE DEFAULT NULL,

    -- Requirement 12 asks for "Salary upon joining" and it is stored nowhere.
    `salary_on_joining` DECIMAL(15, 2) DEFAULT NULL,

    `note` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_staff` (`staff_id`),
    KEY `idx_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SECTION 2 - GENERIC MULTI-LEVEL APPROVAL ENGINE
--
-- Five features in these requirements need approvals: loans (2 or 3 levels by
-- category), increments (finance then managing director), vacations (supervisor
-- then HR), employee document changes (HR), and sign-off of completed project
-- activities. Every module in this codebase has copy-pasted its own
-- *_approval_setting / *_approval_details pair (timesheets, warehouse,
-- manufacturing, purchase, fixed_equipment) with the chain serialised into a
-- JSON blob. Rather than add a sixth copy, this is one engine with the chain as
-- rows, so a level can be read, indexed and reported on.
--
-- Deliberate difference from the module pattern: when no flow is configured the
-- request stays pending. The timesheets version auto-approves in that case,
-- which is how an unconfigured system silently pays out.
-- ============================================================
CREATE TABLE IF NOT EXISTS `tblapproval_flow` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,

    -- loan_application | increment_request | vacation_request
    -- | document_change | activity_completion
    `request_type` VARCHAR(50) NOT NULL,

    -- NULL = applies to every employee category. A category-specific flow wins
    -- over the catch-all, which is what gives labour 3 levels and staff 2.
    `employee_category_id` INT(11) UNSIGNED DEFAULT NULL,

    `name` VARCHAR(150) NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `note` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_type_category` (`request_type`, `employee_category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `tblapproval_flow_level` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `flow_id` INT(11) UNSIGNED NOT NULL,
    `level_no` TINYINT(2) NOT NULL,
    `name` VARCHAR(150) DEFAULT NULL,

    -- How the approver is found when a request is raised. direct_manager reads
    -- tblstaff.team_manage and head_of_department reads tbldepartments.manager_id,
    -- matching how the timesheets module already resolves approvers.
    `approver_type` ENUM('direct_manager', 'head_of_department', 'specific_staff', 'role') NOT NULL DEFAULT 'direct_manager',
    `approver_staff_id` INT(11) DEFAULT NULL,
    `approver_role_id` INT(11) DEFAULT NULL,

    -- Requirement 11 puts the approved amount and approved instalment at a
    -- specific level (L2 for staff, L3 for labour) rather than at submission.
    `can_edit_amounts` TINYINT(1) NOT NULL DEFAULT 0,

    -- Days allowed before the step is flagged as overdue. NULL = no deadline.
    `sla_days` INT(11) DEFAULT NULL,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_flow_level` (`flow_id`, `level_no`),
    KEY `idx_flow` (`flow_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- One row per submitted document, whatever its type. rel_id points at the loan,
-- increment, leave, document or activity row.
CREATE TABLE IF NOT EXISTS `tblapproval_request` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_type` VARCHAR(50) NOT NULL,
    `rel_id` INT(11) NOT NULL,

    -- Subject of the request, who it is about.
    `staff_id` INT(11) NOT NULL,
    -- Who pressed submit. Differs from staff_id when a manager or supervisor
    -- raises it on behalf of someone who does not use the application.
    `submitted_by` INT(11) DEFAULT NULL,

    `flow_id` INT(11) UNSIGNED DEFAULT NULL,
    `total_levels` TINYINT(2) NOT NULL DEFAULT 0,
    `current_level` TINYINT(2) NOT NULL DEFAULT 1,
    `status` ENUM('pending', 'approved', 'rejected', 'cancelled') NOT NULL DEFAULT 'pending',
    `submitted_at` DATETIME DEFAULT NULL,
    `completed_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    -- One live approval chain per document.
    UNIQUE KEY `uk_type_rel` (`request_type`, `rel_id`),
    KEY `idx_staff` (`staff_id`),
    KEY `idx_status` (`status`, `current_level`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- The audit trail: one row per level, created pending when the chain is
-- materialised and stamped when somebody acts. Kept separate from the request so
-- history survives and "who approved what, when" is answerable.
CREATE TABLE IF NOT EXISTS `tblapproval_action` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `approval_request_id` INT(11) UNSIGNED NOT NULL,
    `level_no` TINYINT(2) NOT NULL,
    `level_name` VARCHAR(150) DEFAULT NULL,

    -- Resolved when the chain is built, so a later change to the reporting line
    -- does not silently move a pending approval to somebody else.
    `approver_staff_id` INT(11) DEFAULT NULL,
    `approver_type` VARCHAR(30) DEFAULT NULL,
    `can_edit_amounts` TINYINT(1) NOT NULL DEFAULT 0,
    `due_date` DATE DEFAULT NULL,

    `action` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `note` VARCHAR(1000) DEFAULT NULL,
    `acted_by` INT(11) DEFAULT NULL,
    `acted_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_request_level` (`approval_request_id`, `level_no`),
    KEY `idx_approver` (`approver_staff_id`, `action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SECTION 3 - EMPLOYEE DOCUMENTS (requirement 13)
--
-- Today passport, Emirates ID, labour card and visa numbers/expiries are TEXT
-- columns on tblstaff, and employee attachments are untyped rows in tblfiles
-- with rel_type 'hr_staff_file' - no type, no number, no expiry, no approval
-- state. So nothing can drive an expiry reminder or an HR review queue.
--
-- These two tables add typed documents with expiries while leaving the tblstaff
-- columns as the "master list" the requirement refers to: on approval, the
-- mapped columns are written back, which is what makes the master list update
-- automatically.
-- ============================================================
CREATE TABLE IF NOT EXISTS `tblemp_document_type` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(150) NOT NULL,
    `slug` VARCHAR(150) NOT NULL,

    `has_number` TINYINT(1) NOT NULL DEFAULT 1,
    `has_expiry` TINYINT(1) NOT NULL DEFAULT 1,

    -- Days before expiry that the reminder is sent. Per type, because a passport
    -- needs far more warning than an insurance card.
    `reminder_days` INT(11) NOT NULL DEFAULT 30,

    -- May the employee upload or replace this themselves?
    `employee_can_upload` TINYINT(1) NOT NULL DEFAULT 1,
    -- Must HR confirm before it becomes the current record?
    `requires_hr_approval` TINYINT(1) NOT NULL DEFAULT 1,

    -- THE BRIDGE TO THE EXISTING MASTER LIST. When an update is approved these
    -- tblstaff columns are overwritten from the document. NULL for types that
    -- have no column, such as an insurance card.
    `staff_number_field` VARCHAR(60) DEFAULT NULL,
    `staff_expiry_field` VARCHAR(60) DEFAULT NULL,

    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The column names below are the real ones added to tblstaff by hr_profile
-- migration 110. reminder_days are starting points an administrator can change.
INSERT IGNORE INTO `tblemp_document_type`
    (`name`, `slug`, `has_number`, `has_expiry`, `reminder_days`, `employee_can_upload`, `requires_hr_approval`, `staff_number_field`, `staff_expiry_field`, `active`, `sort_order`) VALUES
    ('Passport',       'passport',       1, 1, 90, 1, 1, 'passport_no',     'passport_expiry', 1, 1),
    ('Emirates ID',    'emirates_id',    1, 1, 60, 1, 1, 'emirates_ID_No',  'EID_expiry',      1, 2),
    ('Residence Visa', 'residence_visa', 1, 1, 60, 1, 1, NULL,              'visa_validity',   1, 3),
    ('Labour Card',    'labour_card',    1, 1, 60, 1, 1, 'labour_card_No',  NULL,              1, 4),
    ('Insurance Card', 'insurance_card', 1, 1, 30, 0, 0, NULL,              NULL,              1, 5),
    ('Insurance List', 'insurance_list', 0, 0, 30, 0, 0, NULL,              NULL,              1, 6),
    ('Certificate',    'certificate',    0, 0, 30, 1, 1, NULL,              NULL,              1, 7);


CREATE TABLE IF NOT EXISTS `tblemp_document` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `document_type_id` INT(11) UNSIGNED NOT NULL,

    `document_number` VARCHAR(100) DEFAULT NULL,
    `issue_date` DATE DEFAULT NULL,
    `expiry_date` DATE DEFAULT NULL,

    -- Stored under uploads/emp_documents/{staff_id}/. original_name is kept so a
    -- sanitised filename on disk still shows the employee what they uploaded.
    `file_name` VARCHAR(255) DEFAULT NULL,
    `original_name` VARCHAR(255) DEFAULT NULL,
    `file_type` VARCHAR(100) DEFAULT NULL,

    -- pending_approval - waiting for HR
    -- current          - the live record for this staff member and type
    -- superseded       - replaced by a newer approved record, kept for history
    -- rejected         - HR declined the update
    `status` ENUM('pending_approval', 'current', 'superseded', 'rejected') NOT NULL DEFAULT 'pending_approval',

    -- Set when this row is the one whose values were written to tblstaff.
    `applied_to_master` TINYINT(1) NOT NULL DEFAULT 0,

    `note` VARCHAR(500) DEFAULT NULL,
    `uploaded_by` INT(11) DEFAULT NULL,
    `uploaded_at` DATETIME DEFAULT NULL,
    `reviewed_by` INT(11) DEFAULT NULL,
    `reviewed_at` DATETIME DEFAULT NULL,
    `reject_reason` VARCHAR(500) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_staff_type` (`staff_id`, `document_type_id`, `status`),
    -- The HR "expiring soon" list and the cron both read by expiry.
    KEY `idx_expiry` (`expiry_date`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Dedupe log for the expiry cron, so a reminder is sent once per stage rather
-- than every time the cron runs. Keyed on the expiry date as well as the type so
-- renewing a document re-arms the reminder.
CREATE TABLE IF NOT EXISTS `tblemp_document_alert` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `document_type_id` INT(11) UNSIGNED DEFAULT NULL,
    `document_id` INT(11) UNSIGNED DEFAULT NULL,
    `expiry_date` DATE NOT NULL,
    -- upcoming = inside the reminder window, expired = already past
    `alert_stage` VARCHAR(20) NOT NULL DEFAULT 'upcoming',
    `sent_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_alert` (`staff_id`, `document_type_id`, `expiry_date`, `alert_stage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SECTION 4 - LOAN APPLICATIONS (requirement 11)
--
-- Nothing loan-shaped exists. tbltimesheets_go_bussiness_advance_payment is a
-- business-trip per-diem advance with no principal, schedule or balance, and
-- tblpur_petty_cash_advances is a purchasing document.
--
-- Repayment recovers through the existing payroll deduction path: hr_payroll
-- reads tblhrp_salary_deductions.deduction_list as JSON keyed by deduction id,
-- so a due instalment can be injected there without touching payslip code.
-- ============================================================

-- Lending rules. Ships INACTIVE with every limit NULL: a maximum amount or
-- instalment count invented here would either block legitimate requests or wave
-- through ones that should have been refused.
CREATE TABLE IF NOT EXISTS `tblloan_policy` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,

    -- NULL = the rule for any category with no specific row.
    `category_id` INT(11) UNSIGNED DEFAULT NULL,

    `max_amount` DECIMAL(15, 2) DEFAULT NULL,
    `max_instalments` INT(11) DEFAULT NULL,
    -- Minimum completed service before an employee may borrow.
    `min_service_months` INT(11) NOT NULL DEFAULT 0,
    `max_concurrent_loans` INT(11) NOT NULL DEFAULT 1,
    -- Ceiling on the monthly instalment as a percentage of current salary.
    `max_instalment_percent_of_salary` DECIMAL(5, 2) DEFAULT NULL,

    `active` TINYINT(1) NOT NULL DEFAULT 0,
    `note` VARCHAR(500) DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `tblloan_application` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` VARCHAR(40) DEFAULT NULL,
    `staff_id` INT(11) NOT NULL,
    `submitted_by` INT(11) DEFAULT NULL,

    -- Entered by the employee.
    `reason` TEXT DEFAULT NULL,
    `requested_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0,
    `proposed_instalment` DECIMAL(15, 2) NOT NULL DEFAULT 0,
    `first_instalment_date` DATE DEFAULT NULL,

    -- Filled in by the system and SNAPSHOT at submission, so the record still
    -- explains itself years later when service and balances have moved on.
    `service_years` DECIMAL(6, 2) DEFAULT NULL,
    `outstanding_at_request` DECIMAL(15, 2) DEFAULT NULL,
    `current_salary` DECIMAL(15, 2) DEFAULT NULL,
    `category_id` INT(11) UNSIGNED DEFAULT NULL,

    -- Set by the approver whose level has can_edit_amounts.
    `approved_amount` DECIMAL(15, 2) DEFAULT NULL,
    `approved_instalment` DECIMAL(15, 2) DEFAULT NULL,
    `approved_first_instalment_date` DATE DEFAULT NULL,

    `status` ENUM('draft', 'pending', 'approved', 'rejected', 'cancelled', 'disbursed', 'closed') NOT NULL DEFAULT 'draft',
    `disbursed_on` DATE DEFAULT NULL,
    `closed_on` DATE DEFAULT NULL,
    `note` VARCHAR(1000) DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_staff_status` (`staff_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- The schedule. Generated from the approved amount and instalment, which is what
-- lets the form show the whole plan including the last instalment date.
-- Outstanding balance = SUM(amount) of rows still 'scheduled'.
CREATE TABLE IF NOT EXISTS `tblloan_instalment` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `loan_id` INT(11) UNSIGNED NOT NULL,
    `instalment_no` INT(11) NOT NULL,
    `due_date` DATE NOT NULL,
    `amount` DECIMAL(15, 2) NOT NULL DEFAULT 0,

    `status` ENUM('scheduled', 'deducted', 'waived', 'skipped') NOT NULL DEFAULT 'scheduled',
    -- Which payroll month actually recovered it.
    `payroll_month` DATE DEFAULT NULL,
    `deducted_amount` DECIMAL(15, 2) DEFAULT NULL,
    `deducted_on` DATE DEFAULT NULL,
    `note` VARCHAR(500) DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_loan_instalment` (`loan_id`, `instalment_no`),
    KEY `idx_due` (`due_date`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SECTION 5 - SALARY INCREMENTS AND SALARY HISTORY (requirement 12)
--
-- Salary lives in tblhr_staff_contract_detail as rows: rel_type 'salary' or
-- 'allowance', rel_value the amount. Editing a value overwrites it, so past
-- salaries are lost - which is why "last increment date/amount" and "full
-- increment history" cannot be answered today. tblsalary_history fixes that by
-- recording every change alongside the contract row it touched.
-- ============================================================

-- The 50/30/20 rule, as data. Percentages come from the requirement. The
-- allowance types they land on cannot be guessed - they are rows in the
-- hr_profile master tblhr_allowance_type, which differs per install - so the
-- rows ship INACTIVE with allowance_type_id NULL. Pick the types, then activate.
CREATE TABLE IF NOT EXISTS `tblincrement_split_rule` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT(11) UNSIGNED DEFAULT NULL,

    -- basic    - adds to the contract detail row with rel_type 'salary'
    -- allowance - adds to an allowance row, identified by allowance_type_id
    `component_type` ENUM('basic', 'allowance') NOT NULL DEFAULT 'basic',
    `allowance_type_id` INT(11) DEFAULT NULL,

    `percent` DECIMAL(6, 2) NOT NULL DEFAULT 0,
    `label` VARCHAR(150) DEFAULT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 0,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    -- One rule per category per slot, so re-running the seed below cannot
    -- duplicate the split and quietly turn 50/30/20 into 100/60/40.
    UNIQUE KEY `uk_category_slot` (`category_id`, `sort_order`),
    KEY `idx_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- For installs where the table was created before the unique key existed.
ALTER TABLE `tblincrement_split_rule`
    ADD UNIQUE KEY IF NOT EXISTS `uk_category_slot` (`category_id`, `sort_order`);

-- The 50/30/20 split, seeded INACTIVE.
--
-- The percentages are from the requirement, so they are recorded. What cannot be
-- guessed is which allowance rows the 30 and the 20 belong to: those are rows in
-- the hr_profile master tblhr_allowance_type and differ per install. So
-- allowance_type_id is NULL and active is 0 - an administrator picks the two
-- allowance types and activates. Until then no increment is split automatically,
-- which is the safe failure: a wrong split silently mispays every future payslip.
INSERT IGNORE INTO `tblincrement_split_rule`
    (`category_id`, `component_type`, `allowance_type_id`, `percent`, `label`, `active`, `sort_order`)
SELECT `id`, 'basic', NULL, 50.00, 'Basic salary', 0, 1
FROM `tblemp_category` WHERE `is_labour` = 0;

INSERT IGNORE INTO `tblincrement_split_rule`
    (`category_id`, `component_type`, `allowance_type_id`, `percent`, `label`, `active`, `sort_order`)
SELECT `id`, 'allowance', NULL, 30.00, 'Allowance - select the type', 0, 2
FROM `tblemp_category` WHERE `is_labour` = 0;

INSERT IGNORE INTO `tblincrement_split_rule`
    (`category_id`, `component_type`, `allowance_type_id`, `percent`, `label`, `active`, `sort_order`)
SELECT `id`, 'allowance', NULL, 20.00, 'Other allowances - select the type', 0, 3
FROM `tblemp_category` WHERE `is_labour` = 0;

-- Labour: basic stays unchanged and the whole increment goes to Other Allowances.
INSERT IGNORE INTO `tblincrement_split_rule`
    (`category_id`, `component_type`, `allowance_type_id`, `percent`, `label`, `active`, `sort_order`)
SELECT `id`, 'allowance', NULL, 100.00, 'Other allowances - select the type', 0, 1
FROM `tblemp_category` WHERE `is_labour` = 1;


CREATE TABLE IF NOT EXISTS `tblincrement_request` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `reference` VARCHAR(40) DEFAULT NULL,
    `staff_id` INT(11) NOT NULL,
    `submitted_by` INT(11) DEFAULT NULL,

    `reason` TEXT DEFAULT NULL,
    `increment_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0,
    `effective_from` DATE DEFAULT NULL,

    -- Everything below is read automatically and snapshot, because the whole
    -- point of the form is that the approver sees the context without asking.
    `designation` VARCHAR(200) DEFAULT NULL,
    `joining_date` DATE DEFAULT NULL,
    `service_years` DECIMAL(6, 2) DEFAULT NULL,
    `visa_type` VARCHAR(100) DEFAULT NULL,
    `visa_expiry` DATE DEFAULT NULL,
    `salary_on_joining` DECIMAL(15, 2) DEFAULT NULL,
    `current_salary` DECIMAL(15, 2) DEFAULT NULL,
    `last_increment_date` DATE DEFAULT NULL,
    `last_increment_amount` DECIMAL(15, 2) DEFAULT NULL,
    `evaluation_result` VARCHAR(200) DEFAULT NULL,
    `evaluation_year` SMALLINT(6) DEFAULT NULL,
    `category_id` INT(11) UNSIGNED DEFAULT NULL,

    `approved_amount` DECIMAL(15, 2) DEFAULT NULL,

    `status` ENUM('draft', 'pending', 'approved', 'rejected', 'cancelled', 'applied') NOT NULL DEFAULT 'draft',
    -- Stamped when the split was actually written onto the contract.
    `applied_at` DATETIME DEFAULT NULL,
    `note` VARCHAR(1000) DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_staff_status` (`staff_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `tblsalary_history` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id` INT(11) NOT NULL,
    `effective_from` DATE NOT NULL,
    `change_type` ENUM('joining', 'increment', 'revision', 'promotion', 'correction') NOT NULL DEFAULT 'increment',

    `component_type` ENUM('basic', 'allowance', 'total') NOT NULL DEFAULT 'total',
    `allowance_type_id` INT(11) DEFAULT NULL,
    `component_label` VARCHAR(150) DEFAULT NULL,

    `old_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0,
    `new_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0,
    `change_amount` DECIMAL(15, 2) NOT NULL DEFAULT 0,

    -- Traceability back to the request, and to the contract row that was edited.
    `ref_type` VARCHAR(40) DEFAULT NULL,
    `ref_id` INT(11) DEFAULT NULL,
    `contract_id` INT(11) DEFAULT NULL,
    `contract_detail_id` INT(11) DEFAULT NULL,

    `note` VARCHAR(500) DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `created_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_staff_date` (`staff_id`, `effective_from`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- SECTION 6 - PROJECT SCOPE / ACTIVITY MANAGEMENT (requirement 10)
--
-- Perfex offers projects, milestones and tasks. Milestones carry no progress,
-- budget, status, owner or department, tasks carry no budget or department, and
-- project progress is an unweighted count of tasks at status 5 - which also
-- returns 100% for a project with no tasks at all.
--
-- Activities are therefore a separate, item-wise scope list with a weight, so
-- overall completion is a weighted roll-up rather than a task headcount, and so
-- a completed item can require sign-off before it counts.
--
-- NAMING: these are tblproject_scope_* rather than tblproject_activity_*. Perfex
-- already ships a core table called tblproject_activity - it is the project audit
-- feed ("X added a team member") - so that name is taken. The scope/activity feature
-- here is a different thing and must not collide with it.
-- ============================================================
CREATE TABLE IF NOT EXISTS `tblproject_scope_status` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `slug` VARCHAR(100) NOT NULL,
    -- Marks the statuses that mean "finished", used by the roll-up.
    `is_complete` TINYINT(1) NOT NULL DEFAULT 0,
    -- Excluded from the completion roll-up entirely, e.g. Cancelled.
    `is_excluded` TINYINT(1) NOT NULL DEFAULT 0,
    `colour` VARCHAR(10) DEFAULT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT(11) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tblproject_scope_status`
    (`name`, `slug`, `is_complete`, `is_excluded`, `colour`, `active`, `sort_order`) VALUES
    ('Not Started', 'not_started', 0, 0, '#64748b', 1, 1),
    ('In Progress', 'in_progress', 0, 0, '#3b82f6', 1, 2),
    ('On Hold',     'on_hold',     0, 0, '#f59e0b', 1, 3),
    ('Completed',   'completed',   1, 0, '#22c55e', 1, 4),
    ('Cancelled',   'cancelled',   0, 1, '#ef4444', 1, 5);


CREATE TABLE IF NOT EXISTS `tblproject_scope_activity` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `project_id` INT(11) NOT NULL,
    -- Scope hierarchy, so an activity can sit under a parent scope item.
    `parent_id` INT(11) UNSIGNED DEFAULT NULL,
    -- Optional link to the existing milestone grouping.
    `milestone_id` INT(11) DEFAULT NULL,

    `code` VARCHAR(50) DEFAULT NULL,
    `name` VARCHAR(255) NOT NULL,
    `description` TEXT DEFAULT NULL,

    -- Item-wise scope: quantity and unit make "8 of 20 units installed" possible.
    `quantity` DECIMAL(15, 3) DEFAULT NULL,
    `unit` VARCHAR(30) DEFAULT NULL,

    `responsible_staff_id` INT(11) DEFAULT NULL,
    -- Stored explicitly, not derived from the responsible person: staff belong to
    -- several departments in this install, so deriving it would be ambiguous.
    `department_id` INT(11) DEFAULT NULL,

    `status_id` INT(11) UNSIGNED DEFAULT NULL,

    `budget_amount` DECIMAL(15, 2) DEFAULT NULL,
    `material_budget` DECIMAL(15, 2) DEFAULT NULL,

    -- Relative size, so overall completion is weighted rather than a plain
    -- average. 1 for everything gives the simple average.
    `weight` DECIMAL(8, 2) NOT NULL DEFAULT 1,

    `planned_start` DATE DEFAULT NULL,
    `planned_end` DATE DEFAULT NULL,
    `actual_start` DATE DEFAULT NULL,
    `actual_end` DATE DEFAULT NULL,

    -- Last APPROVED progress. Unapproved updates live in the progress table until
    -- signed off, so an unreviewed claim cannot move the project percentage.
    `progress_percent` DECIMAL(5, 2) NOT NULL DEFAULT 0,

    -- Requirement: approval of completed activities.
    `requires_approval` TINYINT(1) NOT NULL DEFAULT 1,

    `sort_order` INT(11) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT NULL,
    `created_by` INT(11) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_project` (`project_id`, `sort_order`),
    KEY `idx_responsible` (`responsible_staff_id`),
    KEY `idx_department` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- Every progress update, not just the latest. This is the item-by-item update
-- trail the project managers produce, and the queue HR/PM approve from.
CREATE TABLE IF NOT EXISTS `tblproject_scope_progress` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `activity_id` INT(11) UNSIGNED NOT NULL,

    `previous_percent` DECIMAL(5, 2) NOT NULL DEFAULT 0,
    `progress_percent` DECIMAL(5, 2) NOT NULL DEFAULT 0,
    `quantity_done` DECIMAL(15, 3) DEFAULT NULL,

    `note` VARCHAR(1000) DEFAULT NULL,
    `updated_by` INT(11) DEFAULT NULL,
    `updated_at` DATETIME DEFAULT NULL,

    -- approved immediately when the activity does not require sign-off.
    `approval_status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    `approved_by` INT(11) DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `reject_reason` VARCHAR(500) DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_activity` (`activity_id`, `approval_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
