-- VAT account mapping
-- 123 = 179010 - VAT Received (Sales VAT - liability)
-- 124 = 179020 - VAT Paid (Purchase VAT - asset)
-- 117 = 170000 - A/R Trade receivable
-- 159 = 230000 - A/Payable Stock Vendors - Local

UPDATE tblacc_tax_mappings SET 
  payment_account = 123,
  deposit_to = 117,
  purchase_payment_account = 124,
  purchase_deposit_to = 159,
  expense_payment_account = 124,
  expense_deposit_to = 159
WHERE tax_id = 1;
