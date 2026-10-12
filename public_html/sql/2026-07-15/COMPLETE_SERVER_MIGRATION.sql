-- =====================================================
-- COMPLETE SERVER MIGRATION SCRIPT
-- Single file - run with: mysql --force -u root -p nextgen < COMPLETE_SERVER_MIGRATION.sql
-- The --force flag skips errors (like "Duplicate column")
-- =====================================================

-- ─────────────────────────────────────────────────────
-- PART 1: ADD MISSING COLUMNS
-- (safe: "Duplicate column" errors are OK - means already exists)
-- ─────────────────────────────────────────────────────

ALTER TABLE `tblpur_invoices` ADD COLUMN `is_deleted` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tblpur_invoices` ADD COLUMN `grn_id` text DEFAULT NULL;
ALTER TABLE `tblpur_orders` ADD COLUMN `is_deleted` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tblpur_orders` ADD COLUMN `ledger_account` int DEFAULT NULL;
ALTER TABLE `tblpur_orders` ADD COLUMN `cost_center` int DEFAULT NULL;
ALTER TABLE `tblexpenses` ADD COLUMN `is_deleted` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tblexpenses` ADD COLUMN `cheque_number` varchar(100) DEFAULT NULL;
ALTER TABLE `tblexpenses` ADD COLUMN `cheque_bank_name` varchar(200) DEFAULT NULL;
ALTER TABLE `tblexpenses` ADD COLUMN `cheque_date` date DEFAULT NULL;
ALTER TABLE `tblexpenses` ADD COLUMN `date_deleted` datetime DEFAULT NULL;
ALTER TABLE `tblwh_order_returns` ADD COLUMN `grn_id` text DEFAULT NULL;
ALTER TABLE `tblpur_request_types` ADD COLUMN `credit_ledger` int DEFAULT NULL;
ALTER TABLE `tblpur_debit_notes` ADD COLUMN `ledger_account` int DEFAULT NULL;
ALTER TABLE `tblcreditnotes` ADD COLUMN `ledger_account` int DEFAULT NULL;
ALTER TABLE `tblinvoices` ADD COLUMN `sales_order_id` int DEFAULT NULL;
ALTER TABLE `tblinvoices` ADD COLUMN `delivery_voucher_ids` text DEFAULT NULL;
ALTER TABLE `tblpur_vendor` ADD COLUMN `ap_account` int DEFAULT NULL;
ALTER TABLE `tblpur_invoice_payment` ADD COLUMN `gl_account` int DEFAULT NULL;

-- ─────────────────────────────────────────────────────
-- PART 2: NEW TABLES
-- ─────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `tblsales_returns` (
  `id` int NOT NULL AUTO_INCREMENT,
  `estimate_id` int DEFAULT NULL,
  `clientid` int DEFAULT NULL,
  `gdv_ids` text DEFAULT NULL,
  `return_number` varchar(50) DEFAULT NULL,
  `return_date` date DEFAULT NULL,
  `subtotal` decimal(15,2) DEFAULT 0.00,
  `total_tax` decimal(15,2) DEFAULT 0.00,
  `total` decimal(15,2) DEFAULT 0.00,
  `note` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'draft',
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `created_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `tblsales_return_items` (
  `id` int NOT NULL AUTO_INCREMENT,
  `sales_return_id` int NOT NULL,
  `item_code` int DEFAULT NULL,
  `description` text DEFAULT NULL,
  `qty` decimal(15,2) DEFAULT 0.00,
  `unit_price` decimal(15,2) DEFAULT 0.00,
  `unit_id` int DEFAULT NULL,
  `tax_id` varchar(50) DEFAULT NULL,
  `tax_rate` varchar(50) DEFAULT NULL,
  `amount` decimal(15,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `sales_return_id` (`sales_return_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────
-- PART 3: FIX ALL ACCOUNTING SETTINGS
-- ─────────────────────────────────────────────────────

-- GRN Stock Import
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_wh_stock_import_deposit_to', '112');
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_wh_stock_import_payment_account', '167');
UPDATE `tbloptions` SET `value` = '112' WHERE `name` = 'acc_wh_stock_import_deposit_to';
UPDATE `tbloptions` SET `value` = '167' WHERE `name` = 'acc_wh_stock_import_payment_account';

-- Purchase Order
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_pur_order_payment_account', '167');
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_pur_order_deposit_to', '112');
UPDATE `tbloptions` SET `value` = '167' WHERE `name` = 'acc_pur_order_payment_account';
UPDATE `tbloptions` SET `value` = '112' WHERE `name` = 'acc_pur_order_deposit_to';

-- Purchase Invoice AP
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_pur_invoice_payment_account', '159');
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_invoice_payment_account';

-- Purchase Tax (VAT)
UPDATE `tbloptions` SET `value` = '124' WHERE `name` = 'acc_pur_tax_payment_account';
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_tax_deposit_to';

-- Purchase Invoice Discount: 201 (300300 Discount) / 159 (AP)
UPDATE `tbloptions` SET `value` = '201' WHERE `name` = 'acc_pur_invoice_discount_payment_account';
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_invoice_discount_deposit_to';

-- Purchase Invoice Shipping: 290 (420042 Freight) / 159 (AP)
UPDATE `tbloptions` SET `value` = '290' WHERE `name` = 'acc_pur_invoice_shipping_payment_account';
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_invoice_shipping_deposit_to';

-- Purchase Order Discount: 201 / 159
UPDATE `tbloptions` SET `value` = '201' WHERE `name` = 'acc_pur_order_discount_payment_account';
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_order_discount_deposit_to';

-- Purchase Order Shipping: 290 / 159
UPDATE `tbloptions` SET `value` = '290' WHERE `name` = 'acc_pur_order_shipping_payment_account';
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_order_shipping_deposit_to';

-- ─────────────────────────────────────────────────────
-- PART 4: TAX MAPPING
-- ─────────────────────────────────────────────────────

INSERT IGNORE INTO `tblacc_tax_mappings` (`id`, `tax_id`, `payment_account`, `deposit_to`, `expense_payment_account`, `expense_deposit_to`, `purchase_payment_account`, `purchase_deposit_to`) 
VALUES (1, 1, 123, 117, 124, 159, 124, 159);

UPDATE `tblacc_tax_mappings` SET 
  `payment_account` = 123, 
  `deposit_to` = 117, 
  `expense_payment_account` = 124, 
  `expense_deposit_to` = 159, 
  `purchase_payment_account` = 124, 
  `purchase_deposit_to` = 159 
WHERE `tax_id` = 1;

-- ─────────────────────────────────────────────────────
-- PART 5: DATA FIXES
-- ─────────────────────────────────────────────────────

-- PR Types: set GR/IR clearing account
UPDATE `tblpur_request_types` SET `credit_ledger` = 167 WHERE `credit_ledger` IS NULL OR `credit_ledger` = 0;

-- GRN buyer_id fix
UPDATE `tblgoods_receipt` SET `buyer_id` = `addedfrom` WHERE `buyer_id` IS NULL AND `addedfrom` IS NOT NULL;

-- ─────────────────────────────────────────────────────
-- PART 6: FIX WRONG ACCOUNTING ENTRIES (old accounts)
-- ─────────────────────────────────────────────────────

-- Delete all old purchase_order entries (will be regenerated by new code on GRN approval)
DELETE FROM `tblacc_account_history` WHERE `rel_type` = 'purchase_order';

-- Account 87 (old AP) -> 159 (active AP)
UPDATE `tblacc_account_history` SET `account` = 159 WHERE `account` = 87;
UPDATE `tblacc_account_history` SET `split` = 159 WHERE `split` = 87;

-- Account 29 (old VAT Payable) -> 124 (VAT Paid) for debits, 159 (AP) for credits
UPDATE `tblacc_account_history` SET `account` = 124 WHERE `account` = 29 AND `debit` > 0;
UPDATE `tblacc_account_history` SET `account` = 159 WHERE `account` = 29 AND `credit` > 0;
UPDATE `tblacc_account_history` SET `split` = 124 WHERE `split` = 29;

-- Account 80 (old Uncategorised) shipping debit -> 290 (Freight)
UPDATE `tblacc_account_history` SET `account` = 290 WHERE `account` = 80 AND `debit` > 0 AND `description` LIKE '%hipping%';
UPDATE `tblacc_account_history` SET `split` = 290 WHERE `split` = 80 AND `description` LIKE '%hipping%';
-- Account 80 credit -> 159 (AP)
UPDATE `tblacc_account_history` SET `account` = 159 WHERE `account` = 80 AND `credit` > 0;
UPDATE `tblacc_account_history` SET `split` = 159 WHERE `split` = 80 AND `credit` > 0;
-- Account 80 remaining debit (discount) -> 201 (Discount)
UPDATE `tblacc_account_history` SET `account` = 201 WHERE `account` = 80 AND `debit` > 0;
UPDATE `tblacc_account_history` SET `split` = 201 WHERE `split` = 80;

-- Account 13 (old Cash) -> 159 (AP) for credits
UPDATE `tblacc_account_history` SET `account` = 159 WHERE `account` = 13 AND `credit` > 0;
UPDATE `tblacc_account_history` SET `split` = 159 WHERE `split` = 13;

-- Account 19 (old) debit -> 201 (Discount)
UPDATE `tblacc_account_history` SET `account` = 201 WHERE `account` = 19 AND `debit` > 0;
UPDATE `tblacc_account_history` SET `split` = 201 WHERE `split` = 19;
-- Account 19 credit -> 159 (AP)
UPDATE `tblacc_account_history` SET `account` = 159 WHERE `account` = 19 AND `credit` > 0;

-- Account 37 (old Inventory) in stock_import -> keep as-is or map to type-specific
-- (These will be correct after regeneration via the PHP script)
UPDATE `tblacc_account_history` SET `split` = 167 WHERE `split` = 37;

-- ─────────────────────────────────────────────────────
-- PART 7: VERIFY
-- ─────────────────────────────────────────────────────

SELECT SUM(debit) as total_debit, SUM(credit) as total_credit, SUM(debit)-SUM(credit) as difference FROM tblacc_account_history;

SELECT 'MIGRATION COMPLETE' as status;
