-- =====================================================
-- ADD ALL MISSING COLUMNS (Safe - skips if exists)
-- Run this on live server database
-- =====================================================

-- Helper procedure to add column only if not exists
DROP PROCEDURE IF EXISTS add_col_if_not_exists;
DELIMITER $$
CREATE PROCEDURE add_col_if_not_exists(IN tbl VARCHAR(100), IN col VARCHAR(100), IN col_def VARCHAR(500))
BEGIN
    SET @db_name = DATABASE();
    SET @col_exists = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = tbl AND COLUMN_NAME = col);
    IF @col_exists = 0 THEN
        SET @ddl = CONCAT('ALTER TABLE `', tbl, '` ADD COLUMN `', col, '` ', col_def);
        PREPARE stmt FROM @ddl;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- tblpur_invoices
CALL add_col_if_not_exists('tblpur_invoices', 'is_deleted', 'tinyint(1) NOT NULL DEFAULT 0');
CALL add_col_if_not_exists('tblpur_invoices', 'grn_id', 'text DEFAULT NULL');

-- tblpur_orders
CALL add_col_if_not_exists('tblpur_orders', 'is_deleted', 'tinyint(1) NOT NULL DEFAULT 0');
CALL add_col_if_not_exists('tblpur_orders', 'ledger_account', 'int(11) DEFAULT NULL');
CALL add_col_if_not_exists('tblpur_orders', 'cost_center', 'int(11) DEFAULT NULL');

-- tblexpenses
CALL add_col_if_not_exists('tblexpenses', 'is_deleted', 'tinyint(1) NOT NULL DEFAULT 0');
CALL add_col_if_not_exists('tblexpenses', 'cheque_number', 'varchar(100) DEFAULT NULL');
CALL add_col_if_not_exists('tblexpenses', 'cheque_bank_name', 'varchar(200) DEFAULT NULL');
CALL add_col_if_not_exists('tblexpenses', 'cheque_date', 'date DEFAULT NULL');
CALL add_col_if_not_exists('tblexpenses', 'date_deleted', 'datetime DEFAULT NULL');

-- tblwh_order_returns
CALL add_col_if_not_exists('tblwh_order_returns', 'grn_id', 'text DEFAULT NULL');

-- tblpur_request_types
CALL add_col_if_not_exists('tblpur_request_types', 'credit_ledger', 'int(11) DEFAULT NULL');

-- tblpur_debit_notes
CALL add_col_if_not_exists('tblpur_debit_notes', 'ledger_account', 'int(11) DEFAULT NULL');

-- tblcreditnotes
CALL add_col_if_not_exists('tblcreditnotes', 'ledger_account', 'int(11) DEFAULT NULL');

-- tblinvoices
CALL add_col_if_not_exists('tblinvoices', 'sales_order_id', 'int(11) DEFAULT NULL');
CALL add_col_if_not_exists('tblinvoices', 'delivery_voucher_ids', 'text DEFAULT NULL');

-- tblpur_vendor
CALL add_col_if_not_exists('tblpur_vendor', 'ap_account', 'int(11) DEFAULT NULL');

-- tblpur_invoice_payment
CALL add_col_if_not_exists('tblpur_invoice_payment', 'gl_account', 'int(11) DEFAULT NULL');

-- Cleanup procedure
DROP PROCEDURE IF EXISTS add_col_if_not_exists;

-- =====================================================
-- DATA UPDATES
-- =====================================================

-- Set credit_ledger to GR/IR Clearing (167) for all request types
UPDATE `tblpur_request_types` SET `credit_ledger` = 167 WHERE `credit_ledger` IS NULL OR `credit_ledger` = 0;

-- Fix GRN buyer_id NULL values
UPDATE `tblgoods_receipt` SET `buyer_id` = `addedfrom` WHERE `buyer_id` IS NULL AND `addedfrom` IS NOT NULL;

-- Accounting settings
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_pur_order_payment_account', '167');
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_pur_order_deposit_to', '112');
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_pur_invoice_payment_account', '159');
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_wh_stock_import_deposit_to', '112');
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_wh_stock_import_payment_account', '167');

-- =====================================================
-- NEW TABLES
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
-- DONE - Safe to run multiple times
-- =====================================================
