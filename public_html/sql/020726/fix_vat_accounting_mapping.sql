-- ============================================================
-- Fix VAT Accounting Mapping
-- GRN: No VAT (already correct)
-- Invoice: Dr GR/IR Clearing + Dr VAT Input, Cr Vendor AP
-- ============================================================

-- Fix global purchase tax settings
-- Dr: VAT Input Receivable (1410)
UPDATE tbloptions SET value = (SELECT id FROM tblacc_accounts WHERE number = '1410' LIMIT 1) WHERE name = 'acc_pur_tax_payment_account';
-- Cr: Accounts Payable (2100)  
UPDATE tbloptions SET value = (SELECT id FROM tblacc_accounts WHERE number = '2100' LIMIT 1) WHERE name = 'acc_pur_tax_deposit_to';

-- Create tax mapping for VAT (tax_id = 1, 5%)
-- Sales: Dr VAT Output Payable, Cr VAT Input
-- Purchase/Expense: Dr VAT Input Receivable, Cr Accounts Payable
INSERT INTO tblacc_tax_mappings (tax_id, payment_account, deposit_to, expense_payment_account, expense_deposit_to, purchase_payment_account, purchase_deposit_to) 
SELECT 1, 
  (SELECT id FROM tblacc_accounts WHERE number = '2400' LIMIT 1),
  (SELECT id FROM tblacc_accounts WHERE number = '1410' LIMIT 1),
  (SELECT id FROM tblacc_accounts WHERE number = '1410' LIMIT 1),
  (SELECT id FROM tblacc_accounts WHERE number = '2100' LIMIT 1),
  (SELECT id FROM tblacc_accounts WHERE number = '1410' LIMIT 1),
  (SELECT id FROM tblacc_accounts WHERE number = '2100' LIMIT 1)
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_tax_mappings WHERE tax_id = 1);
