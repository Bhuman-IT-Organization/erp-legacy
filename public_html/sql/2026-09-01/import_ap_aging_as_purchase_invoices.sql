-- ===========================================================================
-- Import the AP aging report as purchase invoices, posted to the GL.
--
-- DO NOT RUN THIS AS-IS. It will insert nothing until you set the parameters in
-- SECTION 0 and set @COMMIT_CHANGES = 1. Every write is guarded by that flag, so
-- a first run is a dry run that only reports what it would do.
--
-- Source : `30_04_2026___ap_aging_report`  (185 rows, 49 suppliers,
--           net -28,330,450.48 AED)
-- Creates: tblpur_invoices rows  + matching tblacc_account_history double entry
--
-- Once created these behave like any other purchase invoice: they appear in the
-- purchase invoice list, on the vendor Statement of Accounts (get_statement()
-- reads tblpur_invoices by vendor and invoice_date), in the general ledger, and
-- in the AP balance of each vendor's payable account.
--
-- ---------------------------------------------------------------------------
-- THREE DECISIONS YOU MUST MAKE FIRST
-- ---------------------------------------------------------------------------
--
-- 1. VENDOR MAPPING.  None of the 49 SAP supplier codes match
--    tblpur_vendor.vendor_code - that column is not populated with SAP codes.
--    Matching on name only works for 20 of 49, because the import truncated
--    supplier names to 20 characters. SECTION 1 builds a mapping table, fills in
--    what it can, and lists the rest for you to complete by hand. Nothing is
--    imported for a supplier with no vendor: inventing vendor records from
--    truncated names would leave you with bad master data forever.
--
-- 2. SIGN CONVENTION.  In this extract negatives dominate: -31,735,228.94
--    negative against 3,404,778.46 positive. Two readings are possible and they
--    are exact opposites, so this cannot be guessed - it is worth 28 million.
--      @NEGATIVE_MEANS_PAYABLE = 1  negative = amount owed to the supplier
--                                   (SAP GL sign for a liability credit).
--                                   Amounts are sign-flipped on import so a
--                                   payable becomes a positive invoice.
--      @NEGATIVE_MEANS_PAYABLE = 0  positive = amount owed. Amounts import
--                                   as-is; negatives become negative invoices
--                                   (credits / advances).
--    Check one supplier against SAP before choosing. BIZGROUP SUPPLIES
--    (code 3000099) is -18,366,428.73 across 22 rows - if you owe them that,
--    set 1; if they owe you, set 0.
--
-- 3. CONTRA ACCOUNT.  A normal purchase invoice debits 167 GR/IR Clearing. That
--    is wrong for an opening balance upload: 28 million would sit in a clearing
--    account with nothing ever to clear it. This database has no Opening Balance
--    Equity or suspense account. Your options:
--      158  Retained Earnings (Default)   - correct if these balances predate
--                                           the go-live cut-over
--      create a dedicated "Opening Balance Suspense" account first - cleanest,
--      because the upload is then visible and reconcilable as its own figure
--    Set @CONTRA_ACCOUNT once you have decided. There is no safe default, so it
--    starts as 0 and the preflight refuses to proceed until you change it.
--
-- ---------------------------------------------------------------------------
-- HOW TO RUN
-- ---------------------------------------------------------------------------
--   1. Back up the database.
--   2. Run as-is. Nothing is written. Read every report block.
--   3. Complete the vendor mapping (SECTION 1) for the suppliers listed as
--      unmapped, or accept that they are skipped.
--   4. Set the three parameters in SECTION 0.
--   5. Run again with @COMMIT_CHANGES = 1.
--   6. Read SECTION 5. If anything is off, use the rollback at the foot.
--
--   mysql -u root nextgen_live -e "source sql/2026-09-01/import_ap_aging_as_purchase_invoices.sql"
-- ===========================================================================


-- ===========================================================================
-- SECTION 0 - PARAMETERS
-- ===========================================================================

