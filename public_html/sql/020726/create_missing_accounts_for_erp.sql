-- ============================================================
-- Create Missing Chart of Accounts for Full ERP Accounting
-- Run on both nextgen and inova databases
-- ============================================================

-- Production / WIP Accounts
INSERT INTO tblacc_accounts (number, name, account_type_id, account_detail_type_id, active) 
SELECT '700000', 'Work in Progress - WIP', 2, 3, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE number = '700000');

INSERT INTO tblacc_accounts (number, name, account_type_id, account_detail_type_id, active) 
SELECT '700010', 'Production Variance', 14, 24, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE number = '700010');

-- Quality Rejection Expense
INSERT INTO tblacc_accounts (number, name, account_type_id, account_detail_type_id, active) 
SELECT '610000', 'Quality Rejection Expense', 14, 24, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE number = '610000');

-- Inventory Adjustment Accounts
INSERT INTO tblacc_accounts (number, name, account_type_id, account_detail_type_id, active) 
SELECT '410050', 'Inventory Adjustment Income', 12, 18, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE number = '410050');

INSERT INTO tblacc_accounts (number, name, account_type_id, account_detail_type_id, active) 
SELECT '610010', 'Inventory Adjustment Loss', 14, 24, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE number = '610010');

-- Advance Payment Accounts
INSERT INTO tblacc_accounts (number, name, account_type_id, account_detail_type_id, active) 
SELECT '130000', 'Advances to Suppliers', 2, 3, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE number = '130000');

INSERT INTO tblacc_accounts (number, name, account_type_id, account_detail_type_id, active) 
SELECT '230100', 'Advances from Customers', 8, 12, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE number = '230100');

-- Direct Labour Account
INSERT INTO tblacc_accounts (number, name, account_type_id, account_detail_type_id, active) 
SELECT '520000', 'Direct Labour', 13, 20, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE number = '520000');

-- Factory Overheads
INSERT INTO tblacc_accounts (number, name, account_type_id, account_detail_type_id, active) 
SELECT '530000', 'Factory Overheads', 13, 20, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE number = '530000');

-- Finished Goods Inventory
INSERT INTO tblacc_accounts (number, name, account_type_id, account_detail_type_id, active) 
SELECT '150010', 'Inventory - Finished Goods', 2, 3, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE number = '150010');

-- Accrued Expenses
INSERT INTO tblacc_accounts (number, name, account_type_id, account_detail_type_id, active) 
SELECT '230200', 'Accrued Expenses Payable', 8, 12, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE number = '230200');

-- Sales Return
INSERT INTO tblacc_accounts (number, name, account_type_id, account_detail_type_id, active) 
SELECT '400100', 'Sales Returns and Allowances', 11, 17, 1 FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE number = '400100');

-- ============================================================
-- Add ERP accounting options
-- ============================================================
INSERT IGNORE INTO tbloptions (name, value) SELECT 'acc_wip_account', id FROM tblacc_accounts WHERE number = '700000';
INSERT IGNORE INTO tbloptions (name, value) SELECT 'acc_production_variance_account', id FROM tblacc_accounts WHERE number = '700010';
INSERT IGNORE INTO tbloptions (name, value) SELECT 'acc_quality_rejection_expense_account', id FROM tblacc_accounts WHERE number = '610000';
INSERT IGNORE INTO tbloptions (name, value) SELECT 'acc_inventory_adjustment_income_account', id FROM tblacc_accounts WHERE number = '410050';
INSERT IGNORE INTO tbloptions (name, value) SELECT 'acc_inventory_adjustment_loss_account', id FROM tblacc_accounts WHERE number = '610010';
INSERT IGNORE INTO tbloptions (name, value) SELECT 'acc_advance_to_suppliers_account', id FROM tblacc_accounts WHERE number = '130000';
INSERT IGNORE INTO tbloptions (name, value) SELECT 'acc_advance_from_customers_account', id FROM tblacc_accounts WHERE number = '230100';
INSERT IGNORE INTO tbloptions (name, value) SELECT 'acc_finished_goods_inventory_account', id FROM tblacc_accounts WHERE number = '150010';
