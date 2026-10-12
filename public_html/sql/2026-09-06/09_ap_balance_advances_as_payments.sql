-- ---------------------------------------------------------------------------
-- AP opening balances as of 30/04/2026 - step 9: advances into the Payments column
-- ---------------------------------------------------------------------------
--
-- Run 00 (or 01), then 02, 03 and 06, then this file.
--
-- FILES 06 AND 07 ARE NOT MODIFIED BY ANY OF THIS. Everything needed is in here.
--
--
-- WHAT IT FIXES
--
-- File 06 writes an advance line as an invoice with a NEGATIVE total. The balance
-- comes out correct, but the statement then shows a minus figure in the Amount
-- column - vendor 67 BIZGROUP reads -511,043.07. That is the wrong place for it: an
-- advance is money that has already gone out, so it belongs in the Payments column as
-- a positive, exactly like any other payment to the vendor.
--
-- This file moves the 49 advance lines out of Amount and into Payments, as positives.
--
--
-- THE BALANCE DOES NOT MOVE. This is presentation only.
--
-- The statement treats the two forms identically:
--
--   as a negative invoice   running balance does  tmp - (-511,043.07) = tmp + 511,043.07
--   as a payment            running balance does  tmp + 511,043.07
--
-- and in the summary block:
--
--   as a negative invoice   invoiced_amount falls by 511,043.07, amount_paid unchanged
--   as a payment            invoiced_amount unchanged, amount_paid rises by 511,043.07
--
-- balance_due is invoiced_amount - amount_paid + beginning_balance + refunds, so both
-- give the same answer. Vendor 67 still ends on 18,366,428.73 either way.
--
--
-- HOW, IN THREE STEPS
--
--   1. Hide the negative invoices file 06 created, by setting is_deleted = 1. The
--      statement filters on is_deleted, so they drop out of the Amount column and out
--      of the beginning balance. NOT A DELETE - the rows stay, and step 1 of the
--      reversal note below puts them straight back.
--
--   2. Create one carrier invoice per vendor with advances, numbered OBADVnnnnn, with
--      a total of 0.00. The statement reads payments through a join to the invoice
--      table, so a payment has to hang off an invoice - this schema has no payment on
--      account. Ten of the eighteen vendors with advances have no payable line to
--      attach one to, hence the carriers.
--
--      Zero is deliberate. A carrier with a real value would add to what is owed and
--      move the balance, which is precisely what must not happen. It shows on the
--      statement as a single 0.00 row above that vendor's advance payments, which is
--      the one visible cost of doing this without touching any PHP.
--
--      One carrier per vendor, rather than attaching advances to an existing payable,
--      so that no payable invoice has its payment status distorted.
--
--   3. Create one payment per advance line against that vendor's carrier, carrying the
--      line's own date, amount and reference from the sheet.
--
--
-- NOTHING IS DELETED BY THIS FILE. Safe to re-run: every statement is either an
-- UPDATE that is already true, or an INSERT with a guard that makes it do nothing when
-- the set already exists.
--
--
-- TO UNDO JUST THIS FILE and go back to how file 06 left things:
--
--     DELETE FROM `tblpur_invoice_payment`
--     WHERE `pur_invoice` IN (SELECT `id` FROM `tblpur_invoices`
--                             WHERE `invoice_number` LIKE 'OBADV%');
--     DELETE FROM `tblpur_invoices` WHERE `invoice_number` LIKE 'OBADV%';
--     UPDATE `tblpur_invoices` SET `is_deleted` = 0, `date_deleted` = NULL
--     WHERE `invoice_number` LIKE 'OB%' AND `invoice_number` NOT LIKE 'OBADV%'
--       AND `total` < 0;
--
-- File 07 still removes everything, unchanged: 'OBADV' starts with 'OB', so its
-- existing patterns already cover the carriers and the payments hanging off them.
--
--
-- Document numbering is untouched, same as file 06: number = 0, and the next invoice
-- number comes from the next_inv_number option which this file does not go near.
-- Nothing is posted to the general ledger either, acc_mapping = 0 throughout, because
-- the opening trial balance already carries these figures.
-- ---------------------------------------------------------------------------


-- 1. Take the negative invoices out of the statement. The rows are kept, only flagged.
UPDATE `tblpur_invoices`
SET `is_deleted`   = 1,
    `date_deleted` = NOW(),
    `adminnote`    = CONCAT(COALESCE(`adminnote`, ''),
                            ' | Superseded by file 09: this advance is now recorded as',
                            ' a payment against the OBADV carrier for this vendor, so',
                            ' it reads as a positive in the Payments column. Flagged',
                            ' rather than removed so it can be put back.')
WHERE `invoice_number` LIKE 'OB%'
  AND `invoice_number` NOT LIKE 'OBADV%'
  AND `total` < 0
  AND `is_deleted` = 0;


