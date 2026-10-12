-- =====================================================
-- STEP 1: First run this to check your database name
-- =====================================================
SELECT DATABASE() as current_database;

-- =====================================================
-- STEP 2: Check if tables exist (copy result here)
-- =====================================================
SHOW TABLES LIKE 'tblpur_invoices';
SHOW TABLES LIKE 'tblpur_orders';
SHOW TABLES LIKE 'tblexpenses';

-- =====================================================
-- STEP 3: Run EACH line below ONE BY ONE in phpMyAdmin
-- If you get "Duplicate column" error = column exists = OK
-- If you get "Query OK" = column was added = OK
-- =====================================================

-- 3a. tblpur_invoices
ALTER TABLE `tblpur_invoices` ADD COLUMN `is_deleted` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tblpur_invoices` ADD COLUMN `grn_id` text DEFAULT NULL;

-- 3b. tblpur_orders
ALTER TABLE `tblpur_orders` ADD COLUMN `is_deleted` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tblpur_orders` ADD COLUMN `ledger_account` int DEFAULT NULL;
ALTER TABLE `tblpur_orders` ADD COLUMN `cost_center` int DEFAULT NULL;

-- 3c. tblexpenses
ALTER TABLE `tblexpenses` ADD COLUMN `is_deleted` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `tblexpenses` ADD COLUMN `cheque_number` varchar(100) DEFAULT NULL;
ALTER TABLE `tblexpenses` ADD COLUMN `cheque_bank_name` varchar(200) DEFAULT NULL;
ALTER TABLE `tblexpenses` ADD COLUMN `cheque_date` date DEFAULT NULL;
ALTER TABLE `tblexpenses` ADD COLUMN `date_deleted` datetime DEFAULT NULL;

-- 3d. tblwh_order_returns
ALTER TABLE `tblwh_order_returns` ADD COLUMN `grn_id` text DEFAULT NULL;

-- 3e. tblpur_request_types
ALTER TABLE `tblpur_request_types` ADD COLUMN `credit_ledger` int DEFAULT NULL;

-- 3f. tblpur_debit_notes
ALTER TABLE `tblpur_debit_notes` ADD COLUMN `ledger_account` int DEFAULT NULL;

-- 3g. tblcreditnotes
ALTER TABLE `tblcreditnotes` ADD COLUMN `ledger_account` int DEFAULT NULL;

-- 3h. tblinvoices
ALTER TABLE `tblinvoices` ADD COLUMN `sales_order_id` int DEFAULT NULL;
ALTER TABLE `tblinvoices` ADD COLUMN `delivery_voucher_ids` text DEFAULT NULL;

-- 3i. tblpur_vendor
ALTER TABLE `tblpur_vendor` ADD COLUMN `ap_account` int DEFAULT NULL;

-- 3j. tblpur_invoice_payment
ALTER TABLE `tblpur_invoice_payment` ADD COLUMN `gl_account` int DEFAULT NULL;

-- =====================================================
-- STEP 4: Data updates (run all together)
-- =====================================================
UPDATE `tblpur_request_types` SET `credit_ledger` = 167 WHERE `credit_ledger` IS NULL OR `credit_ledger` = 0;
UPDATE `tblgoods_receipt` SET `buyer_id` = `addedfrom` WHERE `buyer_id` IS NULL AND `addedfrom` IS NOT NULL;
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_pur_order_payment_account', '167');
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_pur_order_deposit_to', '112');
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_pur_invoice_payment_account', '159');
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_wh_stock_import_deposit_to', '112');
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_wh_stock_import_payment_account', '167');

-- =====================================================
-- STEP 5: New tables (safe - IF NOT EXISTS)
-- =====================================================
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

-- =====================================================
-- STEP 6: Verify (run this LAST to confirm)
-- =====================================================
SELECT 'tblpur_invoices.is_deleted' as col, COUNT(*) as ok FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_invoices' AND COLUMN_NAME = 'is_deleted'
UNION ALL SELECT 'tblpur_invoices.grn_id', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_invoices' AND COLUMN_NAME = 'grn_id'
UNION ALL SELECT 'tblpur_orders.is_deleted', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_orders' AND COLUMN_NAME = 'is_deleted'
UNION ALL SELECT 'tblpur_orders.ledger_account', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_orders' AND COLUMN_NAME = 'ledger_account'
UNION ALL SELECT 'tblpur_orders.cost_center', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_orders' AND COLUMN_NAME = 'cost_center'
UNION ALL SELECT 'tblexpenses.is_deleted', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblexpenses' AND COLUMN_NAME = 'is_deleted'
UNION ALL SELECT 'tblwh_order_returns.grn_id', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblwh_order_returns' AND COLUMN_NAME = 'grn_id'
UNION ALL SELECT 'tblpur_request_types.credit_ledger', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_request_types' AND COLUMN_NAME = 'credit_ledger'
UNION ALL SELECT 'tblpur_debit_notes.ledger_account', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_debit_notes' AND COLUMN_NAME = 'ledger_account'
UNION ALL SELECT 'tblcreditnotes.ledger_account', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblcreditnotes' AND COLUMN_NAME = 'ledger_account'
UNION ALL SELECT 'tblinvoices.sales_order_id', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblinvoices' AND COLUMN_NAME = 'sales_order_id'
UNION ALL SELECT 'tblinvoices.delivery_voucher_ids', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblinvoices' AND COLUMN_NAME = 'delivery_voucher_ids'
UNION ALL SELECT 'tblpur_vendor.ap_account', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_vendor' AND COLUMN_NAME = 'ap_account'
UNION ALL SELECT 'tblpur_invoice_payment.gl_account', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblpur_invoice_payment' AND COLUMN_NAME = 'gl_account'
UNION ALL SELECT 'tblexpenses.cheque_number', COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblexpenses' AND COLUMN_NAME = 'cheque_number';
