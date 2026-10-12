-- =====================================================
-- LIVE SERVER MIGRATION SCRIPT (MySQL 8.0 compatible)
-- Run this on the live database BEFORE uploading code
-- =====================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ─────────────────────────────────────────────────────
-- 1. NEW TABLES
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
-- 2. ADD MISSING COLUMNS (safe - ignores if exists)
-- ─────────────────────────────────────────────────────

-- Purchase Invoices
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblpur_invoices` ADD COLUMN `is_deleted` tinyint(1) NOT NULL DEFAULT 0', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_invoices' AND COLUMN_NAME = 'is_deleted');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblpur_invoices` ADD COLUMN `grn_id` text DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_invoices' AND COLUMN_NAME = 'grn_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Purchase Orders
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblpur_orders` ADD COLUMN `is_deleted` tinyint(1) NOT NULL DEFAULT 0', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_orders' AND COLUMN_NAME = 'is_deleted');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblpur_orders` ADD COLUMN `ledger_account` int DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_orders' AND COLUMN_NAME = 'ledger_account');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblpur_orders` ADD COLUMN `cost_center` int DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_orders' AND COLUMN_NAME = 'cost_center');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Expenses
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblexpenses` ADD COLUMN `is_deleted` tinyint(1) NOT NULL DEFAULT 0', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblexpenses' AND COLUMN_NAME = 'is_deleted');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Order Returns - grn_id
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblwh_order_returns` ADD COLUMN `grn_id` text DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblwh_order_returns' AND COLUMN_NAME = 'grn_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Purchase Request Types - credit_ledger
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblpur_request_types` ADD COLUMN `credit_ledger` int DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_request_types' AND COLUMN_NAME = 'credit_ledger');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Purchase Debit Notes - ledger_account
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblpur_debit_notes` ADD COLUMN `ledger_account` int DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_debit_notes' AND COLUMN_NAME = 'ledger_account');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Vendor - ap_account (vendor-specific AP account)
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblpur_vendor` ADD COLUMN `ap_account` int DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_vendor' AND COLUMN_NAME = 'ap_account');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Purchase Invoice Payment - gl_account
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblpur_invoice_payment` ADD COLUMN `gl_account` int DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_invoice_payment' AND COLUMN_NAME = 'gl_account');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Expenses - cheque fields and date_deleted
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblexpenses` ADD COLUMN `cheque_number` varchar(100) DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblexpenses' AND COLUMN_NAME = 'cheque_number');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblexpenses` ADD COLUMN `cheque_bank_name` varchar(200) DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblexpenses' AND COLUMN_NAME = 'cheque_bank_name');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblexpenses` ADD COLUMN `cheque_date` date DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblexpenses' AND COLUMN_NAME = 'cheque_date');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblexpenses` ADD COLUMN `date_deleted` datetime DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblexpenses' AND COLUMN_NAME = 'date_deleted');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Credit Notes - ledger_account
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblcreditnotes` ADD COLUMN `ledger_account` int DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblcreditnotes' AND COLUMN_NAME = 'ledger_account');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Sales Invoices - sales_order_id, delivery_voucher_ids
SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblinvoices` ADD COLUMN `sales_order_id` int DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblinvoices' AND COLUMN_NAME = 'sales_order_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE `tblinvoices` ADD COLUMN `delivery_voucher_ids` text DEFAULT NULL', 'SELECT 1') FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblinvoices' AND COLUMN_NAME = 'delivery_voucher_ids');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ─────────────────────────────────────────────────────
-- 3. ACCOUNTING SETTINGS
-- ─────────────────────────────────────────────────────

-- PO accounting: GR/IR Clearing (167) as credit side
INSERT INTO `tbloptions` (`name`, `value`) SELECT 'acc_pur_order_payment_account', '167' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tbloptions` WHERE `name` = 'acc_pur_order_payment_account');
UPDATE `tbloptions` SET `value` = '167' WHERE `name` = 'acc_pur_order_payment_account';

-- PO accounting: Inventory (112) as debit side (fallback)
INSERT INTO `tbloptions` (`name`, `value`) SELECT 'acc_pur_order_deposit_to', '112' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tbloptions` WHERE `name` = 'acc_pur_order_deposit_to');
UPDATE `tbloptions` SET `value` = '112' WHERE `name` = 'acc_pur_order_deposit_to';

-- Invoice accounting: Vendor AP (159) 
INSERT INTO `tbloptions` (`name`, `value`) SELECT 'acc_pur_invoice_payment_account', '159' FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `tbloptions` WHERE `name` = 'acc_pur_invoice_payment_account');
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_invoice_payment_account';

-- ─────────────────────────────────────────────────────
-- 4. DATA UPDATES
-- ─────────────────────────────────────────────────────

-- Set credit_ledger to GR/IR Clearing (167) for all request types
UPDATE `tblpur_request_types` SET `credit_ledger` = 167 WHERE `credit_ledger` IS NULL OR `credit_ledger` = 0;

-- Fix GRN buyer_id NULL values (use addedfrom/creator)
UPDATE `tblgoods_receipt` SET `buyer_id` = `addedfrom` WHERE `buyer_id` IS NULL AND `addedfrom` IS NOT NULL;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- MIGRATION COMPLETE
-- 
-- After running this SQL:
-- 1. Upload all modified PHP files to the server
-- 2. Clear any PHP opcode cache (if applicable)
-- 3. Test: Create a GRN, approve it, create invoice
-- =====================================================
