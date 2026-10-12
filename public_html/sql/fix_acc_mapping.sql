-- =====================================================
-- Fix: Add missing acc_mapping column to all tables
-- used by the Accounting module
-- Run in phpMyAdmin - ignore "Duplicate column" errors
-- =====================================================

ALTER TABLE `tblpur_orders` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblpur_invoices` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblpur_invoice_payment` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblwh_order_returns` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblwh_order_returns_refunds` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblinvoices` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblinvoicepaymentrecords` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblexpenses` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblgoods_delivery` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblgoods_receipt` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblwh_loss_adjustment` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblitems` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblmrp_manufacturing_orders` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblhrp_payslips` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblcart` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblomni_refunds` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblfe_assets` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblfe_asset_maintenances` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblfe_depreciation_items` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblcredits` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
ALTER TABLE `tblcreditnote_refunds` ADD COLUMN `acc_mapping` tinyint(1) NOT NULL DEFAULT '0';
