-- ---------------------------------------------------------------------------
-- Petty Cash Advance Request
--
-- Same shape as the Bid Waiver form (sql/2026-08-29/pur_bid_waiver.sql):
--   tblpur_petty_cash_advances            the form itself (sections 1 and 2)
--   tblpur_petty_cash_advance_items       section 3, one row per expense line
--   tblpur_petty_cash_advance_approvals   section 4, one row per approval role
--
-- Attachments live in the core tblfiles table with rel_type 'pur_petty_cash', so
-- the existing preview, download and delete plumbing applies unchanged.
--
-- Section 4 has FOUR roles, not five: Initiator, Line Manager, Finance Manager, GM.
-- There is no CEO box and no amount threshold on this form.
--
-- Re-runnable: everything is IF NOT EXISTS or guarded by a NOT EXISTS select.
-- Plain DDL rather than information_schema lookups, because the live database user
-- cannot read information_schema.
--
--   mysql -u root nextgen_live -e "source sql/2026-09-01/pur_petty_cash_advance.sql"
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tblpur_petty_cash_advances` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,

  -- Auto generated reference, e.g. PCA00000001. `number` is the raw counter kept
  -- alongside it so the next value needs no string parsing.
  `request_no` VARCHAR(45) NOT NULL,
  `number` INT(11) NULL,

  -- Document control strip. All system maintained, same rules as the Bid Waiver:
  -- Ver steps 1.0 -> 2.0 -> 3.0 on each edit, Effective is fixed at v1.0, Revise
  -- holds the latest edit date, Approved By is stamped once every role approves.
  `doc_version` VARCHAR(20) NULL DEFAULT '1.0',
  `doc_effective_date` DATE NULL,
  `doc_revise` DATE NULL,
  `doc_number` VARCHAR(100) NULL,
  `doc_approved_by` VARCHAR(191) NULL,

  -- 1. Requestor Details
  `employee_id` VARCHAR(100) NULL,
  -- tblstaff.staffid. Stored as an id rather than a name so the form can be scoped
  -- to "my requests" for users without global view rights.
  `employee` INT(11) NULL,
  `department` INT(11) NULL,
  `required_date` DATE NULL,

  -- 2. Advance Request Details
  `purpose_of_advance` TEXT NULL,
  -- tblexpenses_categories.id
  `expense_category` INT(11) NULL,
  `amount_requested` DECIMAL(15,2) NULL DEFAULT 0.00,
  `currency` INT(11) NULL,
  -- The printed form carries a Required Date in both section 1 and section 2. Both
  -- are kept so the layout matches; this is the section 2 one.
  `advance_required_date` DATE NULL,
  -- normal | urgent
  `payment_priority` VARCHAR(20) NULL DEFAULT 'normal',

  -- 1 pending, 2 approved, 3 rejected
  `approve_status` INT(1) NOT NULL DEFAULT 1,

  `created_at` DATETIME NULL,
  `created_by` INT(11) NULL,
  `updated_at` DATETIME NULL,
  `updated_by` INT(11) NULL,

  PRIMARY KEY (`id`),
  KEY `idx_pca_request_no` (`request_no`),
  KEY `idx_pca_employee` (`employee`),
  KEY `idx_pca_department` (`department`),
  KEY `idx_pca_status` (`approve_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Expense / Items Details ─────────────────────────────────────────────
-- "One line will be printed if one, if more line items as per the request", so this
-- is a plain repeater with no fixed row count.
CREATE TABLE IF NOT EXISTS `tblpur_petty_cash_advance_items` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `advance_id` INT(11) NOT NULL,
  `description` TEXT NULL,
  `amount` DECIMAL(15,2) NULL DEFAULT 0.00,
  `vendor_id` INT(11) NULL,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_pcai_advance` (`advance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 4. Approvals ───────────────────────────────────────────────────────────
-- initiator, line_manager, finance_manager, gm. The unique key means a role can
-- only ever hold one decision per form, so a double submit cannot create two
-- conflicting rows.
CREATE TABLE IF NOT EXISTS `tblpur_petty_cash_advance_approvals` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `advance_id` INT(11) NOT NULL,
  `role` VARCHAR(45) NOT NULL,
  `staffid` INT(11) NULL,
  -- 1 pending, 2 approved, 3 rejected
  `status` INT(1) NOT NULL DEFAULT 1,
  `note` TEXT NULL,
  `action_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_pcaa_advance_role` (`advance_id`, `role`),
  KEY `idx_pcaa_advance` (`advance_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Numbering and document code ────────────────────────────────────────────
INSERT INTO `tblpurchase_option` (`option_name`, `option_val`, `auto`)
SELECT 'pur_pca_prefix', 'PCA', 1
WHERE NOT EXISTS (SELECT 1 FROM `tblpurchase_option` WHERE `option_name` = 'pur_pca_prefix');

INSERT INTO `tblpurchase_option` (`option_name`, `option_val`, `auto`)
SELECT 'next_pca_number', '1', 1
WHERE NOT EXISTS (SELECT 1 FROM `tblpurchase_option` WHERE `option_name` = 'next_pca_number');

-- Controlled document code for the form template, printed in the header.
INSERT INTO `tblpurchase_option` (`option_name`, `option_val`, `auto`)
SELECT 'pur_pca_doc_no', 'NGPP/FIN/PCA/01', 1
WHERE NOT EXISTS (SELECT 1 FROM `tblpurchase_option` WHERE `option_name` = 'pur_pca_doc_no');

-- ── Verification ───────────────────────────────────────────────────────────
SELECT 'tblpur_petty_cash_advances' AS table_name, COUNT(*) AS rows_present FROM `tblpur_petty_cash_advances`
UNION ALL SELECT 'tblpur_petty_cash_advance_items', COUNT(*) FROM `tblpur_petty_cash_advance_items`
UNION ALL SELECT 'tblpur_petty_cash_advance_approvals', COUNT(*) FROM `tblpur_petty_cash_advance_approvals`;

SELECT `option_name`, `option_val` FROM `tblpurchase_option`
WHERE `option_name` IN ('pur_pca_prefix', 'next_pca_number', 'pur_pca_doc_no');
