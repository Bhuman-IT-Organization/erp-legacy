-- ============================================================
-- TRUNCATE ALL PURCHASE PAYMENTS
-- Clears all payment records and resets invoice payment status
-- Run this in phpMyAdmin or MySQL CLI
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Clear all purchase invoice payments (hard delete all rows)
TRUNCATE TABLE `tblpur_invoice_payment`;

-- 2. Clear cheque management records linked to purchase payments
DELETE FROM `tblcheque_management`
WHERE `source_type` = 'pur_invoice_payment';

-- 3. Clear cheque transactions for those cheque records
--    (already gone via cascade or clean up orphans)
DELETE FROM `tblcheque_transactions`
WHERE `cheque_id` NOT IN (SELECT `id` FROM `tblcheque_management`);

-- 4. Reset all purchase invoice payment statuses back to 'unpaid'
UPDATE `tblpur_invoices`
SET `payment_status`   = 'unpaid',
    `transactionid`    = NULL,
    `transaction_date` = NULL;

-- 5. Clear approval details for payment_request type
DELETE FROM `tblpur_approval_details`
WHERE `rel_type` = 'payment_request';

-- 6. Remove uploaded cheque attachment files reference
--    (physical files in uploads/purchase/payment_cheque/ must be deleted manually)

SET FOREIGN_KEY_CHECKS = 1;

-- Done. All payments cleared. All invoices reset to unpaid.
SELECT 'Payments truncated successfully.' AS status;

