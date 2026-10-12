-- Fix Shipping and Discount account mapping for Purchase Invoices
-- Shipping: Dr Freight/Shipping Expense (P&L), Cr AP (Balance Sheet)
-- Discount: Dr AP (reduces payable in BS), Cr Purchase Discount Received (P&L income)

-- Shipping: deposit_to = debit account (expense), payment_account = credit account (AP)
UPDATE tbloptions SET value = '290' WHERE name = 'acc_pur_invoice_shipping_deposit_to';
-- 290 = 420042 - Freight Charges In/Shipping Exp (Expense - P&L)
UPDATE tbloptions SET value = '87' WHERE name = 'acc_pur_invoice_shipping_payment_account';
-- 87 = 2100 - Accounts Payable (Liability - Balance Sheet)

-- Discount: deposit_to = debit account (AP), payment_account = credit account (discount income)
UPDATE tbloptions SET value = '87' WHERE name = 'acc_pur_invoice_discount_deposit_to';
-- 87 = 2100 - Accounts Payable (reduces liability)
UPDATE tbloptions SET value = '209' WHERE name = 'acc_pur_invoice_discount_payment_account';
-- 209 = 310010 - Cash Discount Received (Other Income - P&L)

-- ============================================================
-- Example: Purchase Invoice with Inventory 50,000 + Shipping 500 - Discount 300
-- 
-- Balance Sheet:
--   Inventory (GR/IR Clearing)  Dr 50,000
--   Accounts Payable            Cr 50,700 (50,000 + 500 shipping + VAT - 300 discount)
--   (Net: 50,000 + 500 - 300 + VAT = total AP)
--
-- P&L:
--   Shipping Expense            Dr 500
--   Purchase Discount Received  Cr 300
--
-- Journal entries created:
--   Dr GR/IR Clearing    50,000  |  Cr AP             50,000  (inventory cost)
--   Dr Shipping Expense     500  |  Cr AP                500  (shipping)
--   Dr AP                   300  |  Cr Discount Recv     300  (discount reduces AP)
--   Dr VAT Input          2,500  |  Cr AP              2,500  (5% VAT on 50,000)
-- ============================================================