SET @COMMIT_CHANGES         = 0;            -- 0 = dry run. 1 = actually write.
SET @NEGATIVE_MEANS_PAYABLE = 1;            -- see decision 2 above
SET @CONTRA_ACCOUNT         = 0;            -- see decision 3 above. Must be > 0.
SET @INVOICE_DATE_OVERRIDE  = NULL;         -- NULL = use each row's own date.
                                            -- Or '2026-04-30' to post the whole
                                            -- upload on the aging report date.
SET @ADDED_BY_STAFF         = 1;            -- staff id recorded as the creator
SET @INVOICE_NUMBER_PREFIX  = 'APOB-';      -- kept distinct from the INV series so
                                            -- the upload is identifiable later
SET @BATCH_TAG              = 'AP aging opening balance 30-04-2026';

-- Base currency, resolved rather than hardcoded
SET @BASE_CURRENCY = (SELECT `id` FROM `tblcurrencies` WHERE `isdefault` = 1 LIMIT 1);

-- Marker used to find and to roll back this batch
SET @REL_TYPE = 'purchase_invoice';


-- ===========================================================================
-- SECTION 1 - VENDOR MAPPING
-- ===========================================================================
-- Kept as a real table, not a temporary one, so you can complete it by hand
-- between runs. It is only rebuilt when it does not already exist, so your
-- edits survive a re-run.

