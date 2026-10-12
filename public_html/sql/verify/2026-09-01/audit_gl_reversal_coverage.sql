-- ---------------------------------------------------------------------------
-- Audit: are GL entries reversed when their source document is deleted?
--
-- For every document type that posts to tblacc_account_history, counts the GL
-- lines whose source document is either soft-deleted (is_deleted = 1) or gone
-- from the table entirely. Any non-zero figure is money sitting on the books
-- with nothing behind it.
--
-- Read-only. Safe to re-run.
--
--   mysql -u root nextgen_live -e "source sql/2026-09-01/audit_gl_reversal_coverage.sql"
-- ---------------------------------------------------------------------------

SELECT '--- All document types currently posting to the GL ---' AS step;

SELECT  rel_type,
        COUNT(*)                    AS gl_lines,
        COUNT(DISTINCT rel_id)      AS documents,
        ROUND(SUM(debit), 2)        AS total_debit,
        ROUND(SUM(credit), 2)       AS total_credit
FROM    `tblacc_account_history`
GROUP BY rel_type
ORDER BY gl_lines DESC;

SELECT '--- Goods Receipt (GRN): GL left behind after delete/revert ---' AS step;

SELECT  COUNT(*)              AS gl_lines_on_deleted_grns,
        ROUND(SUM(ah.debit),2) AS debit_still_posted
FROM    `tblacc_account_history` ah
LEFT JOIN `tblgoods_receipt` gr ON gr.`id` = ah.`rel_id`
WHERE   ah.`rel_type` = 'stock_import'
  AND   (gr.`id` IS NULL OR gr.`is_deleted` = 1);

SELECT '--- Purchase Invoice: GL left behind after delete ---' AS step;

SELECT  COUNT(*)              AS gl_lines_on_deleted_invoices,
        ROUND(SUM(ah.debit),2) AS debit_still_posted
FROM    `tblacc_account_history` ah
LEFT JOIN `tblpur_invoices` i ON i.`id` = ah.`rel_id`
WHERE   ah.`rel_type` = 'purchase_invoice'
  AND   (i.`id` IS NULL OR i.`is_deleted` = 1);

SELECT '--- Purchase Payment: GL left behind after delete ---' AS step;

SELECT  COUNT(*)              AS gl_lines_on_deleted_payments,
        ROUND(SUM(ah.credit),2) AS credit_still_posted
FROM    `tblacc_account_history` ah
LEFT JOIN `tblpur_invoice_payment` p ON p.`id` = ah.`rel_id`
WHERE   ah.`rel_type` = 'purchase_payment'
  AND   (p.`id` IS NULL OR p.`is_deleted` = 1);

SELECT '--- Shipping Charge: GL left behind after reverse ---' AS step;

SELECT  COUNT(*)              AS gl_lines_on_deleted_charges,
        ROUND(SUM(ah.debit),2) AS debit_still_posted
FROM    `tblacc_account_history` ah
LEFT JOIN `tblinv_shipping_charges` sc ON sc.`id` = ah.`rel_id`
WHERE   ah.`rel_type` = 'inv_shipping_charge'
  AND   sc.`id` IS NULL;

SELECT '--- Expense: GL left behind after delete ---' AS step;

SELECT  COUNT(*)              AS gl_lines_on_missing_expenses,
        ROUND(SUM(ah.debit),2) AS debit_still_posted
FROM    `tblacc_account_history` ah
LEFT JOIN `tblexpenses` e ON e.`id` = ah.`rel_id`
WHERE   ah.`rel_type` = 'expense'
  AND   e.`id` IS NULL;

SELECT '--- Reverse of the above: documents posted but NOT reflected as mapped ---' AS step;
-- acc_mapping is the flag the accounting module clears on reversal. A document
-- with live GL lines but acc_mapping = 0 (or the reverse) means the flag and the
-- ledger have drifted apart.

SELECT  'stock_import' AS doc_type,
        SUM(CASE WHEN gr.`acc_mapping` = 1 AND ah.`n` IS NULL     THEN 1 ELSE 0 END) AS mapped_but_no_gl,
        SUM(CASE WHEN gr.`acc_mapping` <> 1 AND ah.`n` IS NOT NULL THEN 1 ELSE 0 END) AS gl_but_not_mapped
FROM    `tblgoods_receipt` gr
LEFT JOIN (
    SELECT `rel_id`, COUNT(*) AS n FROM `tblacc_account_history`
    WHERE `rel_type` = 'stock_import' GROUP BY `rel_id`
) ah ON ah.`rel_id` = gr.`id`
WHERE   gr.`is_deleted` = 0;

SELECT  'purchase_invoice' AS doc_type,
        SUM(CASE WHEN i.`acc_mapping` = 1 AND ah.`n` IS NULL     THEN 1 ELSE 0 END) AS mapped_but_no_gl,
        SUM(CASE WHEN i.`acc_mapping` <> 1 AND ah.`n` IS NOT NULL THEN 1 ELSE 0 END) AS gl_but_not_mapped
FROM    `tblpur_invoices` i
LEFT JOIN (
    SELECT `rel_id`, COUNT(*) AS n FROM `tblacc_account_history`
    WHERE `rel_type` = 'purchase_invoice' GROUP BY `rel_id`
) ah ON ah.`rel_id` = i.`id`
WHERE   i.`is_deleted` = 0;

SELECT  'purchase_payment' AS doc_type,
        SUM(CASE WHEN p.`acc_mapping` = 1 AND ah.`n` IS NULL     THEN 1 ELSE 0 END) AS mapped_but_no_gl,
        SUM(CASE WHEN p.`acc_mapping` <> 1 AND ah.`n` IS NOT NULL THEN 1 ELSE 0 END) AS gl_but_not_mapped
FROM    `tblpur_invoice_payment` p
LEFT JOIN (
    SELECT `rel_id`, COUNT(*) AS n FROM `tblacc_account_history`
    WHERE `rel_type` = 'purchase_payment' GROUP BY `rel_id`
) ah ON ah.`rel_id` = p.`id`
WHERE   p.`is_deleted` = 0;
