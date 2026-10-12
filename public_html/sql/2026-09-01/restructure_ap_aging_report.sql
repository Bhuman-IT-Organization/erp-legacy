-- ---------------------------------------------------------------------------
-- Restructure the AP aging report import table.
--
-- `30_04_2026___ap_aging_report` arrived as a raw spreadsheet import: six
-- columns named "COL 1" to "COL 6", every one of them varchar, no primary key,
-- and the spreadsheet's own header and grand total rows stored as data.
--
-- WHAT THIS DOES
--   1. adds an AUTO_INCREMENT primary key
--   2. names the columns after the spreadsheet headings
--   3. drops the header row
--   4. drops the grand total row - see the warning below
--   5. stores the amount as DECIMAL(15,2), the same type used for money
--      everywhere else in this database (tblpur_invoices.total,
--      tblinv_shipping_charges.total and so on)
--   6. stores the date as a real DATE
--
-- COLUMN MAPPING
--   COL 1  ->  supplier_code      spreadsheet heading "Supplier"
--   COL 2  ->  supplier_name      spreadsheet heading "Supplier"
--   COL 3  ->  invoice_date       "Date",   was m/d/Y text
--   COL 4  ->  inv_no             "INV No"
--   COL 5  ->  amount_aed         "Amount AED", was text with thousand separators
--   COL 6  ->  sap_description    "SAP Description"
--
-- Both COL 1 and COL 2 are headed "Supplier" in the spreadsheet. MySQL cannot
-- have two columns of the same name, so they are split into supplier_code (the
-- numeric account, e.g. 1000654) and supplier_name.
--
-- WARNING - THE GRAND TOTAL ROW
--   One row had every column blank except an amount of -28,330,450.48, which is
--   exactly the sum of the other 185 rows. It is the spreadsheet's total line,
--   not a payable. Left in place, every SUM() over this table double counted:
--   the whole table summed to -56,660,900.96 instead of -28,330,450.48.
--   It is excluded here. The check at the end proves the new total matches.
--
-- WARNING - TRUNCATED SOURCE DATA
--   The import declared COL 2 as varchar(20) and COL 6 as varchar(50), and the
--   longest value in each is exactly 20 and 50 characters. Supplier names and
--   descriptions were therefore cut off at import time
--   ("GUANGZHOU YIDE PRINT", "WUXI KAICHUANG MOULD"). That text is already lost
--   and this file cannot recover it. The new columns are varchar(191) so a
--   re-import will not truncate again.
--
-- REVERSIBLE. The original table is kept untouched as
-- `30_04_2026___ap_aging_report_bak_20260901`.
--
-- NOT RE-RUNNABLE as-is: on a second run the RENAME fails because the backup
-- already exists, which is deliberate - it stops a second run from overwriting
-- the only copy of the original.
--
--   mysql -u root nextgen_live -e "source sql/2026-09-01/restructure_ap_aging_report.sql"
-- ---------------------------------------------------------------------------

SELECT '--- Before ---' AS step;

SELECT COUNT(*) AS all_rows,
       SUM(CASE WHEN `COL 1` = 'Supplier' THEN 1 ELSE 0 END)                     AS header_rows,
       SUM(CASE WHEN TRIM(COALESCE(`COL 1`, '')) = '' THEN 1 ELSE 0 END)          AS total_rows_blank_code,
       ROUND(SUM(CAST(REPLACE(TRIM(`COL 5`), ',', '') AS DECIMAL(18,2))), 2)      AS sum_including_junk
FROM `30_04_2026___ap_aging_report`
WHERE `COL 1` <> 'Supplier' OR `COL 1` IS NULL;

-- ── 1. Build the new table ────────────────────────────────────────────────

DROP TABLE IF EXISTS `ap_aging_report_new_20260901`;

