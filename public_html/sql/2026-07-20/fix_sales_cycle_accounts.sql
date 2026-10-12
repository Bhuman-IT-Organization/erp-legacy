-- ============================================================
-- FIX SALES CYCLE - LIVE SERVER
-- Date: 2026-07-20
--
-- Fixes:
-- 1. Stock export options (old accounts 1, 66 → active 6-digit)
-- 2. Invoice/payment/tax options pointing to inactive accounts
-- 3. XK00000003 entries remapped to correct ledgers
-- 4. All entries on old accounts remapped
-- 5. Old accounts deactivated
-- ============================================================

-- ═══════════════════════════════════════════════════════════════
-- PART 1: FIX STOCK EXPORT (GOODS DELIVERY) OPTIONS
-- ═══════════════════════════════════════════════════════════════

-- COGS account (Dr side when goods delivered)
UPDATE `tbloptions` SET `value` = '222' WHERE `name` = 'acc_wh_stock_export_deposit_to';
-- Inventory account (Cr side - stock reduction)
UPDATE `tbloptions` SET `value` = '112' WHERE `name` = 'acc_wh_stock_export_payment_account';
-- Revenue account (Cr side - profit)
UPDATE `tbloptions` SET `value` = '199' WHERE `name` = 'acc_wh_stock_export_profit_payment_account';
-- A/R account (Dr side - profit)
UPDATE `tbloptions` SET `value` = '117' WHERE `name` = 'acc_wh_stock_export_profit_deposit_to';
-- Enable profit conversion
UPDATE `tbloptions` SET `value` = '1' WHERE `name` = 'acc_wh_stock_export_automatic_conversion';
UPDATE `tbloptions` SET `value` = '1' WHERE `name` = 'acc_wh_stock_export_profit_automatic_conversion';

-- ═══════════════════════════════════════════════════════════════
-- PART 2: FIX INVOICE / PAYMENT / TAX OPTIONS
-- ═══════════════════════════════════════════════════════════════

-- Invoice
UPDATE `tbloptions` SET `value` = '117' WHERE `name` = 'acc_invoice_deposit_to';
UPDATE `tbloptions` SET `value` = '199' WHERE `name` = 'acc_invoice_payment_account';
UPDATE `tbloptions` SET `value` = '117' WHERE `name` = 'acc_invoice_discount_payment_account';
UPDATE `tbloptions` SET `value` = '201' WHERE `name` = 'acc_invoice_discount_deposit_to';

-- Payment
UPDATE `tbloptions` SET `value` = '117' WHERE `name` = 'acc_payment_payment_account';
UPDATE `tbloptions` SET `value` = '133' WHERE `name` = 'acc_payment_deposit_to';

-- Tax (VAT)
UPDATE `tbloptions` SET `value` = '123' WHERE `name` = 'acc_tax_payment_account';
UPDATE `tbloptions` SET `value` = '117' WHERE `name` = 'acc_tax_deposit_to';

-- Credit Notes
UPDATE `tbloptions` SET `value` = '117' WHERE `name` = 'acc_credit_note_payment_account';
UPDATE `tbloptions` SET `value` = '117' WHERE `name` = 'acc_credit_note_refund_payment_account';

-- GRN
UPDATE `tbloptions` SET `value` = '112' WHERE `name` = 'acc_wh_stock_import_deposit_to';
UPDATE `tbloptions` SET `value` = '167' WHERE `name` = 'acc_wh_stock_import_payment_account';
UPDATE `tbloptions` SET `value` = '167' WHERE `name` = 'acc_wh_stock_import_return_payment_account';

-- Shipping
UPDATE `tbloptions` SET `value` = '290' WHERE `name` = 'acc_pur_order_shipping_payment_account';
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_order_shipping_deposit_to';

-- Purchase discount
UPDATE `tbloptions` SET `value` = '201' WHERE `name` = 'acc_pur_order_return_discount_payment_account';

-- Omni sales
UPDATE `tbloptions` SET `value` = '117' WHERE `name` = 'acc_omni_sales_order_return_payment_account';
UPDATE `tbloptions` SET `value` = '199' WHERE `name` = 'acc_omni_sales_order_return_deposit_to';
UPDATE `tbloptions` SET `value` = '117' WHERE `name` = 'acc_omni_sales_refund_deposit_to';

