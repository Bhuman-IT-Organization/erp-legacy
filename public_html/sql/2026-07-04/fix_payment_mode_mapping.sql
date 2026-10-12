-- ============================================================
-- FIX 1: Payment mode mapping for customer receipts
-- The payment_account and deposit_to were swapped causing:
-- Customer receipt to Dr AR, Cr Bank instead of correct Dr Bank, Cr AR
-- This caused AR to show double amount and Cash In Hand not reducing
-- ============================================================

-- Payment mode 1 (Bank Transfer): Debit NBF Bank (134), Credit AR (1)
UPDATE tblacc_payment_mode_mappings 
SET payment_account = 1, deposit_to = 134 
WHERE id = 7;

-- Payment mode 2 (Cash): Debit Cash In Hand (133), Credit AR (1)
UPDATE tblacc_payment_mode_mappings 
SET payment_account = 1, deposit_to = 133 
WHERE id = 8;

-- Payment mode 3 (PDC): Debit PDC account (166), Credit AR (1)
UPDATE tblacc_payment_mode_mappings 
SET payment_account = 1, deposit_to = 166 
WHERE id = 9;

-- Fix existing wrong payment entry (payment id 1 was Dr AR, Cr Cash - now corrected to Dr Cash, Cr AR)
UPDATE tblacc_account_history 
SET account = 133, split = 1, debit = 4000.00, credit = 0.00 
WHERE id = 21;

UPDATE tblacc_account_history 
SET account = 1, split = 133, debit = 0.00, credit = 4000.00 
WHERE id = 22;

-- ============================================================
-- FIX 2: Stock Export (Goods Delivery) should Dr COGS, not Dr AR
-- AR should ONLY be debited from Sales Invoice, not from delivery
-- Changed deposit_to from 1 (AR) to 222 (COGS - Finished Goods)
-- ============================================================

UPDATE tbloptions 
SET value = '222' 
WHERE name = 'acc_wh_stock_export_deposit_to';

-- Fix existing stock_export entry that wrongly debited AR instead of COGS
UPDATE tblacc_account_history 
SET account = 222, split = 37 
WHERE id = 11;

UPDATE tblacc_account_history 
SET split = 222 
WHERE id = 12;

-- ============================================================
-- FIX 3: Disable stock export profit conversion
-- Since Sales Invoice already creates Dr AR, Cr Revenue,
-- having stock export also create Dr AR (profit), Cr Revenue 
-- would double-count AR. Disable the profit entry on delivery.
-- ============================================================

UPDATE tbloptions 
SET value = '0' 
WHERE name = 'acc_wh_stock_export_profit_automatic_conversion';

-- ============================================================
-- FIX 4: Sales Invoice VAT Tax Mapping
-- The deposit_to in tax mapping was 356 (VAT Input) which is for purchases.
-- For sales invoices, the debit should go to AR (customer owes us the VAT).
-- Changed deposit_to from 356 to 1 (AR).
-- ============================================================

UPDATE tblacc_tax_mappings 
SET deposit_to = 1 
WHERE id = 1;

-- Remove incorrectly created VAT entries for invoice 1 (invoice has total_tax = 0)
-- These were created from stale item_tax records that don't apply
DELETE FROM tblacc_account_history WHERE id IN (19, 20);