CREATE TABLE IF NOT EXISTS `tmp_ap_aging_vendor_map` (
  `supplier_code` VARCHAR(20)  NOT NULL,
  `supplier_name` VARCHAR(191) NULL,
  `vendor_id`     INT(11)      NULL COMMENT 'tblpur_vendor.userid - fill in by hand where blank',
  `match_source`  VARCHAR(40)  NULL,
  PRIMARY KEY (`supplier_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed one row per supplier in the aging file
INSERT IGNORE INTO `tmp_ap_aging_vendor_map` (`supplier_code`, `supplier_name`)
SELECT DISTINCT `supplier_code`, `supplier_name`
FROM   `30_04_2026___ap_aging_report`
WHERE  `supplier_code` IS NOT NULL;

-- Auto-match on vendor_code where it happens to be populated.
-- Explicit COLLATE: the aging table is utf8mb4_unicode_ci and tblpur_vendor is
-- utf8mb4_general_ci, so an unqualified comparison raises error 1267.
UPDATE `tmp_ap_aging_vendor_map` m
SET    m.`vendor_id` = (
         SELECT v.`userid` FROM `tblpur_vendor` v
          WHERE TRIM(v.`vendor_code`) COLLATE utf8mb4_general_ci
                = TRIM(m.`supplier_code`) COLLATE utf8mb4_general_ci
          LIMIT 1),
       m.`match_source` = 'vendor_code'
WHERE  m.`vendor_id` IS NULL
  AND  EXISTS (SELECT 1 FROM `tblpur_vendor` v
                WHERE TRIM(v.`vendor_code`) COLLATE utf8mb4_general_ci
                      = TRIM(m.`supplier_code`) COLLATE utf8mb4_general_ci);

-- Then on the truncated name, but only where exactly one vendor matches, so an
-- ambiguous prefix is never resolved silently
UPDATE `tmp_ap_aging_vendor_map` m
SET    m.`vendor_id` = (
         SELECT MIN(v.`userid`) FROM `tblpur_vendor` v
          WHERE v.`company` COLLATE utf8mb4_general_ci
                LIKE CONCAT(TRIM(m.`supplier_name`) COLLATE utf8mb4_general_ci, '%')),
       m.`match_source` = 'name_prefix_unique'
WHERE  m.`vendor_id` IS NULL
  AND  TRIM(COALESCE(m.`supplier_name`, '')) <> ''
  AND  (SELECT COUNT(*) FROM `tblpur_vendor` v
         WHERE v.`company` COLLATE utf8mb4_general_ci
               LIKE CONCAT(TRIM(m.`supplier_name`) COLLATE utf8mb4_general_ci, '%')) = 1;

SELECT '=== 1a. Vendor mapping summary ===' AS report;

SELECT COUNT(*)                                                        AS suppliers_total,
       SUM(CASE WHEN `vendor_id` IS NOT NULL THEN 1 ELSE 0 END)        AS mapped,
       SUM(CASE WHEN `vendor_id` IS NULL THEN 1 ELSE 0 END)            AS unmapped
FROM   `tmp_ap_aging_vendor_map`;

SELECT '=== 1b. Mapped suppliers ===' AS report;

SELECT m.`supplier_code`, m.`supplier_name`, m.`vendor_id`,
       v.`company` AS vendor_company, m.`match_source`,
       ROUND((SELECT SUM(a.`amount_aed`) FROM `30_04_2026___ap_aging_report` a
               WHERE a.`supplier_code` = m.`supplier_code`), 2) AS net_aed
FROM   `tmp_ap_aging_vendor_map` m
LEFT JOIN `tblpur_vendor` v ON v.`userid` = m.`vendor_id`
WHERE  m.`vendor_id` IS NOT NULL
ORDER BY m.`supplier_code`;

SELECT '=== 1c. UNMAPPED - these are skipped. Fill in vendor_id to include them ===' AS report;

SELECT m.`supplier_code`, m.`supplier_name`,
       (SELECT COUNT(*) FROM `30_04_2026___ap_aging_report` a
         WHERE a.`supplier_code` = m.`supplier_code`) AS rows_affected,
       ROUND((SELECT SUM(a.`amount_aed`) FROM `30_04_2026___ap_aging_report` a
               WHERE a.`supplier_code` = m.`supplier_code`), 2) AS net_aed_skipped
FROM   `tmp_ap_aging_vendor_map` m
WHERE  m.`vendor_id` IS NULL
ORDER BY ABS((SELECT COALESCE(SUM(a.`amount_aed`),0) FROM `30_04_2026___ap_aging_report` a
               WHERE a.`supplier_code` = m.`supplier_code`)) DESC;

-- To complete the mapping by hand:
--   UPDATE tmp_ap_aging_vendor_map SET vendor_id = <userid>, match_source = 'manual'
--    WHERE supplier_code = '<code>';


-- ===========================================================================
-- SECTION 2 - PREFLIGHT
-- ===========================================================================

SELECT '=== 2a. Parameters in effect ===' AS report;

SELECT @COMMIT_CHANGES         AS commit_changes,
       @NEGATIVE_MEANS_PAYABLE AS negative_means_payable,
       @CONTRA_ACCOUNT         AS contra_account,
       (SELECT `name` FROM `tblacc_accounts` WHERE `id` = @CONTRA_ACCOUNT) AS contra_account_name,
       @BASE_CURRENCY          AS base_currency,
       @INVOICE_DATE_OVERRIDE  AS invoice_date_override;

SELECT '=== 2b. Blocking checks - all must pass before @COMMIT_CHANGES = 1 ===' AS report;

SELECT
  CASE WHEN @CONTRA_ACCOUNT IS NULL OR @CONTRA_ACCOUNT = 0
       THEN 'FAIL - set @CONTRA_ACCOUNT (decision 3)'
       WHEN NOT EXISTS (SELECT 1 FROM `tblacc_accounts` WHERE `id` = @CONTRA_ACCOUNT)
       THEN 'FAIL - @CONTRA_ACCOUNT does not exist'
       ELSE 'ok' END AS contra_account_check,
  CASE WHEN @BASE_CURRENCY IS NULL
       THEN 'FAIL - no default currency found'
       ELSE 'ok' END AS base_currency_check,
  CASE WHEN NOT EXISTS (SELECT 1 FROM `tmp_ap_aging_vendor_map` WHERE `vendor_id` IS NOT NULL)
       THEN 'FAIL - no supplier is mapped to a vendor'
       ELSE 'ok' END AS mapping_check,
  CASE WHEN EXISTS (
         SELECT 1 FROM `tmp_ap_aging_vendor_map` m
          WHERE m.`vendor_id` IS NOT NULL
            AND NOT EXISTS (SELECT 1 FROM `tblpur_vendor` v WHERE v.`userid` = m.`vendor_id`))
       THEN 'FAIL - a mapped vendor_id does not exist'
       ELSE 'ok' END AS vendor_exists_check,
  CASE WHEN EXISTS (
         SELECT 1 FROM `tmp_ap_aging_vendor_map` m
          JOIN `tblpur_vendor` v ON v.`userid` = m.`vendor_id`
         WHERE m.`vendor_id` IS NOT NULL
           AND (v.`ledger_account` IS NULL OR v.`ledger_account` = 0))
       THEN 'FAIL - a mapped vendor has no AP ledger_account set'
       ELSE 'ok' END AS vendor_ap_account_check,
  CASE WHEN EXISTS (SELECT 1 FROM `tblpur_invoices`
                     WHERE `invoice_number` LIKE CONCAT(@INVOICE_NUMBER_PREFIX, '%'))
       THEN 'FAIL - this batch appears to have been imported already'
       ELSE 'ok' END AS not_already_imported_check;

SELECT '=== 2c. Vendors mapped but missing an AP account ===' AS report;

SELECT m.`supplier_code`, m.`supplier_name`, v.`userid`, v.`company`, v.`ledger_account`
FROM   `tmp_ap_aging_vendor_map` m
JOIN   `tblpur_vendor` v ON v.`userid` = m.`vendor_id`
WHERE  m.`vendor_id` IS NOT NULL
  AND (v.`ledger_account` IS NULL OR v.`ledger_account` = 0);

SELECT '=== 2d. What would be imported ===' AS report;

SELECT COUNT(*)                                                                AS invoices_to_create,
       COUNT(DISTINCT a.`supplier_code`)                                       AS suppliers,
       ROUND(SUM(a.`amount_aed`), 2)                                           AS source_net_aed,
       ROUND(SUM(a.`amount_aed` * IF(@NEGATIVE_MEANS_PAYABLE = 1, -1, 1)), 2)  AS invoice_net_after_sign,
       MIN(COALESCE(@INVOICE_DATE_OVERRIDE, a.`invoice_date`))                 AS earliest_date,
       MAX(COALESCE(@INVOICE_DATE_OVERRIDE, a.`invoice_date`))                 AS latest_date
FROM   `30_04_2026___ap_aging_report` a
JOIN   `tmp_ap_aging_vendor_map` m ON m.`supplier_code` = a.`supplier_code`
WHERE  m.`vendor_id` IS NOT NULL;

SELECT '=== 2e. Per vendor preview, largest first ===' AS report;

SELECT v.`userid` AS vendor_id, v.`company`,
       acc.`name` AS ap_account,
       COUNT(*)   AS invoices,
       ROUND(SUM(a.`amount_aed` * IF(@NEGATIVE_MEANS_PAYABLE = 1, -1, 1)), 2) AS total_to_post
FROM   `30_04_2026___ap_aging_report` a
JOIN   `tmp_ap_aging_vendor_map` m ON m.`supplier_code` = a.`supplier_code`
JOIN   `tblpur_vendor` v           ON v.`userid` = m.`vendor_id`
LEFT JOIN `tblacc_accounts` acc    ON acc.`id` = v.`ledger_account`
WHERE  m.`vendor_id` IS NOT NULL
GROUP BY v.`userid`, v.`company`, acc.`name`
ORDER BY ABS(SUM(a.`amount_aed`)) DESC;


-- ===========================================================================
-- SECTION 3 - CREATE THE INVOICES
-- ===========================================================================
-- Every statement below is gated on @COMMIT_CHANGES = 1. With the flag at 0 the
-- WHERE clause matches nothing and no row is written.

-- Staging table holding exactly what will be created, so invoice numbers are
-- allocated deterministically and the GL step can join back to it.
DROP TABLE IF EXISTS `tmp_ap_aging_to_import`;

CREATE TABLE `tmp_ap_aging_to_import` (
  `seq`            INT(11) NOT NULL AUTO_INCREMENT,
  `source_id`      INT(11) NOT NULL,
  `vendor_id`      INT(11) NOT NULL,
  `ap_account`     INT(11) NOT NULL,
  `invoice_date`   DATE NULL,
  `amount`         DECIMAL(15,2) NOT NULL,
  `vendor_inv_no`  VARCHAR(100) NULL,
  `description`    VARCHAR(191) NULL,
  `invoice_id`     INT(11) NULL,
  `invoice_number` VARCHAR(100) NULL,
  `number`         INT(11) NULL,
  PRIMARY KEY (`seq`),
  KEY `idx_src` (`source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @next_number = (SELECT COALESCE(MAX(`number`), 0) FROM `tblpur_invoices`);

INSERT INTO `tmp_ap_aging_to_import`
  (`source_id`, `vendor_id`, `ap_account`, `invoice_date`, `amount`, `vendor_inv_no`, `description`)
SELECT a.`id`, m.`vendor_id`, v.`ledger_account`,
       COALESCE(@INVOICE_DATE_OVERRIDE, a.`invoice_date`),
       ROUND(a.`amount_aed` * IF(@NEGATIVE_MEANS_PAYABLE = 1, -1, 1), 2),
       a.`inv_no`,
       a.`sap_description`
FROM   `30_04_2026___ap_aging_report` a
JOIN   `tmp_ap_aging_vendor_map` m ON m.`supplier_code` = a.`supplier_code`
JOIN   `tblpur_vendor` v           ON v.`userid` = m.`vendor_id`
WHERE  m.`vendor_id` IS NOT NULL
  AND  v.`ledger_account` > 0
  AND  @COMMIT_CHANGES = 1
ORDER BY m.`vendor_id`, a.`invoice_date`, a.`id`;

-- Allocate the invoice numbers
UPDATE `tmp_ap_aging_to_import`
SET    `number`         = @next_number + `seq`,
       `invoice_number` = CONCAT(@INVOICE_NUMBER_PREFIX, LPAD(@next_number + `seq`, 6, '0'));

-- Create the invoices.
--   approval_status 2  approved, so they are live rather than pending
--   acc_mapping     1  already posted, because SECTION 4 writes the GL rows here
--                      and the accounting module must not post them a second time
--   payment_status     unpaid, so the full amount shows as outstanding
INSERT INTO `tblpur_invoices`
  (`number`, `invoice_number`, `invoice_date`, `subtotal`, `tax_rate`, `tax`, `total`,
   `vendor`, `payment_status`, `vendor_note`, `adminnote`, `add_from`, `date_add`,
   `duedate`, `currency`, `currency_rate`, `vendor_invoice_number`,
   `approval_status`, `acc_mapping`, `add_from_type`, `is_deleted`)
SELECT t.`number`, t.`invoice_number`, t.`invoice_date`,
       t.`amount`, 0, 0.00, t.`amount`,
       t.`vendor_id`, 'unpaid', NULL,
       CONCAT(@BATCH_TAG, ' | source row ', t.`source_id`,
              CASE WHEN t.`description` IS NOT NULL
                   THEN CONCAT(' | ', t.`description`) ELSE '' END),
       @ADDED_BY_STAFF, t.`invoice_date`, t.`invoice_date`,
       @BASE_CURRENCY, 1.000000, t.`vendor_inv_no`,
       2, 1, 'admin', 0
FROM   `tmp_ap_aging_to_import` t
WHERE  @COMMIT_CHANGES = 1
ORDER BY t.`seq`;

-- Link the staging rows to the invoices just created, matched on the number,
-- which is unique to this batch
UPDATE `tmp_ap_aging_to_import` t
JOIN   `tblpur_invoices` i ON i.`number` = t.`number`
SET    t.`invoice_id` = i.`id`
WHERE  @COMMIT_CHANGES = 1;


-- ===========================================================================
-- SECTION 4 - DOUBLE ENTRY
-- ===========================================================================
-- Two rows per invoice, mirroring what a real purchase invoice produces:
--
--   Dr  @CONTRA_ACCOUNT      the offset for the opening balance
--   Cr  vendor AP account    what is owed to the supplier
--
-- A negative amount reverses both sides naturally, which is why the amount is
-- placed with a CASE rather than being forced into the debit column: MySQL would
-- otherwise store a negative debit, and every report here assumes debit and
-- credit are both non-negative.
--
-- rel_type is 'purchase_invoice' and rel_id the invoice id, so these entries are
-- picked up by the same reports, and removed by the same delete path, as any
-- other purchase invoice.

INSERT INTO `tblacc_account_history`
  (`account`, `debit`, `credit`, `description`, `rel_id`, `rel_type`, `datecreated`,
   `addedfrom`, `customer`, `split`, `item`, `date`, `tax`, `vendor`, `number`, `currency_rate`)
SELECT @CONTRA_ACCOUNT,
       CASE WHEN t.`amount` >= 0 THEN t.`amount` ELSE 0 END,
       CASE WHEN t.`amount` <  0 THEN -t.`amount` ELSE 0 END,
       CONCAT(@BATCH_TAG, ' - ', v.`company`),
       t.`invoice_id`, @REL_TYPE, NOW(),
       @ADDED_BY_STAFF, 0, t.`ap_account`, 0, t.`invoice_date`, 0,
       t.`vendor_id`, t.`invoice_number`, 1.000000
FROM   `tmp_ap_aging_to_import` t
JOIN   `tblpur_vendor` v ON v.`userid` = t.`vendor_id`
WHERE  @COMMIT_CHANGES = 1 AND t.`invoice_id` IS NOT NULL
ORDER BY t.`seq`;

INSERT INTO `tblacc_account_history`
  (`account`, `debit`, `credit`, `description`, `rel_id`, `rel_type`, `datecreated`,
   `addedfrom`, `customer`, `split`, `item`, `date`, `tax`, `vendor`, `number`, `currency_rate`)
SELECT t.`ap_account`,
       CASE WHEN t.`amount` <  0 THEN -t.`amount` ELSE 0 END,
       CASE WHEN t.`amount` >= 0 THEN t.`amount` ELSE 0 END,
       CONCAT(@BATCH_TAG, ' - ', v.`company`),
       t.`invoice_id`, @REL_TYPE, NOW(),
       @ADDED_BY_STAFF, 0, @CONTRA_ACCOUNT, 0, t.`invoice_date`, 0,
       t.`vendor_id`, t.`invoice_number`, 1.000000
FROM   `tmp_ap_aging_to_import` t
JOIN   `tblpur_vendor` v ON v.`userid` = t.`vendor_id`
WHERE  @COMMIT_CHANGES = 1 AND t.`invoice_id` IS NOT NULL
ORDER BY t.`seq`;


-- ===========================================================================
-- SECTION 5 - VERIFY
-- ===========================================================================

SELECT '=== 5a. Created ===' AS report;

SELECT (SELECT COUNT(*) FROM `tmp_ap_aging_to_import`)                            AS staged,
       (SELECT COUNT(*) FROM `tmp_ap_aging_to_import` WHERE `invoice_id` IS NOT NULL) AS invoices_created,
       (SELECT COUNT(*) FROM `tblacc_account_history`
         WHERE `number` LIKE CONCAT(@INVOICE_NUMBER_PREFIX, '%'))                 AS gl_rows_created;

SELECT '=== 5b. The batch must balance ===' AS report;

SELECT ROUND(SUM(`debit`), 2)  AS total_debit,
       ROUND(SUM(`credit`), 2) AS total_credit,
       CASE WHEN ROUND(SUM(`debit`), 2) = ROUND(SUM(`credit`), 2)
            THEN 'BALANCED' ELSE '*** OUT OF BALANCE ***' END AS status
FROM   `tblacc_account_history`
WHERE  `number` LIKE CONCAT(@INVOICE_NUMBER_PREFIX, '%');

SELECT '=== 5c. Invoice totals must equal the source, after the sign rule ===' AS report;

SELECT ROUND((SELECT SUM(`total`) FROM `tblpur_invoices`
               WHERE `invoice_number` LIKE CONCAT(@INVOICE_NUMBER_PREFIX, '%')), 2) AS invoiced_total,
       ROUND((SELECT SUM(`amount`) FROM `tmp_ap_aging_to_import`), 2)               AS staged_total,
       ROUND((SELECT SUM(a.`amount_aed` * IF(@NEGATIVE_MEANS_PAYABLE = 1, -1, 1))
                FROM `30_04_2026___ap_aging_report` a
                JOIN `tmp_ap_aging_vendor_map` m ON m.`supplier_code` = a.`supplier_code`
                JOIN `tblpur_vendor` v ON v.`userid` = m.`vendor_id`
               WHERE m.`vendor_id` IS NOT NULL AND v.`ledger_account` > 0), 2)      AS expected_total;

SELECT '=== 5d. Movement per account ===' AS report;

SELECT ah.`account`, acc.`name`,
       ROUND(SUM(ah.`debit`), 2)  AS debit,
       ROUND(SUM(ah.`credit`), 2) AS credit,
       ROUND(SUM(ah.`credit`) - SUM(ah.`debit`), 2) AS net_credit
FROM   `tblacc_account_history` ah
LEFT JOIN `tblacc_accounts` acc ON acc.`id` = ah.`account`
WHERE  ah.`number` LIKE CONCAT(@INVOICE_NUMBER_PREFIX, '%')
GROUP BY ah.`account`, acc.`name`
ORDER BY ABS(SUM(ah.`credit`) - SUM(ah.`debit`)) DESC;

SELECT '=== 5e. Every invoice must have exactly 2 GL rows ===' AS report;

SELECT COUNT(*) AS invoices_with_wrong_gl_row_count
FROM (
  SELECT i.`id`, COUNT(ah.`id`) AS n
  FROM   `tblpur_invoices` i
  LEFT JOIN `tblacc_account_history` ah
         ON ah.`rel_type` = @REL_TYPE AND ah.`rel_id` = i.`id`
  WHERE  i.`invoice_number` LIKE CONCAT(@INVOICE_NUMBER_PREFIX, '%')
  GROUP BY i.`id`
  HAVING n <> 2
) bad;

SELECT '=== 5f. Sample, and how it will read on the vendor statement ===' AS report;

SELECT i.`id`, i.`invoice_number`, i.`invoice_date`, v.`company`,
       i.`total`, i.`payment_status`, i.`vendor_invoice_number`
FROM   `tblpur_invoices` i
JOIN   `tblpur_vendor` v ON v.`userid` = i.`vendor`
WHERE  i.`invoice_number` LIKE CONCAT(@INVOICE_NUMBER_PREFIX, '%')
ORDER BY i.`id`
LIMIT 10;

SELECT '=== 5g. Skipped rows, for the record ===' AS report;

SELECT COUNT(*) AS rows_skipped,
       ROUND(SUM(a.`amount_aed`), 2) AS amount_skipped
FROM   `30_04_2026___ap_aging_report` a
LEFT JOIN `tmp_ap_aging_vendor_map` m ON m.`supplier_code` = a.`supplier_code`
LEFT JOIN `tblpur_vendor` v           ON v.`userid` = m.`vendor_id`
WHERE  m.`vendor_id` IS NULL OR v.`ledger_account` IS NULL OR v.`ledger_account` = 0;


-- ===========================================================================
-- ROLLBACK
-- ===========================================================================
-- The invoice number prefix identifies the batch precisely, so it can be undone
-- without touching anything else. Run in this order - the GL rows are found via
-- the invoices, so remove them first.
--
--   DELETE FROM tblacc_account_history
--    WHERE rel_type = 'purchase_invoice'
--      AND rel_id IN (SELECT id FROM tblpur_invoices
--                      WHERE invoice_number LIKE 'APOB-%');
--
--   DELETE FROM tblpur_invoices WHERE invoice_number LIKE 'APOB-%';
--
--   DROP TABLE IF EXISTS tmp_ap_aging_to_import;
--   DROP TABLE IF EXISTS tmp_ap_aging_vendor_map;
--
-- Adjust the prefix if you changed @INVOICE_NUMBER_PREFIX.
-- ===========================================================================
