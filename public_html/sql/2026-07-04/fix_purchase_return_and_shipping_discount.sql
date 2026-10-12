-- ============================================================
-- Fix Purchase Return Order accounting settings
-- Purchase Return: Dr AP (reduce payable), Cr Inventory (goods returned)
-- ============================================================

-- Main accounts: deposit_to = DEBIT, payment_account = CREDIT
UPDATE tbloptions SET value = '87' WHERE name = 'acc_pur_order_return_deposit_to';
-- 87 = 2100 - Accounts Payable (Dr - reduces what we owe vendor)
UPDATE tbloptions SET value = '112' WHERE name = 'acc_pur_order_return_payment_account';
-- 112 = 150000 - Inventory Raw Materials (Cr - goods returned reduce stock)

-- Return order discount: Dr AP (reduces payable), Cr Discount Income
UPDATE tbloptions SET value = '87' WHERE name = 'acc_pur_order_return_discount_deposit_to';
UPDATE tbloptions SET value = '209' WHERE name = 'acc_pur_order_return_discount_payment_account';
-- 209 = 310010 - Cash Discount Received

-- Return order fee/shipping: Dr Shipping Expense, Cr AP
UPDATE tbloptions SET value = '290' WHERE name = 'acc_pur_order_return_fee_deposit_to';
UPDATE tbloptions SET value = '87' WHERE name = 'acc_pur_order_return_fee_payment_account';
-- 290 = 420042 - Freight Charges In/Shipping Exp

-- ============================================================
-- Purchase Invoice Shipping & Discount (already updated - confirming)
-- Shipping: Dr Freight Expense (P&L), Cr AP (Balance Sheet)
-- Discount: Dr AP (reduces BS liability), Cr Discount Income (P&L)
-- ============================================================

UPDATE tbloptions SET value = '290' WHERE name = 'acc_pur_invoice_shipping_deposit_to';
UPDATE tbloptions SET value = '87' WHERE name = 'acc_pur_invoice_shipping_payment_account';
UPDATE tbloptions SET value = '87' WHERE name = 'acc_pur_invoice_discount_deposit_to';
UPDATE tbloptions SET value = '209' WHERE name = 'acc_pur_invoice_discount_payment_account';
