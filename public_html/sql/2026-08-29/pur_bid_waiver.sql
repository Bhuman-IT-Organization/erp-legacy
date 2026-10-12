-- ---------------------------------------------------------------------------
-- Procurement Justification & Bid Waiver Form
--
-- Three tables:
--   tblpur_bid_waivers            the form itself (sections 1, 2, 4 and 5)
--   tblpur_bid_waiver_quotations  section 3, one row per quotation obtained
--   tblpur_bid_waiver_approvals   section 6, one row per approval role
--
-- PDF and other attachments are NOT stored here. They go in the core tblfiles
-- table with rel_type = 'pur_bid_waiver', which is how every other attachment
-- in the purchase module already works, so the existing preview, download and
-- delete plumbing applies unchanged.
--
-- Re-runnable. Everything is IF NOT EXISTS or guarded by a NOT EXISTS select,
-- so applying this twice is a no-op. Written with plain DDL rather than
-- information_schema lookups because the live database user cannot read
-- information_schema.
--
-- Apply with:
--   mysql -u root nextgen_live -e "source sql/2026-08-29/pur_bid_waiver.sql"
-- ---------------------------------------------------------------------------

-- ── The form ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tblpur_bid_waivers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,

  -- Auto generated reference, e.g. BW00000001. `number` is the raw counter kept
  -- alongside it so the next value can be worked out without parsing the string.
  `request_no` VARCHAR(45) NOT NULL,
  `number` INT(11) NULL,

  -- Document control strip across the top of the printed form
  `doc_version` VARCHAR(20) NULL DEFAULT '1.0',
  `doc_effective_date` DATE NULL,
  `doc_revise` VARCHAR(45) NULL,
  `doc_number` VARCHAR(100) NULL,
  `doc_approved_by` VARCHAR(191) NULL,

  -- 1. Purchase Requirement Details
  `department` INT(11) NULL,
  `requestor` INT(11) NULL,
  `budget_required_date` DATE NULL,

  -- 2. Business Need & Background
  `purpose_of_purchase` TEXT NULL,
  `technical_specification` TEXT NULL,
  `quantity_unit` VARCHAR(191) NULL,
  -- asset | cons | rm | sv
  `purchase_type` VARCHAR(20) NULL,

  -- 4. Bid Waiver Assessment
  -- NULL = not answered yet, 1 = Yes, 0 = No
  `two_or_more_quotations` TINYINT(1) NULL,
  `wr_single_source` TINYINT(1) NOT NULL DEFAULT 0,
  `wr_existing_supplier` TINYINT(1) NOT NULL DEFAULT 0,
  `wr_urgent_requirement` TINYINT(1) NOT NULL DEFAULT 0,
  `wr_continuation_contract` TINYINT(1) NOT NULL DEFAULT 0,
  `wr_quality_limitation` TINYINT(1) NOT NULL DEFAULT 0,
  `wr_no_other_supplier` TINYINT(1) NOT NULL DEFAULT 0,
  `wr_other` TINYINT(1) NOT NULL DEFAULT 0,
  `wr_other_text` VARCHAR(191) NULL,

  -- 5. Vendor Selection & Justification
  `recommended_vendor` INT(11) NULL,
  `sr_lowest_price` TINYINT(1) NOT NULL DEFAULT 0,
  `sr_better_commercial_terms` TINYINT(1) NOT NULL DEFAULT 0,
  `sr_technically_compliant` TINYINT(1) NOT NULL DEFAULT 0,
  `sr_previous_performance` TINYINT(1) NOT NULL DEFAULT 0,
  `sr_best_delivery` TINYINT(1) NOT NULL DEFAULT 0,
  `sr_quality_service` TINYINT(1) NOT NULL DEFAULT 0,
  `sr_other` TINYINT(1) NOT NULL DEFAULT 0,
  `sr_other_text` VARCHAR(191) NULL,

  -- Recommended vendor's quoted amount, copied down from the chosen quotation
  -- line. Denormalised on purpose: it decides whether CEO approval is required,
  -- and that must not silently change if a quotation line is edited afterwards.
  `total_amount` DECIMAL(15,2) NULL DEFAULT 0.00,
  `currency` INT(11) NULL,

  -- 1 pending, 2 approved, 3 rejected - same convention as pur_faf_requests
  `approve_status` INT(1) NOT NULL DEFAULT 1,

  `created_at` DATETIME NULL,
  `created_by` INT(11) NULL,
  `updated_at` DATETIME NULL,
  `updated_by` INT(11) NULL,

  PRIMARY KEY (`id`),
  KEY `idx_bw_request_no` (`request_no`),
  KEY `idx_bw_department` (`department`),
  KEY `idx_bw_requestor` (`requestor`),
  KEY `idx_bw_status` (`approve_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 3. Quotation & Commercial Evaluation ───────────────────────────────────
-- One row per quotation. The form prints one line for a single source and three
-- when three quotations were obtained, so this is a plain repeater with no fixed
-- row count.
CREATE TABLE IF NOT EXISTS `tblpur_bid_waiver_quotations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bid_waiver_id` INT(11) NOT NULL,
  `vendor_id` INT(11) NULL,
  `amount` DECIMAL(15,2) NULL DEFAULT 0.00,
  `currency` INT(11) NULL,
  `lead_time` VARCHAR(191) NULL,
  `payment_terms` VARCHAR(191) NULL,

  -- Filename only, relative to modules/purchase/uploads/bid_waivers/<id>/quotations/.
  -- Deliberately not a tblfiles row: quotation lines are replaced wholesale on
  -- save, which would leave orphaned file records behind.
  `attachment` VARCHAR(255) NULL,

  `sort_order` INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_bwq_waiver` (`bid_waiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 6. Approvals ───────────────────────────────────────────────────────────
-- One row per role: initiator, line_manager, finance_manager, gm, ceo.
-- The unique key on (bid_waiver_id, role) means a role can only ever hold one
-- decision per form, so a double submit cannot create two conflicting rows.
CREATE TABLE IF NOT EXISTS `tblpur_bid_waiver_approvals` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `bid_waiver_id` INT(11) NOT NULL,
  `role` VARCHAR(45) NOT NULL,
  `staffid` INT(11) NULL,
  -- 1 pending, 2 approved, 3 rejected
  `status` INT(1) NOT NULL DEFAULT 1,
  `note` TEXT NULL,
  `action_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_bwa_waiver_role` (`bid_waiver_id`, `role`),
  KEY `idx_bwa_waiver` (`bid_waiver_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── Numbering and threshold options ────────────────────────────────────────
-- Stored in tblpurchase_option like every other purchase document counter.
INSERT INTO `tblpurchase_option` (`option_name`, `option_val`, `auto`)
SELECT 'pur_bid_waiver_prefix', 'BW', 1
WHERE NOT EXISTS (SELECT 1 FROM `tblpurchase_option` WHERE `option_name` = 'pur_bid_waiver_prefix');

INSERT INTO `tblpurchase_option` (`option_name`, `option_val`, `auto`)
SELECT 'next_bid_waiver_number', '1', 1
WHERE NOT EXISTS (SELECT 1 FROM `tblpurchase_option` WHERE `option_name` = 'next_bid_waiver_number');

-- Above this recommended amount the CEO approval box is required. The printed
-- form says "CEO - If more than 50K".
INSERT INTO `tblpurchase_option` (`option_name`, `option_val`, `auto`)
SELECT 'pur_bid_waiver_ceo_threshold', '50000', 1
WHERE NOT EXISTS (SELECT 1 FROM `tblpurchase_option` WHERE `option_name` = 'pur_bid_waiver_ceo_threshold');

-- Controlled document code shown in the form header. Identifies the form template,
-- not the individual record - the per record identifier is request_no.
INSERT INTO `tblpurchase_option` (`option_name`, `option_val`, `auto`)
SELECT 'pur_bid_waiver_doc_no', 'NGPP/PUR/BWF/01', 1
WHERE NOT EXISTS (SELECT 1 FROM `tblpurchase_option` WHERE `option_name` = 'pur_bid_waiver_doc_no');

-- ── Verification ───────────────────────────────────────────────────────────
SELECT 'tblpur_bid_waivers' AS table_name, COUNT(*) AS rows_present FROM `tblpur_bid_waivers`
UNION ALL SELECT 'tblpur_bid_waiver_quotations', COUNT(*) FROM `tblpur_bid_waiver_quotations`
UNION ALL SELECT 'tblpur_bid_waiver_approvals', COUNT(*) FROM `tblpur_bid_waiver_approvals`;

SELECT `option_name`, `option_val` FROM `tblpurchase_option`
WHERE `option_name` IN ('pur_bid_waiver_prefix', 'next_bid_waiver_number',
                        'pur_bid_waiver_ceo_threshold', 'pur_bid_waiver_doc_no');