-- 2. One carrier invoice per vendor that has advance lines. Total 0.00, dated the
--    earliest advance for that vendor so it sorts above them on the statement.
INSERT INTO `tblpur_invoices`
    (`number`, `invoice_number`, `invoice_date`, `subtotal`, `tax_rate`, `tax`,
     `total`, `vendor`, `payment_status`, `vendor_note`, `adminnote`,
     `add_from`, `add_from_type`, `date_add`, `duedate`, `currency`,
     `currency_rate`, `discount_total`, `discount_percent`, `shipping_fee`,
     `vendor_invoice_number`, `approval_status`, `acc_mapping`, `is_deleted`)
SELECT
    0,
    CONCAT('OBADV', LPAD(ob.`vendor_id`, 5, '0')),
    MIN(ob.`doc_date`),
    0.00,
    0,
    0.00,
    0.00,
    ob.`vendor_id`,
    'unpaid',
    'Advance payments brought forward from the AP balance as of 30/04/2026',
    CONCAT('Carrier record only, value 0.00. It exists so the ',
           COUNT(*),
           ' advance payment(s) below can be attached to something, because the ',
           'statement reads payments through a join to the invoice table. It adds ',
           'nothing to what is owed.'),
    0,
    'admin',
    MIN(ob.`doc_date`),
    MIN(ob.`doc_date`),
    -- The sheet is in AED, which is the base currency (tblcurrencies id 1).
    1,
    1.000000,
    0.00,
    0.00,
    0.00,
    NULL,
    2,
    0,
    0
FROM `tblpur_vendor_opening_balance` ob
WHERE ob.`source` = 'ap_balance_30_04_2026'
  AND ob.`vendor_id` IS NOT NULL
  AND ob.`amount` > 0
  -- Do nothing at all if the carriers already exist. This is what replaces a DELETE.
  AND NOT EXISTS (
        SELECT 1 FROM (
            SELECT 1 FROM `tblpur_invoices`
            WHERE `invoice_number` LIKE 'OBADV%' LIMIT 1
        ) already_loaded
      )
GROUP BY ob.`vendor_id`;


-- 3. One payment per advance line, attached to that vendor's carrier.
--
--    approval_status = 2 is required: the statement only counts payments that are
--    approved, so anything else would be invisible and the balance would be wrong.
INSERT INTO `tblpur_invoice_payment`
    (`pur_invoice`, `amount`, `paymentmode`, `date`, `daterecorded`, `note`,
     `transactionid`, `approval_status`, `acc_mapping`, `is_deleted`)
SELECT
    carrier.`id`,
    ob.`amount`,
    NULL,
    ob.`doc_date`,
    NOW(),
    CONCAT('Opening balance advance as of 30/04/2026. Reference ',
           COALESCE(NULLIF(ob.`invoice_no`, ''), '-'),
           '. ',
           COALESCE(NULLIF(ob.`description`, ''), 'No description in the source file.')),
    ob.`invoice_no`,
    2,
    0,
    0
FROM `tblpur_vendor_opening_balance` ob
JOIN `tblpur_invoices` carrier
     ON carrier.`invoice_number` = CONCAT('OBADV', LPAD(ob.`vendor_id`, 5, '0'))
WHERE ob.`source` = 'ap_balance_30_04_2026'
  AND ob.`vendor_id` IS NOT NULL
  AND ob.`amount` > 0
  AND NOT EXISTS (
        SELECT 1 FROM (
            SELECT 1 FROM `tblpur_invoice_payment` p
            JOIN `tblpur_invoices` i ON i.`id` = p.`pur_invoice`
            WHERE i.`invoice_number` LIKE 'OBADV%' LIMIT 1
        ) already_loaded
      );


-- ---------------------------------------------------------------------------
-- Verification. Read every result set.
-- ---------------------------------------------------------------------------

-- 1. Counts. Expect 49 hidden, 18 carriers, 49 payments, and 129 invoices still
--    visible in the Amount column.
SELECT 'advance invoices hidden' AS what, COUNT(*) AS n
FROM `tblpur_invoices`
WHERE `invoice_number` LIKE 'OB%' AND `invoice_number` NOT LIKE 'OBADV%'
  AND `total` < 0 AND `is_deleted` = 1
UNION ALL
SELECT 'carrier invoices created', COUNT(*)
FROM `tblpur_invoices` WHERE `invoice_number` LIKE 'OBADV%'
UNION ALL
SELECT 'advance payments created', COUNT(*)
FROM `tblpur_invoice_payment` p
JOIN `tblpur_invoices` i ON i.`id` = p.`pur_invoice`
WHERE i.`invoice_number` LIKE 'OBADV%'
UNION ALL
SELECT 'payable invoices still visible', COUNT(*)
FROM `tblpur_invoices`
WHERE `invoice_number` LIKE 'OB%' AND `invoice_number` NOT LIKE 'OBADV%'
  AND `is_deleted` = 0;


