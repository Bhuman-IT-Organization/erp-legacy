-- ============================================================
-- Configure Manufacturing & Quality Accounting Options
-- Run on both nextgen and inova databases
-- ============================================================

-- T8: Material Cost: Dr WIP (700000), Cr RM Inventory (150000)
UPDATE tbloptions SET value = (SELECT id FROM tblacc_accounts WHERE number = '700000' LIMIT 1) WHERE name = 'acc_mrp_material_cost_deposit_to';
UPDATE tbloptions SET value = (SELECT id FROM tblacc_accounts WHERE number = '150000' LIMIT 1) WHERE name = 'acc_mrp_material_cost_payment_account';

-- T9: Labour Cost: Dr WIP (700000), Cr Direct Labour (520000)
UPDATE tbloptions SET value = (SELECT id FROM tblacc_accounts WHERE number = '700000' LIMIT 1) WHERE name = 'acc_mrp_labour_cost_deposit_to';
UPDATE tbloptions SET value = (SELECT id FROM tblacc_accounts WHERE number = '520000' LIMIT 1) WHERE name = 'acc_mrp_labour_cost_payment_account';

-- T10: Overhead: Dr WIP (700000), Cr Factory Overheads (530000)
INSERT IGNORE INTO tbloptions (name, value) VALUES ('acc_mrp_overhead_cost_deposit_to', (SELECT id FROM tblacc_accounts WHERE number = '700000'));
INSERT IGNORE INTO tbloptions (name, value) VALUES ('acc_mrp_overhead_cost_payment_account', (SELECT id FROM tblacc_accounts WHERE number = '530000'));

-- T11: FG Receipt: Dr FG Inventory (150010), Cr WIP (700000)
INSERT IGNORE INTO tbloptions (name, value) VALUES ('acc_mrp_fg_receipt_deposit_to', (SELECT id FROM tblacc_accounts WHERE number = '150010'));
INSERT IGNORE INTO tbloptions (name, value) VALUES ('acc_mrp_fg_receipt_payment_account', (SELECT id FROM tblacc_accounts WHERE number = '700000'));

-- T12/T13: Quality Rejection Expense account
-- Already set via: acc_quality_rejection_expense_account

-- Enable manufacturing order automatic conversion
UPDATE tbloptions SET value = '1' WHERE name = 'acc_mrp_manufacturing_order_automatic_conversion';
