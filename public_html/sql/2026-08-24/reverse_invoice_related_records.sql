-- ============================================================
-- Reversing a purchase invoice must reverse everything it created
--
-- delete_pur_invoice() removed the invoice journal, its payments and its
-- shipping charges, but two things it created were left behind:
--
--   1. Cheque register entries. A cheque payment writes a row to
--      tblcheque_management. Nothing cancelled it when the payment or the
--      invoice was reversed, so the cheque stayed on the register as live
--      and outgoing. In this database cheques 2 and 3 point at purchase
--      payments that no longer exist at all - hard deleted by a build that
--      predates soft delete - and both were still sitting at status 1.
--
--   2. Applied debit notes. apply_debits() posts an AP debit and a credit to
--      the note's ledger account. delete_applied_debit() only removed the
--      tblpur_debits row, so those two journal lines stayed in the GL, and
--      reversing an invoice never un-applied the note at all.
--
-- The code now handles both going forward. This file cleans up what is
-- already in the database. Safe to re-run: every step matches only rows
-- that still need changing.
-- ============================================================

-- ── 1. Cancel cheques whose source payment or invoice is gone ─
-- Cleared (4) and Bounced (5) are left alone: the money already moved at the
-- bank, so those stay on the register as they stand. Already Cancelled (6) is
-- skipped, which is what makes this re-runnable.
DROP TEMPORARY TABLE IF EXISTS tmp_cheques_to_cancel;

CREATE TEMPORARY TABLE tmp_cheques_to_cancel AS
SELECT c.`id`, c.`status` AS from_status
FROM `tblcheque_management` c
LEFT JOIN `tblpur_invoice_payment` p ON p.`id` = c.`source_id`
LEFT JOIN `tblpur_invoices`       i ON i.`id` = p.`pur_invoice`
WHERE c.`source_type` = 'pur_invoice_payment'
  AND c.`status` NOT IN (4, 5, 6)
  AND (p.`id` IS NULL OR p.`is_deleted` = 1 OR i.`is_deleted` = 1);

-- Audit trail first, so from_status is still the pre-cancel value
INSERT INTO `tblcheque_transactions`
  (`cheque_id`, `action`, `from_status`, `to_status`, `action_date`, `user_id`, `notes`)
SELECT `id`, 'cancelled', from_status, 6, NOW(), NULL,
       'Cancelled by cleanup: source purchase payment or invoice was reversed'
FROM tmp_cheques_to_cancel;

UPDATE `tblcheque_management`
SET `status` = 6,
    `updated_at` = NOW()
WHERE `id` IN (SELECT `id` FROM tmp_cheques_to_cancel);

DROP TEMPORARY TABLE IF EXISTS tmp_cheques_to_cancel;

-- ── 2. Un-apply debit notes sitting on a reversed invoice ─────
-- Remove the journal entries first, while the application row is still there
-- to identify them.
DELETE h
FROM `tblacc_account_history` h
JOIN `tblpur_debits`    d ON d.`id` = h.`rel_id`
JOIN `tblpur_invoices`  i ON i.`id` = d.`invoice_id`
WHERE h.`rel_type` = 'debit_note_applied'
  AND i.`is_deleted` = 1;

DELETE d
FROM `tblpur_debits` d
JOIN `tblpur_invoices` i ON i.`id` = d.`invoice_id`
WHERE i.`is_deleted` = 1;

-- ── 3. Drop debit_note_applied rows with no application left ──
-- Left over from the old behaviour, where removing an applied debit deleted
-- the tblpur_debits row but never its journal.
DELETE h
FROM `tblacc_account_history` h
LEFT JOIN `tblpur_debits` d ON d.`id` = h.`rel_id`
WHERE h.`rel_type` = 'debit_note_applied'
  AND d.`id` IS NULL;

-- ── 4. Re-derive debit note status after un-applying ──────────
-- 2 = fully used, 1 = open. Mirrors update_debit_note_status().
UPDATE `tblpur_debit_notes` n
SET n.`status` = 1
WHERE n.`status` <> 1
  AND COALESCE((SELECT SUM(d.`amount`) FROM `tblpur_debits` d WHERE d.`debit_id` = n.`id`), 0)
    + COALESCE((SELECT SUM(r.`amount`) FROM `tblpur_debits_refunds` r WHERE r.`debit_note_id` = n.`id`), 0)
    < n.`total`;
