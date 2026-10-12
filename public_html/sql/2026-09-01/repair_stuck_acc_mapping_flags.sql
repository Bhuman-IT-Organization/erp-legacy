-- ---------------------------------------------------------------------------
-- Clear acc_mapping on documents that claim to be posted but have no GL entries.
--
-- acc_mapping = 1 means "this document has been converted to a ledger entry".
-- The accounting module SKIPS any document already flagged, so a document whose
-- GL entries have gone missing while the flag stayed set is silently stranded:
-- it will never be posted again and never appear in the ledger, with no warning.
--
-- Clearing the flag does NOT create any ledger entry. It only makes the document
-- eligible for posting again, so it can be converted from the accounting module
-- in the normal way.
--
-- REVERSIBLE. Step 2 records every id whose flag is cleared.
-- RE-RUNNABLE. Once clear, the UPDATE matches nothing.
--
--   mysql -u root nextgen_live -e "source sql/2026-09-01/repair_stuck_acc_mapping_flags.sql"
-- ---------------------------------------------------------------------------

-- ── 1. Preview ────────────────────────────────────────────────────────────

SELECT '--- Purchase invoices flagged posted with no GL entries ---' AS step;

SELECT  i.`id`, i.`invoice_number`, i.`total`, i.`date_add`
FROM    `tblpur_invoices` i
LEFT JOIN (
    SELECT `rel_id` FROM `tblacc_account_history`
    WHERE `rel_type` = 'purchase_invoice' GROUP BY `rel_id`
) ah ON ah.`rel_id` = i.`id`
WHERE   i.`is_deleted` = 0 AND i.`acc_mapping` = 1 AND ah.`rel_id` IS NULL
ORDER BY i.`id`;

SELECT '--- Goods receipts flagged posted with no GL entries ---' AS step;

-- date_add, not datecreated: tblgoods_receipt has date_c / date_add / expiry_date
SELECT  gr.`id`, gr.`goods_receipt_code`, gr.`date_add`
FROM    `tblgoods_receipt` gr
LEFT JOIN (
    SELECT `rel_id` FROM `tblacc_account_history`
    WHERE `rel_type` = 'stock_import' GROUP BY `rel_id`
) ah ON ah.`rel_id` = gr.`id`
WHERE   gr.`is_deleted` = 0 AND gr.`acc_mapping` = 1 AND ah.`rel_id` IS NULL
ORDER BY gr.`id`;

SELECT '--- Purchase payments flagged posted with no GL entries ---' AS step;

SELECT  p.`id`, p.`pur_invoice`, p.`amount`, p.`date`
FROM    `tblpur_invoice_payment` p
LEFT JOIN (
    SELECT `rel_id` FROM `tblacc_account_history`
    WHERE `rel_type` = 'purchase_payment' GROUP BY `rel_id`
) ah ON ah.`rel_id` = p.`id`
WHERE   p.`is_deleted` = 0 AND p.`acc_mapping` = 1 AND ah.`rel_id` IS NULL
ORDER BY p.`id`;

-- ── 2. Record what is about to change ─────────────────────────────────────

