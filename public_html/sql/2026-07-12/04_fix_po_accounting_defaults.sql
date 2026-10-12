-- Fix PO accounting default accounts (replace old inactive 4-digit accounts)
-- payment_account = credit side (GR/IR Clearing 167 as fallback)
-- deposit_to = debit side (Inventory Raw Materials 112 as fallback)
-- These are only used when no purchase type mapping overrides them

UPDATE tbloptions SET value = '167' WHERE name = 'acc_pur_order_payment_account';
UPDATE tbloptions SET value = '112' WHERE name = 'acc_pur_order_deposit_to';
