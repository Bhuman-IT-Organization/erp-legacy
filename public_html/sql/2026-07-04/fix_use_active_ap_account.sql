-- ============================================================
-- Fix: Use active AP account (230000) instead of inactive (2100)
-- Account 87 (2100 - Accounts Payable) has active=0, not showing in balance sheet
-- Using account 159 (230000 - A/Payable Stock Vendors - Local) which is active
-- ============================================================

-- Update existing accounting entries
UPDATE tblacc_account_history SET account = 159 WHERE account = 87;
UPDATE tblacc_account_history SET split = 159 WHERE split = 87;

-- Update payment mode mappings
UPDATE tblacc_payment_mode_mappings SET expense_payment_account = 159 WHERE expense_payment_account = 87;

-- Update tax mapping
UPDATE tblacc_tax_mappings SET purchase_deposit_to = 159, expense_deposit_to = 159 WHERE purchase_deposit_to = 87;

-- Update all options that reference account 87
UPDATE tbloptions SET value = '159' WHERE value = '87' AND name IN (
  'acc_pur_invoice_payment_account',
  'acc_pur_tax_deposit_to',
  'acc_pur_invoice_shipping_payment_account',
  'acc_pur_invoice_discount_deposit_to',
  'acc_pur_payment_payment_account',
  'acc_pur_order_return_deposit_to',
  'acc_pur_order_return_fee_payment_account',
  'acc_pur_order_return_discount_deposit_to'
);