CREATE TABLE IF NOT EXISTS `tblacc_mapping_reset_log_20260901` (
  `doc_type`  VARCHAR(40) NOT NULL,
  `doc_id`    INT(11) NOT NULL,
  `reset_at`  DATETIME NULL,
  PRIMARY KEY (`doc_type`, `doc_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tblacc_mapping_reset_log_20260901` (`doc_type`, `doc_id`, `reset_at`)
SELECT 'purchase_invoice', i.`id`, NOW()
FROM   `tblpur_invoices` i
LEFT JOIN (SELECT `rel_id` FROM `tblacc_account_history` WHERE `rel_type`='purchase_invoice' GROUP BY `rel_id`) ah
       ON ah.`rel_id` = i.`id`
WHERE  i.`is_deleted` = 0 AND i.`acc_mapping` = 1 AND ah.`rel_id` IS NULL;

INSERT IGNORE INTO `tblacc_mapping_reset_log_20260901` (`doc_type`, `doc_id`, `reset_at`)
SELECT 'stock_import', gr.`id`, NOW()
FROM   `tblgoods_receipt` gr
LEFT JOIN (SELECT `rel_id` FROM `tblacc_account_history` WHERE `rel_type`='stock_import' GROUP BY `rel_id`) ah
       ON ah.`rel_id` = gr.`id`
WHERE  gr.`is_deleted` = 0 AND gr.`acc_mapping` = 1 AND ah.`rel_id` IS NULL;

INSERT IGNORE INTO `tblacc_mapping_reset_log_20260901` (`doc_type`, `doc_id`, `reset_at`)
SELECT 'purchase_payment', p.`id`, NOW()
FROM   `tblpur_invoice_payment` p
LEFT JOIN (SELECT `rel_id` FROM `tblacc_account_history` WHERE `rel_type`='purchase_payment' GROUP BY `rel_id`) ah
       ON ah.`rel_id` = p.`id`
WHERE  p.`is_deleted` = 0 AND p.`acc_mapping` = 1 AND ah.`rel_id` IS NULL;

-- ── 3. Clear the flags ────────────────────────────────────────────────────

UPDATE `tblpur_invoices` i
LEFT JOIN (SELECT `rel_id` FROM `tblacc_account_history` WHERE `rel_type`='purchase_invoice' GROUP BY `rel_id`) ah
       ON ah.`rel_id` = i.`id`
SET    i.`acc_mapping` = 0
WHERE  i.`is_deleted` = 0 AND i.`acc_mapping` = 1 AND ah.`rel_id` IS NULL;

UPDATE `tblgoods_receipt` gr
LEFT JOIN (SELECT `rel_id` FROM `tblacc_account_history` WHERE `rel_type`='stock_import' GROUP BY `rel_id`) ah
       ON ah.`rel_id` = gr.`id`
SET    gr.`acc_mapping` = 0
WHERE  gr.`is_deleted` = 0 AND gr.`acc_mapping` = 1 AND ah.`rel_id` IS NULL;

UPDATE `tblpur_invoice_payment` p
LEFT JOIN (SELECT `rel_id` FROM `tblacc_account_history` WHERE `rel_type`='purchase_payment' GROUP BY `rel_id`) ah
       ON ah.`rel_id` = p.`id`
SET    p.`acc_mapping` = 0
WHERE  p.`is_deleted` = 0 AND p.`acc_mapping` = 1 AND ah.`rel_id` IS NULL;

-- ── 4. Verify ─────────────────────────────────────────────────────────────

SELECT '--- After repair: all three should be 0 ---' AS step;

SELECT
 (SELECT COUNT(*) FROM `tblpur_invoices` i
   LEFT JOIN (SELECT `rel_id` FROM `tblacc_account_history` WHERE `rel_type`='purchase_invoice' GROUP BY `rel_id`) a ON a.`rel_id`=i.`id`
  WHERE i.`is_deleted`=0 AND i.`acc_mapping`=1 AND a.`rel_id` IS NULL) AS invoices_still_stuck,
 (SELECT COUNT(*) FROM `tblgoods_receipt` gr
   LEFT JOIN (SELECT `rel_id` FROM `tblacc_account_history` WHERE `rel_type`='stock_import' GROUP BY `rel_id`) a ON a.`rel_id`=gr.`id`
  WHERE gr.`is_deleted`=0 AND gr.`acc_mapping`=1 AND a.`rel_id` IS NULL) AS grns_still_stuck,
 (SELECT COUNT(*) FROM `tblpur_invoice_payment` p
   LEFT JOIN (SELECT `rel_id` FROM `tblacc_account_history` WHERE `rel_type`='purchase_payment' GROUP BY `rel_id`) a ON a.`rel_id`=p.`id`
  WHERE p.`is_deleted`=0 AND p.`acc_mapping`=1 AND a.`rel_id` IS NULL) AS payments_still_stuck;

SELECT '--- Flags cleared by this run ---' AS step;
SELECT `doc_type`, COUNT(*) AS flags_cleared
FROM   `tblacc_mapping_reset_log_20260901`
GROUP BY `doc_type`;

-- ---------------------------------------------------------------------------
-- ROLLBACK, if ever needed:
--
--   UPDATE tblpur_invoices SET acc_mapping = 1 WHERE id IN
--     (SELECT doc_id FROM tblacc_mapping_reset_log_20260901 WHERE doc_type='purchase_invoice');
--   UPDATE tblgoods_receipt SET acc_mapping = 1 WHERE id IN
--     (SELECT doc_id FROM tblacc_mapping_reset_log_20260901 WHERE doc_type='stock_import');
--   UPDATE tblpur_invoice_payment SET acc_mapping = 1 WHERE id IN
--     (SELECT doc_id FROM tblacc_mapping_reset_log_20260901 WHERE doc_type='purchase_payment');
-- ---------------------------------------------------------------------------
