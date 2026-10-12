-- ============================================================
-- Vendor advance payments (payment against a PO before GRN/invoice)
--
-- Before this, paying an approved PO that had no GRN and no invoice went through
-- Purchase_model::add_payment_on_po(), which FABRICATED a full purchase invoice
-- from the PO and paid that. The invoice never came from the vendor, it bypassed
-- the GRN, and it inflated A/P. Purchase order payments also posted nothing at
-- all to the ledger - there was no listener on after_pur_order_payment_added,
-- which is why no 'purchase_order_payment' rows exist in tblacc_account_history.
--
-- The payment is now held as an advance:
--     Dr  Advances to Suppliers   (asset)
--     Cr  Bank / Cash             (the GL account chosen on the payment)
--
-- and when the real invoice arrives the advance is adjusted against it:
--     Dr  Accounts Payable
--     Cr  Advances to Suppliers
--
-- Safe to re-run: every step is guarded.
-- ============================================================

-- ── 1. Advances to Suppliers control account ─────────────────
-- account_type_id 2 / account_detail_type_id 10 matches the existing 1800xx
-- current-asset advance accounts (180000 Advances for Local Contractors).
INSERT INTO `tblacc_accounts` (`name`, `number`, `account_type_id`, `account_detail_type_id`, `active`)
SELECT 'Advances to Suppliers', '180010', 2, 10, 1
FROM (SELECT 1) t
WHERE NOT EXISTS (
  SELECT 1 FROM `tblacc_accounts` WHERE `number` = '180010'
);

-- ── 2. Make it configurable, like every other purchase mapping ─
INSERT INTO `tbloptions` (`name`, `value`, `autoload`)
SELECT 'acc_pur_advance_account',
       (SELECT `id` FROM `tblacc_accounts` WHERE `number` = '180010' LIMIT 1),
       1
FROM (SELECT 1) t
WHERE NOT EXISTS (
  SELECT 1 FROM `tbloptions` WHERE `name` = 'acc_pur_advance_account'
);

-- ── 3. Conversion + soft delete state on PO payments ─────────
SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `tblpur_order_payment` ADD COLUMN `acc_mapping` TINYINT(1) NOT NULL DEFAULT 0',
    'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_order_payment' AND COLUMN_NAME = 'acc_mapping'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `tblpur_order_payment` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0',
    'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_order_payment' AND COLUMN_NAME = 'is_deleted'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `tblpur_order_payment` ADD COLUMN `vendor` INT(11) NULL DEFAULT NULL',
    'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_order_payment' AND COLUMN_NAME = 'vendor'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Backfill vendor from the parent PO so the ledger can show a payee
UPDATE `tblpur_order_payment` p
JOIN `tblpur_orders` o ON o.`id` = p.`pur_order`
SET p.`vendor` = o.`vendor`
WHERE p.`vendor` IS NULL;

-- ── 4. How much of each advance has been adjusted, and where ──
CREATE TABLE IF NOT EXISTS `tblpur_advance_allocations` (
  `id`             INT(11) NOT NULL AUTO_INCREMENT,
  `po_payment_id`  INT(11) NOT NULL COMMENT 'tblpur_order_payment.id',
  `invoice_id`     INT(11) NOT NULL COMMENT 'tblpur_invoices.id',
  `amount`         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `date`           DATE NULL DEFAULT NULL,
  `date_applied`   DATETIME NULL DEFAULT NULL,
  `staff_id`       INT(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_po_payment` (`po_payment_id`),
  KEY `idx_invoice`    (`invoice_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
