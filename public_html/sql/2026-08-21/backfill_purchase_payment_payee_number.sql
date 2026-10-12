-- ============================================================
-- Account register: purchase_payment rows had no payee / number
--
-- automatic_purchase_payment_conversion() wrote the vendor name into
-- description but never populated acc_account_history.vendor or .number,
-- so the "Payee" and "Number" columns of the user register were blank for
-- every purchase payment line. Other document types (purchase_order,
-- purchase_invoice, stock_import) always populated both.
--
-- The code is fixed going forward. This backfills the rows already posted.
--
-- vendor  <- the vendor on the invoice the payment belongs to
-- number  <- cheque number when paid by cheque, otherwise PP-#####
-- ============================================================

UPDATE `tblacc_account_history` h
JOIN `tblpur_invoice_payment` p ON p.`id` = h.`rel_id`
JOIN `tblpur_invoices` i        ON i.`id` = p.`pur_invoice`
SET h.`vendor` = i.`vendor`,
    h.`number` = CASE
                     WHEN p.`cheque_number` IS NOT NULL AND p.`cheque_number` <> ''
                         THEN p.`cheque_number`
                     ELSE CONCAT('PP-', LPAD(p.`id`, 5, '0'))
                 END
WHERE h.`rel_type` = 'purchase_payment'
  AND (
        h.`vendor` IS NULL OR h.`vendor` = 0
        OR h.`number` IS NULL OR h.`number` = ''
      );
