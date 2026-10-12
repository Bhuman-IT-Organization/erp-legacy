-- ---------------------------------------------------------------------------
-- AP opening balances as of 30/04/2026 - step 6: one invoice record per line
-- ---------------------------------------------------------------------------
--
-- This is what makes each line show on the vendor statement as its own dated
-- row instead of being swallowed into a single "Beginning Balance" figure.
--
-- No PHP is involved. The vendor statement already reads tblpur_invoices, so
-- writing one invoice record per opening balance line is enough.
--
-- Run 00 (or 01) then 02 and 03 first.
--
-- NOTHING IS EVER DELETED BY THIS FILE. It only inserts.
--
-- Safe to re-run. The insert carries a guard that makes it do nothing at all if any
-- OB invoice already exists, so a second run adds no rows rather than a second full
-- set of 178. Worth knowing why the guard is written that way: an earlier version
-- checked each row's own OB number instead, and because reloading the data handed
-- the rows fresh ids, the numbers changed and the check stopped matching. A second
-- pass produced 356 invoices instead of 178. Files 00 and 03 now use fixed ids and
-- insert-only guards, and this file checks for the whole set rather than row by row,
-- so that cannot happen.
--
-- File 07 is the only file that removes these records, and it exists purely for that
-- purpose. If you need to rework the figures, run 07 and then this file again.
--
--
-- SIGN, which is the whole point of the exercise
--
--   sheet amount NEGATIVE  = due for payment    -> invoice total POSITIVE
--   sheet amount POSITIVE  = advance paid       -> invoice total NEGATIVE
--
-- A negative invoice total is what carries an advance. On the statement it lands
-- in the Amount column as a negative figure and moves the running balance the
-- same way a payment does, so the vendor ends up showing as being in advance.
-- This works on the screen and in the PDF because both derive the direction from
-- the sign of the total.
--
-- Worked example, VEN0072 HASSAN IBRAHIM AL FARDAN, vendor 77. Four positive
-- lines, so four invoices each with a negative total:
--
--   10-04-2025  000010  advance   38,000.00  ->  total -38,000.00
--   13-06-2025  000007  advance   36,000.00  ->  total -36,000.00
--   13-06-2025  000109  advance   36,000.00  ->  total -36,000.00
--   07-10-2025  000161  advance   37,000.00  ->  total -37,000.00
--                                 ----------
--                                 147,000.00 total advance
--
-- Set the statement date range to cover 2025 and all four appear as separate
-- dated rows carrying their own cheque number in the Vendor Invoice # column.
--
--
-- THREE THINGS TO KNOW BEFORE RUNNING IT
--
-- 1. Document numbering is untouched. These rows carry number = 0 and an
--    invoice_number of 'OBnnnnn'. The purchase module takes its next invoice
--    number from the next_inv_number option in tblpurchase_option, not from
--    MAX(number), and this file does not touch that option. The next real
--    invoice is still INV00065.
--
-- 2. Nothing is posted to the general ledger. acc_mapping = 0. The opening trial
--    balance already carries these balances, so posting them again would double
--    the payable.
--
-- 3. These rows do appear in the purchase invoice list, because that list reads
--    the same table the statement does. They are recognisable by the OB prefix
--    and by having no purchase order or GRN behind them. An advance line shows a
--    negative total there too.
-- ---------------------------------------------------------------------------


INSERT INTO `tblpur_invoices`
    (`number`, `invoice_number`, `invoice_date`, `subtotal`, `tax_rate`, `tax`,
     `total`, `vendor`, `payment_status`, `vendor_note`, `adminnote`,
     `add_from`, `add_from_type`, `date_add`, `duedate`, `currency`,
     `currency_rate`, `discount_total`, `discount_percent`, `shipping_fee`,
     `vendor_invoice_number`, `approval_status`, `acc_mapping`, `is_deleted`)