-- ═══════════════════════════════════════════════════════════════
-- PART 3: PAYMENT MODE MAPPINGS
-- ═══════════════════════════════════════════════════════════════

DELETE FROM `tblacc_payment_mode_mappings` WHERE `payment_mode_id` IN ('1','2','3');

INSERT INTO `tblacc_payment_mode_mappings`
  (`payment_mode_id`, `payment_account`, `deposit_to`, `expense_payment_account`, `expense_deposit_to`, `credit_note_refund_payment_account`, `credit_note_refund_deposit_to`)
VALUES
  ('1', 134, 117, 159, 134, 134, 117),
  ('2', 133, 117, 159, 133, 133, 117),
  ('3', 166, 117, 159, 166, 166, 117);

UPDATE `tbloptions` SET `value` = '1' WHERE `name` = 'acc_active_payment_mode_mapping';

-- ═══════════════════════════════════════════════════════════════
-- PART 4: REMAP ALL ENTRIES ON OLD INACTIVE ACCOUNTS
-- ═══════════════════════════════════════════════════════════════

-- Account 1 (1100 Accounts Receivable) → 117 (170000 A/R Trade Receivable)
UPDATE `tblacc_account_history` SET `account` = 117 WHERE `account` = 1;
UPDATE `tblacc_account_history` SET `split` = 117 WHERE `split` = 1;

-- Account 66 (4100 Sales) → 199 (300000 Revenue - Domestic)
UPDATE `tblacc_account_history` SET `account` = 199 WHERE `account` = 66;
UPDATE `tblacc_account_history` SET `split` = 199 WHERE `split` = 66;

-- Account 19 (5030 Discounts) → 201 (300300 Discount)
UPDATE `tblacc_account_history` SET `account` = 201 WHERE `account` = 19;
UPDATE `tblacc_account_history` SET `split` = 201 WHERE `split` = 19;

-- Account 37 (1301 Inventory Asset) → 112 (150000 Inventory Raw Materials)
UPDATE `tblacc_account_history` SET `account` = 112 WHERE `account` = 37;
UPDATE `tblacc_account_history` SET `split` = 112 WHERE `split` = 37;

-- Account 13 (1010 Cash) → 133 (190005 Cash In Hand)
UPDATE `tblacc_account_history` SET `account` = 133 WHERE `account` = 13;
UPDATE `tblacc_account_history` SET `split` = 133 WHERE `split` = 13;

-- Account 87 (2100 Accounts Payable) → 159 (230000 A/P Stock Vendors)
UPDATE `tblacc_account_history` SET `account` = 159 WHERE `account` = 87;
UPDATE `tblacc_account_history` SET `split` = 159 WHERE `split` = 87;

-- Account 80 (6900 Uncategorised Expense) → 294 (420046 Factory General Expenses)
UPDATE `tblacc_account_history` SET `account` = 294 WHERE `account` = 80;
UPDATE `tblacc_account_history` SET `split` = 294 WHERE `split` = 80;

-- Account 29 (2400 VAT Payable) → 123 (179010 VAT Received)
UPDATE `tblacc_account_history` SET `account` = 123 WHERE `account` = 29;
UPDATE `tblacc_account_history` SET `split` = 123 WHERE `split` = 29;

-- Account 16 (5000 Cost of Sales) → 214 (400000 COGP-Material Usage)
UPDATE `tblacc_account_history` SET `account` = 214 WHERE `account` = 16;
UPDATE `tblacc_account_history` SET `split` = 214 WHERE `split` = 16;

-- ═══════════════════════════════════════════════════════════════
-- PART 5: FIX XK00000003 SPECIFICALLY
-- Entry breakdown: COGS 1050 (Dr COGS, Cr RM) + Profit 3950 (Dr A/R, Cr Revenue)
-- ═══════════════════════════════════════════════════════════════

-- Fix COGS entry (was Dr account=1, should be Dr account=222)
UPDATE `tblacc_account_history`
SET `account` = 222, `split` = 112, `number` = 'XK00000003', `customer` = 91, `description` = 'COGS: XK00000003'
WHERE `rel_type` = 'stock_export' AND `rel_id` = 1 AND `debit` = 1050.00 AND `account` IN (1, 117);

