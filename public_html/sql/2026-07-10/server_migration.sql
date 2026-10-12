-- ============================================================
-- SERVER MIGRATION SQL - Run on live server
-- Date: 2026-07-10
-- Database: nextgen (or nextgen_latest)
-- ============================================================

-- 1. GL Account column on payment tables
ALTER TABLE tblpur_invoice_payment ADD COLUMN IF NOT EXISTS gl_account INT(11) DEFAULT NULL;
ALTER TABLE tblpur_order_payment ADD COLUMN IF NOT EXISTS gl_account INT(11) DEFAULT NULL;
ALTER TABLE tblinvoicepaymentrecords ADD COLUMN IF NOT EXISTS gl_account INT(11) DEFAULT NULL;

-- 2. Soft delete columns
ALTER TABLE tblexpenses ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE tblexpenses ADD COLUMN IF NOT EXISTS date_deleted DATETIME NULL DEFAULT NULL;
ALTER TABLE tblinvoices ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE tblinvoices ADD COLUMN IF NOT EXISTS date_deleted DATETIME NULL DEFAULT NULL;
ALTER TABLE tblgoods_delivery ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE tblgoods_delivery ADD COLUMN IF NOT EXISTS date_deleted DATETIME NULL DEFAULT NULL;
ALTER TABLE tblinvoicepaymentrecords ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;

-- 3. Sales invoice - sales order and delivery voucher reference columns
ALTER TABLE tblinvoices ADD COLUMN IF NOT EXISTS sales_order_id INT(11) DEFAULT NULL;
ALTER TABLE tblinvoices ADD COLUMN IF NOT EXISTS delivery_voucher_ids TEXT DEFAULT NULL;

-- 4. Purchase return - GRN reference column
ALTER TABLE tblwh_order_returns ADD COLUMN IF NOT EXISTS grn_id INT(11) DEFAULT NULL AFTER rel_id;

-- 4b. Clear cheque management data
TRUNCATE TABLE tblcheque_management;
TRUNCATE TABLE tblcheque_transactions;

-- 5. Set VAT as default tax on all items
UPDATE tblitems SET tax = 1 WHERE (tax = 0 OR tax IS NULL);

-- 6. Use minified files OFF (for main.js discount fix)
UPDATE tbloptions SET value = '0' WHERE name = 'use_minified_files';

-- 7. VAT account mapping (179010 = VAT Received, 179020 = VAT Paid)
UPDATE tblacc_tax_mappings SET 
  payment_account = 123,
  deposit_to = 117,
  purchase_payment_account = 124,
  purchase_deposit_to = 159,
  expense_payment_account = 124,
  expense_deposit_to = 159
WHERE tax_id = 1;

-- 8. Payment mode mappings (use active 6-digit accounts)
-- Bank: Purchase Dr AP(159), Cr Bank(134) | Sales Dr Bank(134), Cr AR(117)
UPDATE tblacc_payment_mode_mappings SET 
  expense_payment_account = 159, expense_deposit_to = 134,
  deposit_to = 134, payment_account = 117
WHERE payment_mode_id = 1;

-- Cash: Purchase Dr AP(159), Cr Cash(133) | Sales Dr Cash(133), Cr AR(117)
UPDATE tblacc_payment_mode_mappings SET 
  expense_payment_account = 159, expense_deposit_to = 133,
  deposit_to = 133, payment_account = 117
WHERE payment_mode_id = 2;

-- Cheque: Purchase Dr AP(159), Cr PDC(166) | Sales Dr PDC(166), Cr AR(117)
UPDATE tblacc_payment_mode_mappings SET 
  expense_payment_account = 159, expense_deposit_to = 166,
  deposit_to = 166, payment_account = 117
WHERE payment_mode_id = 3;

-- 9. Key accounting options (use active 6-digit accounts)
-- Sales Invoice: Dr AR(117), Cr Revenue(199)
UPDATE tbloptions SET value = '117' WHERE name = 'acc_invoice_deposit_to';
UPDATE tbloptions SET value = '199' WHERE name = 'acc_invoice_payment_account';

-- Sales Discount: Dr Discount(201), Cr AR(117)
UPDATE tbloptions SET value = '201' WHERE name = 'acc_invoice_discount_payment_account';
UPDATE tbloptions SET value = '117' WHERE name = 'acc_invoice_discount_deposit_to';

