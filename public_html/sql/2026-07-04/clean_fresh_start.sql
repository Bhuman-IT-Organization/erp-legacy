-- ============================================================
-- CLEAN ALL TRANSACTION DATA FOR FRESH TESTING
-- Removes ALL transactions while preserving master data
-- ============================================================
-- WARNING: This is IRREVERSIBLE. Take a backup before running!
-- Run: mysqldump -u root nextgen > nextgen_backup_YYYYMMDD.sql
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. ACCOUNTING MODULE - All transaction history
-- ============================================================
TRUNCATE TABLE tblacc_account_history;
TRUNCATE TABLE tblacc_journal_entries;
TRUNCATE TABLE tblacc_transfers;
TRUNCATE TABLE tblacc_transaction_bankings;
TRUNCATE TABLE tblacc_bank_reconciles;
TRUNCATE TABLE tblacc_reconciles;
TRUNCATE TABLE tblacc_matched_transactions;
TRUNCATE TABLE tblacc_checks;
TRUNCATE TABLE tblacc_check_details;
TRUNCATE TABLE tblacc_checks_printed;
TRUNCATE TABLE tblacc_print_later;
TRUNCATE TABLE tblacc_pay_bills;
TRUNCATE TABLE tblacc_pay_bill_details;
TRUNCATE TABLE tblacc_pay_bill_item_paid;
TRUNCATE TABLE tblacc_plaid_transaction_logs;

-- ============================================================
-- 2. CHEQUE MANAGEMENT
-- ============================================================
TRUNCATE TABLE tblcheque_management;
TRUNCATE TABLE tblcheque_transactions;

-- ============================================================
-- 3. PURCHASE REQUESTS
-- ============================================================
TRUNCATE TABLE tblpur_request;
TRUNCATE TABLE tblpur_request_detail;
TRUNCATE TABLE tblpur_approval_details;

-- ============================================================
-- 4. PURCHASE ORDERS
-- ============================================================
TRUNCATE TABLE tblpur_orders;
TRUNCATE TABLE tblpur_order_detail;
TRUNCATE TABLE tblpur_order_payment;

-- ============================================================
-- 5. PURCHASE INVOICES & PAYMENTS
-- ============================================================
TRUNCATE TABLE tblpur_invoices;
TRUNCATE TABLE tblpur_invoice_details;
TRUNCATE TABLE tblpur_invoice_payment;

-- ============================================================
-- 6. PURCHASE DEBIT NOTES & REFUNDS
-- ============================================================
TRUNCATE TABLE tblpur_debit_notes;
TRUNCATE TABLE tblpur_debits;
TRUNCATE TABLE tblpur_debits_refunds;

-- ============================================================
-- 7. PURCHASE RETURN ORDERS
-- ============================================================
TRUNCATE TABLE tblwh_order_returns;
TRUNCATE TABLE tblwh_order_return_details;

-- ============================================================
-- 8. GOODS RECEIPT (GRN)
-- ============================================================
TRUNCATE TABLE tblgoods_receipt;
TRUNCATE TABLE tblgoods_receipt_detail;
TRUNCATE TABLE tblgoods_receipt_history;
TRUNCATE TABLE tblgoods_receipt_history_detail;
TRUNCATE TABLE tblgoods_transaction_detail;

-- ============================================================
-- 9. GOODS DELIVERY
-- ============================================================
TRUNCATE TABLE tblgoods_delivery;
TRUNCATE TABLE tblgoods_delivery_detail;
TRUNCATE TABLE tblgoods_delivery_batch;
TRUNCATE TABLE tblgoods_delivery_invoices_pr_orders;
TRUNCATE TABLE tblwh_goods_delivery_activity_log;

-- ============================================================
-- 10. INVENTORY STOCK
-- ============================================================
TRUNCATE TABLE tblinventory_manage;

-- ============================================================
-- 11. WAREHOUSE APPROVALS
-- ============================================================
TRUNCATE TABLE tblwh_approval_details;

-- ============================================================
-- 12. SALES INVOICES & PAYMENTS
-- ============================================================
TRUNCATE TABLE tblinvoices;
TRUNCATE TABLE tblinvoicepaymentrecords;
TRUNCATE TABLE tblitemable;
TRUNCATE TABLE tblitem_tax;

-- ============================================================
-- 13. CREDIT NOTES
-- ============================================================
TRUNCATE TABLE tblcreditnotes;
TRUNCATE TABLE tblcreditnote_refunds;

-- ============================================================
-- 14. ESTIMATES & PROPOSALS
-- ============================================================
TRUNCATE TABLE tblestimates;
TRUNCATE TABLE tblproposals;

-- ============================================================
-- 15. EXPENSES
-- ============================================================
TRUNCATE TABLE tblexpenses;

-- ============================================================
-- 16. ACTIVITY LOGS (purchase related)
-- ============================================================
DELETE FROM tblpur_activity_log WHERE 1=1;

-- ============================================================
-- 17. RESET AUTO-INCREMENT COUNTERS
-- ============================================================

-- Accounting
ALTER TABLE tblacc_account_history AUTO_INCREMENT = 1;
ALTER TABLE tblacc_journal_entries AUTO_INCREMENT = 1;
ALTER TABLE tblacc_transfers AUTO_INCREMENT = 1;
ALTER TABLE tblcheque_management AUTO_INCREMENT = 1;

