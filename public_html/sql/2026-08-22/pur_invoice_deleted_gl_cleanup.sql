-- ============================================================
-- Deleted purchase invoices still appearing in the GL
--
-- Two problems:
--
-- 1. Purchase_model::delete_pur_invoice() writes `date_deleted`, but that column
--    was never added to tblpur_invoices. The UPDATE therefore failed on an
--    unknown column and `is_deleted` was never set. With db_debug off (which is
--    the case in production) the failure is silent and execution carries on, so
--    the invoice stayed active while its accounting entries were removed.
--    The code now guards the optional column, but the column should exist.
--
-- 2. Invoices reversed before the accounting-reversal code existed still have
--    their rows in tblacc_account_history, so a deleted invoice keeps showing in
--    the General Ledger. INV00025 (invoice 5) is one such case: is_deleted = 1
--    with 4 GL rows totalling 1,343.50 on each side.
-- ============================================================

-- ── 1. Add the missing soft-delete timestamp ────────────────
-- Guarded so the whole file stays re-runnable: a plain ALTER aborts the
-- script on an install where the column already exists, which would skip
-- the cleanup steps below.
SET @add_date_deleted = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `tblpur_invoices` ADD COLUMN `date_deleted` DATETIME NULL DEFAULT NULL AFTER `is_deleted`',
    'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME   = 'tblpur_invoices'
    AND COLUMN_NAME  = 'date_deleted'
);
PREPARE stmt FROM @add_date_deleted;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ── 2. Remove GL entries belonging to deleted invoices ──────
-- Safe to re-run: only matches rows whose invoice is flagged deleted.
DELETE h
FROM `tblacc_account_history` h
JOIN `tblpur_invoices` i
  ON i.`id` = h.`rel_id`
WHERE h.`rel_type` = 'purchase_invoice'
  AND i.`is_deleted` = 1;

-- ── 3. Clear the converted flag on those invoices ────────────
UPDATE `tblpur_invoices`
SET `acc_mapping` = 0
WHERE `is_deleted` = 1
  AND `acc_mapping` <> 0;

-- ── 4. Same for payments belonging to deleted invoices ───────
-- A reversed invoice's payments are soft-deleted too, so their journal entries
-- must not remain in the ledger either.
DELETE h
FROM `tblacc_account_history` h
JOIN `tblpur_invoice_payment` p
  ON p.`id` = h.`rel_id`
JOIN `tblpur_invoices` i
  ON i.`id` = p.`pur_invoice`
WHERE h.`rel_type` = 'purchase_payment'
  AND (p.`is_deleted` = 1 OR i.`is_deleted` = 1);

UPDATE `tblpur_invoice_payment` p
JOIN `tblpur_invoices` i ON i.`id` = p.`pur_invoice`
SET p.`acc_mapping` = 0
WHERE (p.`is_deleted` = 1 OR i.`is_deleted` = 1)
  AND p.`acc_mapping` <> 0;