CREATE TABLE `ap_aging_report_new_20260901` (
  `id`              INT(11) NOT NULL AUTO_INCREMENT,
  `supplier_code`   VARCHAR(20)  NULL DEFAULT NULL,
  `supplier_name`   VARCHAR(191) NULL DEFAULT NULL,
  `invoice_date`    DATE         NULL DEFAULT NULL,
  `inv_no`          VARCHAR(100) NULL DEFAULT NULL,
  `amount_aed`      DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `sap_description` VARCHAR(191) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_apag_supplier_code` (`supplier_code`),
  KEY `idx_apag_invoice_date` (`invoice_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ── 2. Copy the real rows across, converted ───────────────────────────────
-- Ordered by supplier then date so the AUTO_INCREMENT ids run in a sensible
-- sequence rather than whatever order the import happened to use.
--
-- NULLIF(...,'') on the date keeps the two genuine rows that have no date as
-- NULL instead of failing; STR_TO_DATE already returns NULL for them, this just
-- makes the intent explicit.

INSERT INTO `ap_aging_report_new_20260901`
  (`supplier_code`, `supplier_name`, `invoice_date`, `inv_no`, `amount_aed`, `sap_description`)
SELECT
  NULLIF(TRIM(`COL 1`), ''),
  NULLIF(TRIM(`COL 2`), ''),
  STR_TO_DATE(NULLIF(TRIM(`COL 3`), ''), '%m/%d/%Y'),
  NULLIF(TRIM(`COL 4`), ''),
  CAST(REPLACE(REPLACE(TRIM(`COL 5`), ',', ''), ' ', '') AS DECIMAL(15,2)),
  NULLIF(TRIM(`COL 6`), '')
FROM `30_04_2026___ap_aging_report`
WHERE `COL 1` IS NOT NULL
  AND TRIM(`COL 1`) <> ''          -- excludes the grand total row
  AND `COL 1` <> 'Supplier'        -- excludes the header row
ORDER BY TRIM(`COL 1`), STR_TO_DATE(NULLIF(TRIM(`COL 3`), ''), '%m/%d/%Y');

-- ── 3. Swap the tables ────────────────────────────────────────────────────

RENAME TABLE `30_04_2026___ap_aging_report` TO `30_04_2026___ap_aging_report_bak_20260901`;
RENAME TABLE `ap_aging_report_new_20260901` TO `30_04_2026___ap_aging_report`;

-- ── 4. Verify ─────────────────────────────────────────────────────────────

SELECT '--- After: structure ---' AS step;
SHOW COLUMNS FROM `30_04_2026___ap_aging_report`;

SELECT '--- After: totals must reconcile ---' AS step;

SELECT
  (SELECT COUNT(*) FROM `30_04_2026___ap_aging_report`)                      AS rows_now,
  (SELECT COUNT(*) FROM `30_04_2026___ap_aging_report_bak_20260901`)         AS rows_before,
  (SELECT ROUND(SUM(`amount_aed`), 2) FROM `30_04_2026___ap_aging_report`)   AS total_now,
  (SELECT ROUND(SUM(CAST(REPLACE(TRIM(`COL 5`), ',', '') AS DECIMAL(18,2))), 2)
     FROM `30_04_2026___ap_aging_report_bak_20260901`
    WHERE `COL 1` IS NOT NULL AND TRIM(`COL 1`) <> '' AND `COL 1` <> 'Supplier') AS total_before_real_rows_only;

SELECT '--- After: the id column increments ---' AS step;
SELECT MIN(`id`) AS min_id, MAX(`id`) AS max_id, COUNT(DISTINCT `id`) AS distinct_ids
FROM `30_04_2026___ap_aging_report`;

SELECT '--- After: no header or total row survived ---' AS step;
SELECT SUM(CASE WHEN `supplier_code` = 'Supplier' THEN 1 ELSE 0 END) AS header_rows_should_be_0,
       SUM(CASE WHEN `supplier_code` IS NULL THEN 1 ELSE 0 END)      AS blank_code_rows_should_be_0
FROM `30_04_2026___ap_aging_report`;

SELECT '--- After: sample ---' AS step;
SELECT * FROM `30_04_2026___ap_aging_report` ORDER BY `id` LIMIT 8;

SELECT '--- After: rows with no date (expected: 2 genuine ones) ---' AS step;
SELECT `id`, `supplier_code`, `supplier_name`, `inv_no`, `amount_aed`
FROM   `30_04_2026___ap_aging_report`
WHERE  `invoice_date` IS NULL;

-- ---------------------------------------------------------------------------
-- ROLLBACK, if ever needed:
--
--   DROP TABLE `30_04_2026___ap_aging_report`;
--   RENAME TABLE `30_04_2026___ap_aging_report_bak_20260901`
--             TO `30_04_2026___ap_aging_report`;
--
-- Once satisfied:
--   DROP TABLE `30_04_2026___ap_aging_report_bak_20260901`;
-- ---------------------------------------------------------------------------