-- Purchase
ALTER TABLE tblpur_request AUTO_INCREMENT = 1;
ALTER TABLE tblpur_request_detail AUTO_INCREMENT = 1;
ALTER TABLE tblpur_orders AUTO_INCREMENT = 1;
ALTER TABLE tblpur_order_detail AUTO_INCREMENT = 1;
ALTER TABLE tblpur_order_payment AUTO_INCREMENT = 1;
ALTER TABLE tblpur_invoices AUTO_INCREMENT = 1;
ALTER TABLE tblpur_invoice_details AUTO_INCREMENT = 1;
ALTER TABLE tblpur_invoice_payment AUTO_INCREMENT = 1;
ALTER TABLE tblpur_debit_notes AUTO_INCREMENT = 1;
ALTER TABLE tblpur_approval_details AUTO_INCREMENT = 1;

-- Warehouse
ALTER TABLE tblgoods_receipt AUTO_INCREMENT = 1;
ALTER TABLE tblgoods_receipt_detail AUTO_INCREMENT = 1;
ALTER TABLE tblgoods_delivery AUTO_INCREMENT = 1;
ALTER TABLE tblgoods_delivery_detail AUTO_INCREMENT = 1;
ALTER TABLE tblinventory_manage AUTO_INCREMENT = 1;
ALTER TABLE tblgoods_transaction_detail AUTO_INCREMENT = 1;
ALTER TABLE tblwh_approval_details AUTO_INCREMENT = 1;

-- Sales
ALTER TABLE tblinvoices AUTO_INCREMENT = 1;
ALTER TABLE tblinvoicepaymentrecords AUTO_INCREMENT = 1;
ALTER TABLE tblitemable AUTO_INCREMENT = 1;
ALTER TABLE tblitem_tax AUTO_INCREMENT = 1;
ALTER TABLE tblcreditnotes AUTO_INCREMENT = 1;
ALTER TABLE tblestimates AUTO_INCREMENT = 1;
ALTER TABLE tblproposals AUTO_INCREMENT = 1;

-- Expenses
ALTER TABLE tblexpenses AUTO_INCREMENT = 1;

-- ============================================================
-- 18. RESET NUMBERING SEQUENCES
-- ============================================================
UPDATE tbloptions SET value = '1' WHERE name = 'next_invoice_number';
UPDATE tbloptions SET value = '1' WHERE name = 'next_estimate_number';
UPDATE tbloptions SET value = '1' WHERE name = 'next_credit_note_number';
UPDATE tbloptions SET value = '1' WHERE name = 'next_proposal_number';

UPDATE tblpurchase_option SET option_val = '1' WHERE option_name = 'next_pr_number';
UPDATE tblpurchase_option SET option_val = '1' WHERE option_name = 'next_po_number';
UPDATE tblpurchase_option SET option_val = '1' WHERE option_name = 'next_inv_number';
UPDATE tblpurchase_option SET option_val = '1' WHERE option_name = 'next_return_number';

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- VERIFICATION: Run after cleanup to confirm clean state
-- ============================================================
-- SELECT 'Accounting entries' as item, COUNT(*) as count FROM tblacc_account_history
-- UNION ALL SELECT 'Purchase requests', COUNT(*) FROM tblpur_request
-- UNION ALL SELECT 'Purchase orders', COUNT(*) FROM tblpur_orders
-- UNION ALL SELECT 'Purchase invoices', COUNT(*) FROM tblpur_invoices
-- UNION ALL SELECT 'GRN', COUNT(*) FROM tblgoods_receipt
-- UNION ALL SELECT 'Goods delivery', COUNT(*) FROM tblgoods_delivery
-- UNION ALL SELECT 'Inventory', COUNT(*) FROM tblinventory_manage
-- UNION ALL SELECT 'Sales invoices', COUNT(*) FROM tblinvoices
-- UNION ALL SELECT 'Payments (sales)', COUNT(*) FROM tblinvoicepaymentrecords
-- UNION ALL SELECT 'Expenses', COUNT(*) FROM tblexpenses
-- UNION ALL SELECT 'Estimates', COUNT(*) FROM tblestimates;

-- ============================================================
-- PRESERVED (NOT DELETED):
-- ============================================================
-- - Chart of Accounts (tblacc_accounts)
-- - Account Types (tblacc_account_type_details)
-- - Tax Mappings (tblacc_tax_mappings)
-- - Payment Mode Mappings (tblacc_payment_mode_mappings)
-- - Items / Products (tblitems)
-- - Vendors (tblpur_vendor, tblpur_contacts)
-- - Customers (tblclients, tblcontacts)
-- - Purchase Request Types (tblpur_request_types)
-- - Approval Settings (tblpur_approval_setting, tblwh_approval_setting)
-- - Warehouses (tblwarehouse)
-- - Payment Modes (tblpayment_modes)
-- - Taxes (tbltaxes)
-- - Currencies (tblcurrencies)
-- - Staff (tblstaff)
-- - All system settings (tbloptions, tblpurchase_option)
-- ============================================================