-- Fix Inventory Cr entry (split was 1, should be 222)
UPDATE `tblacc_account_history`
SET `split` = 222, `number` = 'XK00000003', `customer` = 91, `description` = 'COGS: XK00000003'
WHERE `rel_type` = 'stock_export' AND `rel_id` = 1 AND `credit` = 1050.00 AND `account` = 112;

-- Fix Revenue Dr entry (was account=1, should be account=117)
UPDATE `tblacc_account_history`
SET `account` = 117, `split` = 199, `number` = 'XK00000003', `customer` = 91, `description` = 'Sales: XK00000003'
WHERE `rel_type` = 'stock_export' AND `rel_id` = 1 AND `debit` = 3950.00 AND `account` IN (1, 117);

-- Fix Revenue Cr entry (was account=66, should be account=199)
UPDATE `tblacc_account_history`
SET `account` = 199, `split` = 117, `number` = 'XK00000003', `customer` = 91, `description` = 'Sales: XK00000003'
WHERE `rel_type` = 'stock_export' AND `rel_id` = 1 AND `credit` = 3950.00 AND `account` IN (66, 199);

-- ═══════════════════════════════════════════════════════════════
-- PART 6: FIX MISSING NUMBER/VENDOR ON ALL GRN ENTRIES
-- ═══════════════════════════════════════════════════════════════

UPDATE `tblacc_account_history` h
JOIN `tblgoods_receipt` gr ON gr.id = h.rel_id
SET h.number = gr.goods_receipt_code, h.vendor = gr.supplier_code
WHERE h.rel_type = 'stock_import' AND (h.number IS NULL OR h.number = '');

UPDATE `tblacc_account_history` h
JOIN `tblgoods_delivery` gd ON gd.id = h.rel_id
SET h.number = gd.goods_delivery_code, h.customer = gd.customer_code
WHERE h.rel_type = 'stock_export' AND (h.number IS NULL OR h.number = '');

-- ═══════════════════════════════════════════════════════════════
-- PART 7: DEACTIVATE OLD ACCOUNTS
-- ═══════════════════════════════════════════════════════════════

UPDATE `tblacc_accounts` SET `active` = 0 WHERE `id` IN (1, 13, 16, 19, 29, 37, 66, 80, 87);

UPDATE `tblacc_accounts` SET `active` = 0
WHERE `number` IS NOT NULL AND `number` != '' AND LENGTH(`number`) <= 4 AND `active` = 1;

-- ═══════════════════════════════════════════════════════════════
-- EXPECTED RESULT FOR XK00000003:
--   400170 COGS-FG:          Dr 1,050 (cost of raw material delivered)
--   150000 Inventory RM:     Cr 1,050 (stock reduced)
--   170000 A/R Receivable:   Dr 3,950 (customer owes profit portion)
--   300000 Revenue:          Cr 3,950 (sales income)
--
-- For NEW deliveries, the system auto-creates:
--   Dr COGS (222/400170) = purchase_price × qty
--   Cr Inventory RM (112/150000) = purchase_price × qty
--   Dr A/R (117/170000) = profit (selling_price - cost) × qty
--   Cr Revenue (199/300000) = profit × qty
-- ═══════════════════════════════════════════════════════════════

-- ═══════════════════════════════════════════════════════════════
-- ACCOUNT REFERENCE
-- ═══════════════════════════════════════════════════════════════
-- 112 = 150000 Inventory Raw Materials
-- 114 = 150002 Inventory Finished Goods
-- 117 = 170000 A/R Trade Receivable
-- 123 = 179010 VAT Received
-- 133 = 190005 Cash In Hand
-- 134 = 190010 NBF Bank
-- 159 = 230000 A/Payable Stock Vendors
-- 166 = 237512 Post Dated Cheque
-- 167 = 240000 GR/IR Clearing
-- 199 = 300000 Revenue - Domestic
-- 201 = 300300 Discount
-- 214 = 400000 COGP-Material Usage
-- 222 = 400170 COGS - Finished Goods
-- 290 = 420042 Freight Charges In
-- 294 = 420046 Factory General Expenses
-- ═══════════════════════════════════════════════════════════════