-- 2. No minus figure can reach the statement any more. Both must read 0.
SELECT 'visible opening balance invoices with a negative total' AS what, COUNT(*) AS n
FROM `tblpur_invoices`
WHERE `invoice_number` LIKE 'OB%' AND `total` < 0 AND `is_deleted` = 0
UNION ALL
SELECT 'advance payments with a negative amount', COUNT(*)
FROM `tblpur_invoice_payment` p
JOIN `tblpur_invoices` i ON i.`id` = p.`pur_invoice`
WHERE i.`invoice_number` LIKE 'OBADV%' AND p.`amount` < 0
UNION ALL
SELECT 'carriers with a non zero total', COUNT(*)
FROM `tblpur_invoices` WHERE `invoice_number` LIKE 'OBADV%' AND `total` <> 0;


-- 3. The money. Expect 31,734,010.94 owed, 3,403,728.46 in advances, and a net of
--    28,330,282.48 which is what the sheet says.
SELECT 'due for payment, visible invoices' AS what, ROUND(SUM(`total`), 2) AS total_aed
FROM `tblpur_invoices`
WHERE `invoice_number` LIKE 'OB%' AND `is_deleted` = 0
UNION ALL
SELECT 'advances, as payments', ROUND(SUM(p.`amount`), 2)
FROM `tblpur_invoice_payment` p
JOIN `tblpur_invoices` i ON i.`id` = p.`pur_invoice`
WHERE i.`invoice_number` LIKE 'OBADV%'
UNION ALL
SELECT 'net', (
    (SELECT COALESCE(SUM(`total`), 0) FROM `tblpur_invoices`
     WHERE `invoice_number` LIKE 'OB%' AND `is_deleted` = 0)
    -
    (SELECT COALESCE(SUM(p.`amount`), 0) FROM `tblpur_invoice_payment` p
     JOIN `tblpur_invoices` i ON i.`id` = p.`pur_invoice`
     WHERE i.`invoice_number` LIKE 'OBADV%')
);


-- 4. Vendor 67 BIZGROUP, the reported case. The advance must now read as a positive
--    511,043.07 in the Payments column, and the net must still be 18,366,428.73.
SELECT 'invoiced to vendor 67' AS what, ROUND(SUM(`total`), 2) AS amount_aed
FROM `tblpur_invoices`
WHERE `invoice_number` LIKE 'OB%' AND `vendor` = 67 AND `is_deleted` = 0
UNION ALL
SELECT 'paid for vendor 67', ROUND(SUM(p.`amount`), 2)
FROM `tblpur_invoice_payment` p
JOIN `tblpur_invoices` i ON i.`id` = p.`pur_invoice`
WHERE i.`invoice_number` LIKE 'OBADV%' AND i.`vendor` = 67
UNION ALL
SELECT 'net still owed by us', (
    (SELECT COALESCE(SUM(`total`), 0) FROM `tblpur_invoices`
     WHERE `invoice_number` LIKE 'OB%' AND `vendor` = 67 AND `is_deleted` = 0)
    -
    (SELECT COALESCE(SUM(p.`amount`), 0) FROM `tblpur_invoice_payment` p
     JOIN `tblpur_invoices` i ON i.`id` = p.`pur_invoice`
     WHERE i.`invoice_number` LIKE 'OBADV%' AND i.`vendor` = 67)
);


-- 5. Net position per vendor, exactly as the statement now presents it.
SELECT v.`userid` AS vendor_id,
       v.`company`,
       ROUND(COALESCE(inv.`due`, 0), 2)  AS due_for_payment,
       ROUND(COALESCE(pay.`paid`, 0), 2) AS advance_paid,
       ROUND(COALESCE(inv.`due`, 0) - COALESCE(pay.`paid`, 0), 2) AS net_owed_to_vendor
FROM `tblpur_vendor` v
LEFT JOIN (
    SELECT `vendor`, SUM(`total`) AS due
    FROM `tblpur_invoices`
    WHERE `invoice_number` LIKE 'OB%' AND `is_deleted` = 0
    GROUP BY `vendor`
) inv ON inv.`vendor` = v.`userid`
LEFT JOIN (
    SELECT i.`vendor`, SUM(p.`amount`) AS paid
    FROM `tblpur_invoice_payment` p
    JOIN `tblpur_invoices` i ON i.`id` = p.`pur_invoice`
    WHERE i.`invoice_number` LIKE 'OBADV%'
    GROUP BY i.`vendor`
) pay ON pay.`vendor` = v.`userid`
WHERE inv.`due` IS NOT NULL OR pay.`paid` IS NOT NULL
ORDER BY net_owed_to_vendor DESC;
