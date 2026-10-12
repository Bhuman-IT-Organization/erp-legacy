-- ---------------------------------------------------------------------------
-- AP opening balances as of 30/04/2026 - step 2 of 4: clean and map to vendors
-- ---------------------------------------------------------------------------
--
-- Run step 00 first, or step 01 if you imported the spreadsheet yourself.
--
-- NOTHING IS DELETED AND NO SCHEMA IS CHANGED BY THIS FILE. It only fills in the
-- parsed and resolved columns, so it can be re-run any number of times and always
-- ends in the same state.
--
-- What it does:
--   1. trims whitespace off every text column
--   2. parses amount_aed_raw into a real DECIMAL
--   3. parses doc_date_raw into a real DATE
--   4. sets balance_type from the sign of the amount
--   5. resolves vendor_id from vendor_code
--
-- Sign convention, straight from the sheet:
--   amount < 0  the vendor is still owed this, we have to pay it   -> 'payable'
--   amount > 0  we already paid it, it sits as an advance          -> 'advance'
--
--
-- ABOUT THE `COLLATE utf8mb4_general_ci` ON THE VENDOR MATCHES IN STEP 5
--
-- Without it, on MySQL 8, both of those UPDATEs fail with
--
--     #1267 - Illegal mix of collations (utf8mb4_general_ci,IMPLICIT)
--             and (utf8mb4_0900_ai_ci,IMPLICIT) for operation '='
--
-- because the staging table takes the MySQL 8 server default of utf8mb4_0900_ai_ci
-- while tblpur_vendor is utf8mb4_general_ci. Forcing the comparison collation makes
-- the file work whatever collation either table happens to carry, which matters
-- because this database is already mixed - tblpur_invoices is utf8mb4_unicode_ci
-- while tblpur_vendor is utf8mb4_general_ci.
--
-- The codes are plain ASCII like VEN0072, so which collation is used changes nothing
-- about what matches.
--
-- If you hit #1267 and used --force, only these two UPDATEs were skipped; every
-- other statement in this file ran. Just run the file again, it is safe to repeat.
--
--
-- IF YOU CAME VIA FILE 01 rather than file 00, the staging table still holds two
-- rows that are not vendor lines: the spreadsheet header row, and the grand total
-- row that carries only an amount with every other column blank.
--
-- They are left in place rather than deleted, and they are harmless:
--   - the header row's amount_aed_raw reads 'Amount AED', which fails the numeric
--     test in step 2, so its amount stays NULL
--   - the grand total row has no vendor code, so step 5 cannot resolve a vendor_id
--
-- File 03 loads only rows that have both a vendor_id and an amount, so neither row
-- can reach the vendor opening balances. File 04 counts them out of its totals too.
-- ---------------------------------------------------------------------------


-- 1. Trim everything. The sheet has trailing spaces in several columns and the
--    vendor codes in tblpur_vendor have leading ones, which is why a plain
--    equality join found nothing for VEN0052 upwards.
UPDATE `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`
SET `sap_supplier_code` = TRIM(COALESCE(`sap_supplier_code`, '')),
    `vendor_code`       = TRIM(COALESCE(`vendor_code`, '')),
    `supplier_name`     = TRIM(COALESCE(`supplier_name`, '')),
    `doc_date_raw`      = TRIM(COALESCE(`doc_date_raw`, '')),
    `invoice_no`        = TRIM(COALESCE(`invoice_no`, '')),
    `amount_aed_raw`    = TRIM(COALESCE(`amount_aed_raw`, '')),
    `sap_description`   = TRIM(COALESCE(`sap_description`, ''));


-- 2. Amount. Strip the thousand separators and any stray spaces, then cast.
UPDATE `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`
SET `amount` = CAST(REPLACE(REPLACE(`amount_aed_raw`, ',', ''), ' ', '') AS DECIMAL(18,2))
WHERE `amount_aed_raw` <> ''
  AND REPLACE(REPLACE(REPLACE(`amount_aed_raw`, ',', ''), ' ', ''), '-', '') REGEXP '^[0-9]+(\\.[0-9]+)?$';


-- 3. Date. The sheet is m/d/Y. Two rows have no date at all and stay NULL -
--    file 04 lists them.
UPDATE `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`
SET `doc_date` = STR_TO_DATE(`doc_date_raw`, '%m/%d/%Y')
WHERE `doc_date_raw` <> ''
  AND STR_TO_DATE(`doc_date_raw`, '%m/%d/%Y') IS NOT NULL;


-- 4. Which side of the account each line sits on.
UPDATE `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`
SET `balance_type` = CASE
        WHEN `amount` IS NULL THEN NULL
        WHEN `amount` <  0    THEN 'payable'
        WHEN `amount` >  0    THEN 'advance'
        ELSE 'nil'
    END;


-- 5a. Vendor id, first pass: code AND company name both agree.
--     This pass exists because TRIM(vendor_code) = 'VEN0052' matches two
--     vendors - 56 ABM INKS INDUSTRY L.L.C and 57 HANGZHOU KUANGHU IMP. The
--     sheet says HANGZHOU KUANGHU, so the name decides it. Matching on the name
--     first means the ambiguous code is never resolved by luck.
UPDATE `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026` i
JOIN `tblpur_vendor` v
     ON TRIM(v.`vendor_code`) = i.`vendor_code` COLLATE utf8mb4_general_ci
    AND UPPER(TRIM(v.`company`)) = UPPER(i.`supplier_name`) COLLATE utf8mb4_general_ci
SET i.`vendor_id` = v.`userid`;


-- 5b. Vendor id, second pass: code only, and only where that code belongs to
--     exactly one vendor. The n = 1 guard is what keeps a duplicated code from
--     being assigned arbitrarily.
UPDATE `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026` i
JOIN (
        SELECT TRIM(`vendor_code`) AS code,
               MIN(`userid`)       AS userid,
               COUNT(*)            AS n
        FROM `tblpur_vendor`
        WHERE TRIM(COALESCE(`vendor_code`, '')) <> ''
        GROUP BY TRIM(`vendor_code`)
     ) u
     ON u.code = i.`vendor_code` COLLATE utf8mb4_general_ci
    AND u.n = 1
SET i.`vendor_id` = u.`userid`
WHERE i.`vendor_id` IS NULL;
