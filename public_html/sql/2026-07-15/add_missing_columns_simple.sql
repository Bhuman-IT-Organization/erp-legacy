-- =====================================================
-- ADD ALL MISSING COLUMNS - Simple version
-- If column already exists, MySQL will show error but continue
-- Run each statement one by one in phpMyAdmin if needed
-- =====================================================

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

-- Data updates
UPDATE `tblpur_request_types` SET `credit_ledger` = 167 WHERE `credit_ledger` IS NULL OR `credit_ledger` = 0;
UPDATE `tblgoods_receipt` SET `buyer_id` = `addedfrom` WHERE `buyer_id` IS NULL AND `addedfrom` IS NOT NULL;

-- Settings
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_pur_order_payment_account', '167');
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_pur_order_deposit_to', '112');
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_pur_invoice_payment_account', '159');
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_wh_stock_import_deposit_to', '112');
INSERT IGNORE INTO `tbloptions` (`name`, `value`) VALUES ('acc_wh_stock_import_payment_account', '167');

-- Fix old tax settings (replace inactive accounts 87/29 with active 124/159)
UPDATE `tbloptions` SET `value` = '124' WHERE `name` = 'acc_pur_tax_payment_account';
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_tax_deposit_to';

-- Tax mapping (VAT 5% - tax_id=1): Dr 124 (VAT Paid), Cr 159 (Vendor AP) for purchases
INSERT IGNORE INTO `tblacc_tax_mappings` (`id`, `tax_id`, `payment_account`, `deposit_to`, `expense_payment_account`, `expense_deposit_to`, `purchase_payment_account`, `purchase_deposit_to`) VALUES (1, 1, 123, 117, 124, 159, 124, 159);

-- New tables
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