SELECT
    0,
    CONCAT('OB', LPAD(ob.`id`, 5, '0')),
    ob.`doc_date`,
    -ob.`amount`,
    0,
    0.00,
    -ob.`amount`,
    ob.`vendor_id`,
    -- An advance has already been paid, so nothing should try to pay it again.
    CASE WHEN ob.`amount` > 0 THEN 'paid' ELSE 'unpaid' END,
    ob.`description`,
    CONCAT('Opening balance brought forward from the AP balance as of 30/04/2026. ',
           'SAP supplier ', COALESCE(ob.`sap_supplier_code`, '-'),
           ', vendor code ', COALESCE(ob.`vendor_code`, '-'),
           ', reference ', COALESCE(NULLIF(ob.`invoice_no`, ''), '-'),
           '. ',
           CASE WHEN ob.`amount` > 0
                THEN 'Advance already paid to the vendor.'
                ELSE 'Due for payment to the vendor.'
           END),
    0,
    'admin',
    ob.`doc_date`,
    ob.`doc_date`,
    -- The sheet is in AED, which is the base currency (tblcurrencies id 1).
    1,
    1.000000,
    0.00,
    0.00,
    0.00,
    ob.`invoice_no`,
    -- Approved, so it is not sitting in somebody's approval queue.
    2,
    0,
    0
FROM `tblpur_vendor_opening_balance` ob
WHERE ob.`source` = 'ap_balance_30_04_2026'
  AND ob.`vendor_id` IS NOT NULL
  AND ob.`amount` <> 0
  -- Do nothing at all if the set already exists, so a second run of this file adds
  -- no rows instead of a second full set. This is what replaces a DELETE.
  AND NOT EXISTS (
        SELECT 1 FROM (
            SELECT 1 FROM `tblpur_invoices` WHERE `invoice_number` LIKE 'OB%' LIMIT 1
        ) already_loaded
      );


-- ---------------------------------------------------------------------------
-- Verification. Read every result set.
-- ---------------------------------------------------------------------------

-- 1. One invoice per opening balance line. The two numbers must match.
SELECT 'opening balance lines' AS what, COUNT(*) AS n
FROM `tblpur_vendor_opening_balance`
WHERE `source` = 'ap_balance_30_04_2026' AND `vendor_id` IS NOT NULL AND `amount` <> 0
UNION ALL
SELECT 'invoice records created', COUNT(*)
FROM `tblpur_invoices`
WHERE `invoice_number` LIKE 'OB%';


-- 2. The money must tie out, with the sign flipped. If the opening balance rows
--    sum to -28,330,282.48 then the invoices must sum to +28,330,282.48.
SELECT 'sum of opening balance lines' AS what, ROUND(SUM(`amount`), 2) AS total_aed
FROM `tblpur_vendor_opening_balance`
WHERE `source` = 'ap_balance_30_04_2026' AND `vendor_id` IS NOT NULL
UNION ALL
SELECT 'sum of invoice totals', ROUND(SUM(`total`), 2)
FROM `tblpur_invoices`
WHERE `invoice_number` LIKE 'OB%';


-- 3. Document numbering is undisturbed.
SELECT `option_name`, `option_val`
FROM `tblpurchase_option`
WHERE `option_name` IN ('next_inv_number', 'pur_inv_prefix');


-- 4. The VEN0072 worked example, exactly as it will read on the statement.
SELECT pi.`invoice_date`         AS statement_date,
       pi.`invoice_number`       AS details_shows,
       pi.`vendor_invoice_number` AS vendor_ref_column,
       pi.`total`                AS amount_column,
       CASE WHEN pi.`total` < 0 THEN 'advance paid' ELSE 'due for payment' END AS meaning,
       pi.`vendor_note`          AS description
FROM `tblpur_invoices` pi
JOIN `tblpur_vendor` v ON v.`userid` = pi.`vendor`
WHERE pi.`invoice_number` LIKE 'OB%'
  AND TRIM(v.`vendor_code`) = 'VEN0072'
ORDER BY pi.`invoice_date`;


-- 5. Net position per vendor as the statement will now show it. A negative net
--    here means the vendor is owed, a positive net means we are in advance.
SELECT pi.`vendor`   AS vendor_id,
       v.`company`,
       COUNT(*)      AS invoice_records,
       ROUND(SUM(CASE WHEN pi.`total` > 0 THEN pi.`total` ELSE 0 END), 2)  AS due_for_payment,
       ROUND(SUM(CASE WHEN pi.`total` < 0 THEN -pi.`total` ELSE 0 END), 2) AS advance_paid,
       ROUND(SUM(pi.`total`), 2) AS net_owed_to_vendor
FROM `tblpur_invoices` pi
LEFT JOIN `tblpur_vendor` v ON v.`userid` = pi.`vendor`
WHERE pi.`invoice_number` LIKE 'OB%'
GROUP BY pi.`vendor`, v.`company`
ORDER BY net_owed_to_vendor DESC;
