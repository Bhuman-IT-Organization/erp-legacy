-- ---------------------------------------------------------------------------
-- AP opening balances as of 30/04/2026 - step 3 of 4: attach to the vendors
-- ---------------------------------------------------------------------------
--
-- Copies the cleaned import into tblpur_vendor_opening_balance, one row per
-- line, keyed on the vendor. This is the table the application reads, so the
-- balances show on each vendor's statement.
--
-- The staging table stays as it is, as the raw record of what was imported.
--
-- NOTHING IS EVER DELETED BY THIS FILE.
--
-- Safe to re-run. The insert carries a guard that makes it do nothing at all if
-- this snapshot has already been loaded, so a second run adds no rows rather than
-- duplicating them. A snapshot loaded later under a different `source` is not
-- affected either way.
--
-- CREATE TABLE IF NOT EXISTS produces a note rather than an error, so this file
-- will not stop in phpMyAdmin on a repeat run.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tblpur_vendor_opening_balance` (
    `id`                INT(11)       NOT NULL AUTO_INCREMENT,
    `vendor_id`         INT(11)       NOT NULL COMMENT 'tblpur_vendor.userid',
    `vendor_code`       VARCHAR(50)   DEFAULT NULL,
    `sap_supplier_code` VARCHAR(50)   DEFAULT NULL,
    `supplier_name`     VARCHAR(255)  DEFAULT NULL COMMENT 'name as it appeared in the source file',
    `doc_date`          DATE          DEFAULT NULL,
    `invoice_no`        VARCHAR(100)  DEFAULT NULL COMMENT 'the vendor reference from the source file',
    `description`       VARCHAR(255)  DEFAULT NULL,
    `amount`            DECIMAL(15,2) NOT NULL DEFAULT 0.00
        COMMENT 'as imported. Minus = we still owe the vendor, plus = already paid / advance',
    `balance_type`      VARCHAR(20)   DEFAULT NULL COMMENT 'payable | advance',
    `as_of_date`        DATE          DEFAULT NULL COMMENT 'the snapshot date, not the document date',
    `source`            VARCHAR(100)  DEFAULT NULL COMMENT 'which import produced this row',
    `date_added`        DATETIME      DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `vendor_id` (`vendor_id`),
    KEY `source` (`source`),
    KEY `as_of_date` (`as_of_date`),
    KEY `balance_type` (`balance_type`)
-- Collation stated explicitly rather than left to the server default, which is
-- utf8mb4_0900_ai_ci on MySQL 8 and would not compare against tblpur_vendor's
-- utf8mb4_general_ci without raising #1267.
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


INSERT INTO `tblpur_vendor_opening_balance`
    (`vendor_id`, `vendor_code`, `sap_supplier_code`, `supplier_name`,
     `doc_date`, `invoice_no`, `description`, `amount`, `balance_type`,
     `as_of_date`, `source`, `date_added`)
SELECT
    i.`vendor_id`,
    i.`vendor_code`,
    i.`sap_supplier_code`,
    i.`supplier_name`,
    -- Two rows in the sheet have no date. They fall back to the snapshot date so
    -- they still land on the statement instead of vanishing on a date filter.
    COALESCE(i.`doc_date`, '2026-04-30'),
    i.`invoice_no`,
    i.`sap_description`,
    i.`amount`,
    i.`balance_type`,
    '2026-04-30',
    'ap_balance_30_04_2026',
    NOW()
FROM `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026` i
-- Both conditions together are what keeps the spreadsheet header row and grand
-- total row out, if you came via file 01 and they are still in the staging table.
WHERE i.`vendor_id` IS NOT NULL
  AND i.`amount` IS NOT NULL
  AND i.`amount` <> 0
  -- Do nothing at all if this snapshot is already loaded, so a second run of this
  -- file adds no rows instead of duplicating them. This is what replaces a DELETE.
  AND NOT EXISTS (
        SELECT 1 FROM `tblpur_vendor_opening_balance` existing
        WHERE existing.`source` = 'ap_balance_30_04_2026'
      );


-- Expect the row count to be the same after a second run as after the first.
SELECT COUNT(*) AS rows_loaded,
       ROUND(SUM(`amount`), 2) AS total_aed
FROM `tblpur_vendor_opening_balance`
WHERE `source` = 'ap_balance_30_04_2026';
