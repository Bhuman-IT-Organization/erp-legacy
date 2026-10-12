-- ---------------------------------------------------------------------------
-- AP opening balances as of 30/04/2026 - step 4 of 4: verify
-- ---------------------------------------------------------------------------
--
-- Read only. Run this after 01, 02 and 03 and read the result sets top to
-- bottom. Every one of them should match what the notes below say.
-- ---------------------------------------------------------------------------

-- 1. Row counts. Expect 180 data rows, 178 mapped, 2 unmapped until the VEN0077
--    vendor exists, and 178 loaded.
--
--    "data rows" excludes the spreadsheet header row and grand total row, which are
--    still present if you loaded via file 01 rather than file 00. Neither is a vendor
--    line: the header row has no parsable amount, the total row has no vendor code.
SELECT 'data rows'   AS what, COUNT(*) AS n
FROM `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`
WHERE `amount` IS NOT NULL AND TRIM(COALESCE(`vendor_code`, '')) <> ''
UNION ALL
SELECT 'mapped to a vendor', COUNT(*)
FROM `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`
WHERE `vendor_id` IS NOT NULL
UNION ALL
SELECT 'NOT mapped to a vendor', COUNT(*)
FROM `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`
WHERE `vendor_id` IS NULL
  AND `amount` IS NOT NULL AND TRIM(COALESCE(`vendor_code`, '')) <> ''
UNION ALL
SELECT 'date failed to parse', COUNT(*)
FROM `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`
WHERE `doc_date` IS NULL
  AND `amount` IS NOT NULL AND TRIM(COALESCE(`vendor_code`, '')) <> ''
UNION ALL
SELECT 'non data rows still present (0 via file 00, 2 via file 01)', COUNT(*)
FROM `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`
WHERE `amount` IS NULL OR TRIM(COALESCE(`vendor_code`, '')) = ''
UNION ALL
SELECT 'loaded into tblpur_vendor_opening_balance', COUNT(*)
FROM `tblpur_vendor_opening_balance`
WHERE `source` = 'ap_balance_30_04_2026';


-- 2. The money has to tie out. The data row total must read -28330450.48, which is
--    exactly the grand total printed on the spreadsheet. If it differs, something
--    was lost in the parse.
SELECT 'total of the data rows' AS what,
       ROUND(SUM(`amount`), 2) AS total_aed
FROM `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`
WHERE `amount` IS NOT NULL AND TRIM(COALESCE(`vendor_code`, '')) <> ''
UNION ALL
SELECT 'loaded total',
       ROUND(SUM(`amount`), 2)
FROM `tblpur_vendor_opening_balance`
WHERE `source` = 'ap_balance_30_04_2026';


-- 3. Split by side of the account.
SELECT `balance_type`,
       COUNT(*)                AS lines_count,
       ROUND(SUM(`amount`), 2) AS total_aed
FROM `tblpur_vendor_opening_balance`
WHERE `source` = 'ap_balance_30_04_2026'
GROUP BY `balance_type`;


-- 4. Anything that did not find a vendor. Each of these needs the vendor creating,
--    or its vendor_code correcting. Because 03 and 06 will not re-load once loaded,
--    fixing these means running 07 first, then 02, 03 and 06 again.
SELECT `vendor_code`, `sap_supplier_code`, `supplier_name`,
       COUNT(*)                AS lines_count,
       ROUND(SUM(`amount`), 2) AS total_aed
FROM `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`
WHERE `vendor_id` IS NULL
  AND `amount` IS NOT NULL AND TRIM(COALESCE(`vendor_code`, '')) <> ''
GROUP BY `vendor_code`, `sap_supplier_code`, `supplier_name`;


-- 5. Vendor codes held by more than one vendor. A duplicate here is why step 02
--    resolves on the company name first. Expect exactly one row: VEN0052.
SELECT TRIM(`vendor_code`) AS code,
       COUNT(*) AS vendors,
       GROUP_CONCAT(CONCAT(`userid`, ' = ', `company`) SEPARATOR '  |  ') AS who
FROM `tblpur_vendor`
WHERE TRIM(COALESCE(`vendor_code`, '')) <> ''
GROUP BY TRIM(`vendor_code`)
HAVING COUNT(*) > 1;


-- 6. Net position per vendor. This is the answer to the question the sheet was
--    asking. A negative net means the vendor is still owed that much, a positive
--    net means we are that much in advance with them.
SELECT ob.`vendor_id`,
       v.`company`,
       ob.`vendor_code`,
       COUNT(*)                     AS lines_count,
       ROUND(SUM(CASE WHEN ob.`amount` < 0 THEN -ob.`amount` ELSE 0 END), 2) AS still_to_pay,
       ROUND(SUM(CASE WHEN ob.`amount` > 0 THEN  ob.`amount` ELSE 0 END), 2) AS advance_paid,
       ROUND(SUM(ob.`amount`), 2)   AS net_amount,
       CASE
           WHEN SUM(ob.`amount`) < 0 THEN 'we owe the vendor'
           WHEN SUM(ob.`amount`) > 0 THEN 'advance with the vendor'
           ELSE 'square'
       END AS position
FROM `tblpur_vendor_opening_balance` ob
LEFT JOIN `tblpur_vendor` v ON v.`userid` = ob.`vendor_id`
WHERE ob.`source` = 'ap_balance_30_04_2026'
GROUP BY ob.`vendor_id`, v.`company`, ob.`vendor_code`
ORDER BY net_amount;


-- 7. Vendors that were mapped but whose name in the system does not look like
--    the name in the sheet. The sheet truncates names at 20 characters, so a
--    prefix match is the right test. Anything listed here is worth an eyeball,
--    it means a code was reused or mistyped.
SELECT DISTINCT ob.`vendor_id`, ob.`vendor_code`,
       ob.`supplier_name` AS name_in_sheet,
       v.`company`        AS name_in_system
FROM `tblpur_vendor_opening_balance` ob
JOIN `tblpur_vendor` v ON v.`userid` = ob.`vendor_id`
WHERE ob.`source` = 'ap_balance_30_04_2026'
  -- COLLATE for the same reason file 02 needs it: these two tables can carry
  -- different collations and a bare comparison then raises #1267.
  AND UPPER(TRIM(v.`company`)) NOT LIKE
      CONCAT(UPPER(LEFT(TRIM(ob.`supplier_name`), 12)), '%') COLLATE utf8mb4_general_ci
ORDER BY ob.`vendor_code`;
