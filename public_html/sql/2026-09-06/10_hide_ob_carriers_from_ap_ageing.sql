-- ---------------------------------------------------------------------------
-- AP opening balances - step 10: keep the 0.00 carriers out of the AP ageing
-- ---------------------------------------------------------------------------
--
-- Run after 09. Optional but recommended. Nothing is deleted, one UPDATE only.
--
--
-- WHAT IT FIXES
--
-- The AP Ageing Detail report at
--     /admin/accounting/rp_accounts_payable_ageing_detail
-- selects purchase invoices with
--     payment_status != 'paid' AND approval_status = 2 AND is_deleted = 0
--
-- The 18 OBADV carrier invoices that file 09 creates were left as 'unpaid', so they
-- match that filter and show up as 18 rows of AED 0.00. They add nothing to the
-- total, they are just noise on the report.
--
-- Marking them 'paid' takes them out, and it is also the honest description: a
-- carrier has a total of 0.00 with payments recorded against it, so there is nothing
-- left to pay on it.
--
--
-- IT DOES NOT AFFECT THE VENDOR STATEMENT
--
-- Purchase_model::get_statement() selects invoices on vendor, invoice_date,
-- is_deleted and currency. It does not look at payment_status at all, so the carrier
-- row and its advance payments carry on showing exactly as they do now.
--
--
-- WHAT IT DOES NOT FIX - READ THIS
--
-- The report still leaves the 49 advances out altogether, so it overstates accounts
-- payable by 3,403,728.46:
--
--     report shows          31,872,477.27
--     of which OB payables  31,734,010.94
--     true OB net AP        28,330,282.48   (31,734,010.94 less 3,403,728.46)
--
-- That cannot be fixed from the data alone, and this file does not attempt it. The
-- reason is a direct conflict between the two screens:
--
--   - the vendor statement works out direction from the sign of the invoice total, so
--     an advance has to be a payment for it to appear as a positive under Payments
--   - the ageing report just lists invoice totals, so an advance has to be a live,
--     not-paid invoice with a NEGATIVE total for it to appear as a negative
--
-- One record cannot be both. Getting the advances to show as negatives on the ageing
-- report means either putting the minus back on the statement, or a small change to
-- Accounting_model::get_data_accounts_payable_ageing_detail() so it also reads the
-- advance payments. See the README.
-- ---------------------------------------------------------------------------

UPDATE `tblpur_invoices`
SET `payment_status` = 'paid'
WHERE `invoice_number` LIKE 'OBADV%'
  AND `total` = 0
  AND `payment_status` <> 'paid';


-- Expect 18 carriers, all 'paid', and 0 of them still visible to the ageing report.
SELECT COUNT(*) AS carriers, `payment_status`
FROM `tblpur_invoices`
WHERE `invoice_number` LIKE 'OBADV%'
GROUP BY `payment_status`;

SELECT COUNT(*) AS carriers_still_on_the_ageing_report
FROM `tblpur_invoices`
WHERE `invoice_number` LIKE 'OBADV%'
  AND `payment_status` <> 'paid'
  AND `approval_status` = 2
  AND `is_deleted` = 0;


-- The statement side must be untouched: still 18 carriers and 49 payments.
SELECT
    (SELECT COUNT(*) FROM `tblpur_invoices`
     WHERE `invoice_number` LIKE 'OBADV%' AND `is_deleted` = 0) AS carriers_on_statement,
    (SELECT COUNT(*) FROM `tblpur_invoice_payment` p
     JOIN `tblpur_invoices` i ON i.`id` = p.`pur_invoice`
     WHERE i.`invoice_number` LIKE 'OBADV%') AS advance_payments;
