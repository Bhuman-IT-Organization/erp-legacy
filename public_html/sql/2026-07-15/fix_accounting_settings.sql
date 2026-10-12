-- =====================================================
-- FIX ACCOUNTING SETTINGS - Replace old inactive accounts
-- Run on live server after add_missing_columns_simple.sql
-- =====================================================

-- Purchase Tax settings (VAT)
-- Old: 87 (AP old), 29 (VAT Payable old)
-- New: 124 (179020 VAT Paid), 159 (230000 Vendor AP)
UPDATE `tbloptions` SET `value` = '124' WHERE `name` = 'acc_pur_tax_payment_account';
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_tax_deposit_to';

-- Purchase Invoice Discount
-- Old: 80 (Uncategorised Expense), 19 (unknown)
-- New: 201 (300300 Discount), 159 (230000 Vendor AP)
UPDATE `tbloptions` SET `value` = '201' WHERE `name` = 'acc_pur_invoice_discount_payment_account';
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_invoice_discount_deposit_to';

-- Purchase Invoice Shipping
-- Old: 13 (Cash old), 80 (Uncategorised Expense)
-- New: 290 (420042 Freight Charges In/Shipping), 159 (230000 Vendor AP)
UPDATE `tbloptions` SET `value` = '290' WHERE `name` = 'acc_pur_invoice_shipping_payment_account';
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_invoice_shipping_deposit_to';

-- Purchase Order Discount
-- Old: 80 (Uncategorised Expense), 19 (unknown)
-- New: 201 (300300 Discount), 159 (230000 Vendor AP)
UPDATE `tbloptions` SET `value` = '201' WHERE `name` = 'acc_pur_order_discount_payment_account';
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_order_discount_deposit_to';

-- Purchase Order Shipping
-- Old: 13 (Cash old), 80 (Uncategorised Expense)
-- New: 290 (420042 Freight Charges In/Shipping), 159 (230000 Vendor AP)
UPDATE `tbloptions` SET `value` = '290' WHERE `name` = 'acc_pur_order_shipping_payment_account';
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_order_shipping_deposit_to';

-- GRN (Stock Import) defaults
-- New: 112 (150000 Inventory Raw Materials), 167 (240000 GR/IR Clearing)
UPDATE `tbloptions` SET `value` = '112' WHERE `name` = 'acc_wh_stock_import_deposit_to';
UPDATE `tbloptions` SET `value` = '167' WHERE `name` = 'acc_wh_stock_import_payment_account';

-- Purchase Order defaults
-- New: 112 (debit fallback), 167 (credit = GR/IR Clearing)
UPDATE `tbloptions` SET `value` = '167' WHERE `name` = 'acc_pur_order_payment_account';
UPDATE `tbloptions` SET `value` = '112' WHERE `name` = 'acc_pur_order_deposit_to';

-- Purchase Invoice AP account
UPDATE `tbloptions` SET `value` = '159' WHERE `name` = 'acc_pur_invoice_payment_account';

-- Tax mapping (VAT 5%): Ensure correct mapping exists
-- Sales: Dr 123 (179010 VAT Received), Cr 117 (170000 AR)
-- Expense: Dr 124 (179020 VAT Paid), Cr 159 (230000 AP)
-- Purchase: Dr 124 (179020 VAT Paid), Cr 159 (230000 AP)
INSERT IGNORE INTO `tblacc_tax_mappings` (`id`, `tax_id`, `payment_account`, `deposit_to`, `expense_payment_account`, `expense_deposit_to`, `purchase_payment_account`, `purchase_deposit_to`) 
VALUES (1, 1, 123, 117, 124, 159, 124, 159);
-- Update if already exists with wrong values
UPDATE `tblacc_tax_mappings` SET 
  `payment_account` = 123, 
  `deposit_to` = 117, 
  `expense_payment_account` = 124, 
  `expense_deposit_to` = 159, 
  `purchase_payment_account` = 124, 
  `purchase_deposit_to` = 159 
WHERE `tax_id` = 1;

-- Purchase Request Types - credit_ledger (GR/IR Clearing)
UPDATE `tblpur_request_types` SET `credit_ledger` = 167 WHERE `credit_ledger` IS NULL OR `credit_ledger` = 0;

-- =====================================================
-- Fix any existing wrong entries in accounting history
-- Replace old inactive accounts with active ones
-- =====================================================

-- Account 87 (old AP) -> 159 (active AP)
UPDATE `tblacc_account_history` SET `account` = 159 WHERE `account` = 87;
UPDATE `tblacc_account_history` SET `split` = 159 WHERE `split` = 87;

-- Account 29 (old VAT Payable) -> 124 (VAT Paid) for debits, 159 (AP) for credits
UPDATE `tblacc_account_history` SET `account` = 124 WHERE `account` = 29 AND `debit` > 0;
UPDATE `tblacc_account_history` SET `account` = 159 WHERE `account` = 29 AND `credit` > 0;
UPDATE `tblacc_account_history` SET `split` = 124 WHERE `split` = 29;

-- Account 80 (old Uncategorised Expense) used as shipping debit -> 290
UPDATE `tblacc_account_history` SET `account` = 290 WHERE `account` = 80 AND `debit` > 0 AND `description` LIKE '%hipping%';
UPDATE `tblacc_account_history` SET `split` = 290 WHERE `split` = 80 AND `description` LIKE '%hipping%';
-- Account 80 used as discount credit -> 159 (AP)
UPDATE `tblacc_account_history` SET `account` = 159 WHERE `account` = 80 AND `credit` > 0;
UPDATE `tblacc_account_history` SET `split` = 159 WHERE `split` = 80 AND `credit` > 0;
-- Account 80 remaining (discount debit) -> 201
UPDATE `tblacc_account_history` SET `account` = 201 WHERE `account` = 80 AND `debit` > 0;
UPDATE `tblacc_account_history` SET `split` = 201 WHERE `split` = 80;

-- Account 13 (old Cash) -> 159 (AP) for credits in shipping context
UPDATE `tblacc_account_history` SET `account` = 159 WHERE `account` = 13 AND `credit` > 0;
UPDATE `tblacc_account_history` SET `split` = 159 WHERE `split` = 13;

-- Account 19 (old unknown) -> 201 (Discount) for debits
UPDATE `tblacc_account_history` SET `account` = 201 WHERE `account` = 19 AND `debit` > 0;
UPDATE `tblacc_account_history` SET `split` = 201 WHERE `split` = 19;
-- Account 19 credits -> 159 (AP)
UPDATE `tblacc_account_history` SET `account` = 159 WHERE `account` = 19 AND `credit` > 0;

-- Account 37 (old Inventory Asset) -> 167 (GR/IR Clearing) for stock_import
UPDATE `tblacc_account_history` SET `account` = 167 WHERE `account` = 37 AND `rel_type` = 'stock_import';
UPDATE `tblacc_account_history` SET `split` = 167 WHERE `split` = 37;

-- =====================================================
-- Verify balance after fixes
-- =====================================================
SELECT SUM(debit) as total_debit, SUM(credit) as total_credit, SUM(debit)-SUM(credit) as difference FROM tblacc_account_history;
