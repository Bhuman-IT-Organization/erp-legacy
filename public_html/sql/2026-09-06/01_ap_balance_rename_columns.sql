-- ---------------------------------------------------------------------------
-- AP opening balances as of 30/04/2026 - step 1 of 4: name the columns
-- ---------------------------------------------------------------------------
--
-- The import landed as `COL 1` .. `COL 7` with the real column names sitting in
-- the first data row. This renames the columns to those names and adds the
-- columns the later steps fill in.
--
-- Column mapping, taken from row 1 of the sheet:
--
--   COL 1  Supplier                 -> sap_supplier_code   (e.g. 1000654)
--   COL 2  New System Vendor Code   -> vendor_code         (e.g. VEN0015)
--   COL 3  Supplier                 -> supplier_name
--   COL 4  Date                     -> doc_date_raw        (m/d/Y text)
--   COL 5  INV No                   -> invoice_no
--   COL 6  Amount AED               -> amount_aed_raw      (text, has commas)
--   COL 7  SAP Description          -> sap_description
--
-- The sheet uses "Supplier" for both the SAP code and the name, and a table
-- cannot hold two columns of the same name, so those two are named for what
-- they actually contain.
--
-- THIS FILE IS ONE SINGLE ALTER STATEMENT ON PURPOSE.
-- Run it once. On a second run it fails as a whole with
--
--     #1054 - Unknown column 'COL 1' in 'ngpp_ap_balances_...'
--
-- which is harmless and means the rename already happened. Nothing is half
-- applied, because one ALTER either succeeds completely or changes nothing.
-- Steps 02, 03 and 04 hold no DDL and are safe to re-run as often as you like.
-- ---------------------------------------------------------------------------

ALTER TABLE `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`
    CHANGE COLUMN `COL 1` `sap_supplier_code` VARCHAR(50)  NULL DEFAULT NULL,
    CHANGE COLUMN `COL 2` `vendor_code`       VARCHAR(50)  NULL DEFAULT NULL,
    CHANGE COLUMN `COL 3` `supplier_name`     VARCHAR(255) NULL DEFAULT NULL,
    CHANGE COLUMN `COL 4` `doc_date_raw`      VARCHAR(30)  NULL DEFAULT NULL,
    CHANGE COLUMN `COL 5` `invoice_no`        VARCHAR(100) NULL DEFAULT NULL,
    CHANGE COLUMN `COL 6` `amount_aed_raw`    VARCHAR(30)  NULL DEFAULT NULL,
    CHANGE COLUMN `COL 7` `sap_description`   VARCHAR(255) NULL DEFAULT NULL,
    ADD COLUMN `id`           INT UNSIGNED   NOT NULL AUTO_INCREMENT FIRST,
    ADD COLUMN `vendor_id`    INT            NULL DEFAULT NULL
        COMMENT 'tblpur_vendor.userid resolved from vendor_code',
    ADD COLUMN `doc_date`     DATE           NULL DEFAULT NULL
        COMMENT 'doc_date_raw parsed as m/d/Y',
    ADD COLUMN `amount`       DECIMAL(15,2)  NULL DEFAULT NULL
        COMMENT 'amount_aed_raw with the thousand separators removed. Minus = we still owe the vendor, plus = already paid / advance sitting with the vendor',
    ADD COLUMN `balance_type` VARCHAR(20)    NULL DEFAULT NULL
        COMMENT 'payable when amount < 0, advance when amount > 0',
    ADD PRIMARY KEY (`id`),
    ADD KEY `vendor_id` (`vendor_id`),
    ADD KEY `vendor_code` (`vendor_code`);