-- Purchase Invoice: Cr AP(159), Dr Inventory(112 for without-PO)
UPDATE tbloptions SET value = '159' WHERE name = 'acc_pur_invoice_payment_account';
UPDATE tbloptions SET value = '112' WHERE name = 'acc_pur_invoice_deposit_to';

-- Purchase Shipping: Dr Freight(290), Cr AP(159)
UPDATE tbloptions SET value = '290' WHERE name = 'acc_pur_invoice_shipping_deposit_to';
UPDATE tbloptions SET value = '159' WHERE name = 'acc_pur_invoice_shipping_payment_account';

-- Purchase Discount: Dr AP(159), Cr Discount Received(209)
UPDATE tbloptions SET value = '159' WHERE name = 'acc_pur_invoice_discount_deposit_to';
UPDATE tbloptions SET value = '209' WHERE name = 'acc_pur_invoice_discount_payment_account';

-- Purchase VAT: Dr VAT Paid(124), Cr AP(159)
UPDATE tbloptions SET value = '124' WHERE name = 'acc_pur_tax_payment_account';
UPDATE tbloptions SET value = '159' WHERE name = 'acc_pur_tax_deposit_to';

-- Goods Delivery: Dr COGS-FG(222), Cr Inventory FG(114)
UPDATE tbloptions SET value = '222' WHERE name = 'acc_wh_stock_export_deposit_to';
UPDATE tbloptions SET value = '114' WHERE name = 'acc_wh_stock_export_payment_account';
UPDATE tbloptions SET value = '0' WHERE name = 'acc_wh_stock_export_profit_automatic_conversion';

-- Purchase Return: Dr AP(159), Cr Inventory RM(112)
UPDATE tbloptions SET value = '159' WHERE name = 'acc_pur_order_return_deposit_to';
UPDATE tbloptions SET value = '112' WHERE name = 'acc_pur_order_return_payment_account';
UPDATE tbloptions SET value = '159' WHERE name = 'acc_pur_order_return_discount_deposit_to';
UPDATE tbloptions SET value = '209' WHERE name = 'acc_pur_order_return_discount_payment_account';
UPDATE tbloptions SET value = '290' WHERE name = 'acc_pur_order_return_fee_deposit_to';
UPDATE tbloptions SET value = '159' WHERE name = 'acc_pur_order_return_fee_payment_account';

-- 10. Migrate existing accounting entries from inactive accounts to active ones
UPDATE tblacc_account_history SET account = 159 WHERE account = 87;
UPDATE tblacc_account_history SET split = 159 WHERE split = 87;
UPDATE tblacc_account_history SET account = 117 WHERE account = 1;
UPDATE tblacc_account_history SET split = 117 WHERE split = 1;
UPDATE tblacc_account_history SET account = 114 WHERE account = 37;
UPDATE tblacc_account_history SET split = 114 WHERE split = 37;
UPDATE tblacc_account_history SET account = 199 WHERE account = 66;
UPDATE tblacc_account_history SET split = 199 WHERE split = 66;
UPDATE tblacc_account_history SET account = 124 WHERE account = 356;
UPDATE tblacc_account_history SET split = 124 WHERE split = 356;
UPDATE tblacc_account_history SET account = 123 WHERE account = 29;
UPDATE tblacc_account_history SET split = 123 WHERE split = 29;

-- ============================================================
-- ACCOUNT REFERENCE:
-- 117 = 170000 - A/R Trade receivable - Customers (active)
-- 159 = 230000 - A/Payable Stock Vendors - Local (active)
-- 112 = 150000 - Inventory Raw Materials (active)
-- 114 = 150002 - Inventory Finished Goods (active)
-- 123 = 179010 - VAT Received (active)
-- 124 = 179020 - VAT Paid (active)
-- 133 = 190005 - Cash In Hand (active)
-- 134 = 190010 - NBF Bank AED (active)
-- 166 = 237512 - Post Dated Cheque (active)
-- 167 = 240000 - GR/IR Clearing (active)
-- 199 = 300000 - Revenue - Domestic (active)
-- 201 = 300300 - Discount (active)
-- 209 = 310010 - Cash Discount Received (active)
-- 222 = 400170 - Cost of Goods Sold - FG (active)
-- 290 = 420042 - Freight Charges In/Shipping Exp (active)
-- ============================================================
