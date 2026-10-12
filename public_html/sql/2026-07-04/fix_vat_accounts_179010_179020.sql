-- ============================================================
-- Use existing VAT accounts for all postings:
-- 179010 - VAT Received (id=123) — Sales VAT (liability, what we owe government)
-- 179020 - VAT Paid (id=124) — Purchase/Expense VAT (asset, what we can recover)
-- ============================================================

-- Tax mapping for VAT (tax_id = 1)
UPDATE tblacc_tax_mappings SET 
  payment_account = 123,        -- Sales: Cr VAT Received (179010)
  deposit_to = 1,               -- Sales: Dr AR (1100)
  purchase_payment_account = 124, -- Purchase: Dr VAT Paid (179020)
  purchase_deposit_to = 87,       -- Purchase: Cr AP (2100)
  expense_payment_account = 124,  -- Expense: Dr VAT Paid (179020)
  expense_deposit_to = 87          -- Expense: Cr AP (2100)
WHERE tax_id = 1;

-- Global settings
UPDATE tbloptions SET value = '124' WHERE name = 'acc_pur_tax_payment_account';
UPDATE tbloptions SET value = '87' WHERE name = 'acc_pur_tax_deposit_to';
UPDATE tbloptions SET value = '123' WHERE name = 'acc_tax_payment_account';
UPDATE tbloptions SET value = '1' WHERE name = 'acc_tax_deposit_to';

-- Migrate existing entries from old accounts to new
UPDATE tblacc_account_history SET account = 124 WHERE account = 356;
UPDATE tblacc_account_history SET split = 124 WHERE split = 356;
UPDATE tblacc_account_history SET account = 123 WHERE account = 29;
UPDATE tblacc_account_history SET split = 123 WHERE split = 29;

-- ============================================================
-- RESULT:
-- Sales Invoice VAT:    Dr AR (1100), Cr VAT Received (179010)
-- Purchase Invoice VAT: Dr VAT Paid (179020), Cr AP (2100)
-- Expense VAT:          Dr VAT Paid (179020), Cr Bank/Cash
-- ============================================================
