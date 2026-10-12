-- =====================================================
-- CLEAN ALL TRANSACTION DATA (KEEP MASTER DATA)
-- WARNING: This will delete all transactions permanently!
-- =====================================================

-- Disable foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- Purchase Requests
TRUNCATE TABLE `tblpur_request`;
TRUNCATE TABLE `tblpur_request_detail`;

-- Purchase Orders
TRUNCATE TABLE `tblpur_orders`;
TRUNCATE TABLE `tblpur_order_detail`;

-- Purchase Invoices
TRUNCATE TABLE `tblpur_invoices`;
TRUNCATE TABLE `tblpur_invoice_details`;
TRUNCATE TABLE `tblpur_invoice_payment`;

-- Goods Receipt (Inventory Receiving)
TRUNCATE TABLE `tblgoods_receipt`;
TRUNCATE TABLE `tblgoods_receipt_detail`;
TRUNCATE TABLE `tblgoods_receipt_history`;
TRUNCATE TABLE `tblgoods_receipt_history_detail`;

-- Goods Delivery (Inventory Delivery)
TRUNCATE TABLE `tblgoods_delivery`;
TRUNCATE TABLE `tblgoods_delivery_detail`;

-- Inventory Stock Levels
TRUNCATE TABLE `tblinventory_manage`;

-- Warehouse Transactions
TRUNCATE TABLE `tblgoods_transaction_detail`;

-- Sales Invoices
TRUNCATE TABLE `tblinvoices`;
TRUNCATE TABLE `tblitemable`;
TRUNCATE TABLE `tblinvoicepaymentrecords`;
TRUNCATE TABLE `tblcreditnotes`;
TRUNCATE TABLE `tblcredits`;

-- Estimates / Sales Orders
TRUNCATE TABLE `tblestimates`;

-- Proposals
TRUNCATE TABLE `tblproposals`;

-- Expenses
TRUNCATE TABLE `tblexpenses`;

-- Accounting History
TRUNCATE TABLE `tblacc_account_history`;

-- Purchase Activity Logs
TRUNCATE TABLE `tblpur_activity_log`;

-- Warehouse Activity Logs
TRUNCATE TABLE `tblwh_activity_log`;

-- Purchase Approval Details
TRUNCATE TABLE `tblpur_approval_details`;

-- Purchase Comments
TRUNCATE TABLE `tblpur_comments`;

-- Order Returns
TRUNCATE TABLE `tblwh_order_returns`;
TRUNCATE TABLE `tblwh_order_return_details`;

-- Loss Adjustment
TRUNCATE TABLE `tblwh_loss_adjustment`;
TRUNCATE TABLE `tblwh_loss_adjustment_detail`;

-- Purchase Contracts
TRUNCATE TABLE `tblpur_contracts`;

-- Debit Notes
TRUNCATE TABLE `tblpur_debit_notes`;
TRUNCATE TABLE `tblpur_debits`;
TRUNCATE TABLE `tblpur_debits_refunds`;

-- Credit Notes (already truncated above, add credits/refunds)
TRUNCATE TABLE `tblcreditnote_refunds`;

-- Sales Returns
TRUNCATE TABLE `tblsales_returns`;
TRUNCATE TABLE `tblsales_return_items`;

-- Files/Attachments related to transactions (keep vendor master attachments)
DELETE FROM `tblfiles` WHERE rel_type IN ('pur_request', 'pur_order', 'goods_receipt', 'pur_invoice', 'expense', 'invoice', 'estimate', 'proposal', 'credit_note', 'debit_note');

-- Quality Control entries (linked to GRN/GDN)
TRUNCATE TABLE `tblquality_checks`;
TRUNCATE TABLE `tblquality_check_details`;
TRUNCATE TABLE `tblquality_activity`;
TRUNCATE TABLE `tblquality_alerts`;

-- Notes related to transactions
DELETE FROM `tblnotes` WHERE rel_type IN ('invoice', 'estimate', 'proposal', 'expense', 'pur_invoice', 'purchase_order');

-- Tags related to transactions
DELETE FROM `tbltaggables` WHERE rel_type IN ('invoice', 'estimate', 'proposal', 'expense', 'pur_order', 'pur_invoice');

-- Reminders related to transactions
DELETE FROM `tblreminders` WHERE rel_type IN ('invoice', 'estimate', 'proposal', 'expense', 'purchase_order', 'pur_invoice');

-- Reset auto-increment counters
ALTER TABLE `tblpur_request` AUTO_INCREMENT = 1;
ALTER TABLE `tblpur_orders` AUTO_INCREMENT = 1;
ALTER TABLE `tblpur_invoices` AUTO_INCREMENT = 1;
ALTER TABLE `tblgoods_receipt` AUTO_INCREMENT = 1;
ALTER TABLE `tblgoods_delivery` AUTO_INCREMENT = 1;
ALTER TABLE `tblinvoices` AUTO_INCREMENT = 1;
ALTER TABLE `tblestimates` AUTO_INCREMENT = 1;
ALTER TABLE `tblproposals` AUTO_INCREMENT = 1;
ALTER TABLE `tblexpenses` AUTO_INCREMENT = 1;

-- Reset next number settings
UPDATE `tblpurchase_option` SET `option_val` = '1' WHERE `option_name` = 'next_pr_number';
UPDATE `tblpurchase_option` SET `option_val` = '1' WHERE `option_name` = 'next_po_number';
UPDATE `tblpurchase_option` SET `option_val` = '1' WHERE `option_name` = 'next_pi_number';
UPDATE `tblwarehouse_option` SET `option_val` = '1' WHERE `option_name` = 'next_inventory_received_mumber';
UPDATE `tblwarehouse_option` SET `option_val` = '1' WHERE `option_name` = 'next_inventory_delivery_mumber';
UPDATE `tbloptions` SET `value` = '1' WHERE `name` = 'next_invoice_number';
UPDATE `tbloptions` SET `value` = '1' WHERE `name` = 'next_estimate_number';

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- DONE - All transaction data cleaned
-- Master data (staff, clients, vendors, items, warehouses, 
-- accounts, settings, permissions) is preserved
-- =====================================================
