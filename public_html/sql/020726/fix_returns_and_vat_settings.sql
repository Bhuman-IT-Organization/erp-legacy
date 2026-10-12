-- ============================================================
-- Fix Sales Return, Purchase Return, and VAT Settings
-- ============================================================

-- Purchase Return: Dr AP (2100), Cr Inventory RM (150000)
UPDATE tbloptions SET value = (SELECT id FROM tblacc_accounts WHERE number = '2100' LIMIT 1) WHERE name = 'acc_pur_order_return_payment_account';
UPDATE tbloptions SET value = (SELECT id FROM tblacc_accounts WHERE number = '150000' LIMIT 1) WHERE name = 'acc_pur_order_return_deposit_to';

-- Sales Return (Credit Note): Dr Sales Returns (400100), Cr AR (1100)
UPDATE tbloptions SET value = (SELECT id FROM tblacc_accounts WHERE number = '400100' LIMIT 1) WHERE name = 'acc_credit_note_payment_account';
UPDATE tbloptions SET value = (SELECT id FROM tblacc_accounts WHERE number = '1100' LIMIT 1) WHERE name = 'acc_credit_note_deposit_to';

-- Sales Return Inventory: Dr FG Inventory (150010), Cr COGS (5000)
UPDATE tbloptions SET value = '1' WHERE name = 'acc_wh_stock_import_return_automatic_conversion';
UPDATE tbloptions SET value = (SELECT id FROM tblacc_accounts WHERE number = '150010' LIMIT 1) WHERE name = 'acc_wh_stock_import_return_deposit_to';
UPDATE tbloptions SET value = (SELECT id FROM tblacc_accounts WHERE number = '5000' LIMIT 1) WHERE name = 'acc_wh_stock_import_return_payment_account';

-- Ensure all return automatic conversions are enabled
UPDATE tbloptions SET value = '1' WHERE name = 'acc_pur_order_return_automatic_conversion';
UPDATE tbloptions SET value = '1' WHERE name = 'acc_credit_note_automatic_conversion';
