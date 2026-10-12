-- ============================================================================
-- PERMANENT FIX: All Purchase Accounting Entries
-- Run on LIVE server to fix ALL existing and ensure future correctness
-- ============================================================================

-- ============================================================================
-- STEP 1: Fix system options
-- ============================================================================

-- VAT Paid account for purchase invoices (debit side)
UPDATE tbloptions SET value = '124' WHERE name = 'acc_pur_tax_deposit_to';

-- GR/IR Clearing account
UPDATE tbloptions SET value = '167' WHERE name = 'acc_wh_stock_import_deposit_to';
UPDATE tbloptions SET value = '167' WHERE name = 'acc_wh_stock_import_payment_account';

-- Enable automatic conversions
UPDATE tbloptions SET value = '1' WHERE name = 'acc_wh_stock_import_automatic_conversion';
UPDATE tbloptions SET value = '1' WHERE name = 'acc_pur_invoice_automatic_conversion';
UPDATE tbloptions SET value = '1' WHERE name = 'acc_pur_payment_automatic_conversion';
UPDATE tbloptions SET value = '1' WHERE name = 'acc_tax_automatic_conversion';

-- Keep GRN in PO currency
UPDATE tbloptions SET value = '1' WHERE name = 'goods_receipt_do_not_convert_to_base_currency';

-- ============================================================================
-- STEP 2: Fix GBP exchange rates (if not already set)
-- ============================================================================
UPDATE tblcurrency_rates SET to_currency_rate = 0.215100 WHERE from_currency_id = 1 AND to_currency_id = 4 AND to_currency_rate = 0;
UPDATE tblcurrency_rates SET to_currency_rate = 4.650000 WHERE from_currency_id = 4 AND to_currency_id = 1 AND to_currency_rate = 0;
UPDATE tblcurrency_rates SET to_currency_rate = 0.840000 WHERE from_currency_id = 2 AND to_currency_id = 4 AND to_currency_rate = 0;
UPDATE tblcurrency_rates SET to_currency_rate = 1.190000 WHERE from_currency_id = 4 AND to_currency_id = 2 AND to_currency_rate = 0;
UPDATE tblcurrency_rates SET to_currency_rate = 0.790000 WHERE from_currency_id = 3 AND to_currency_id = 4 AND to_currency_rate = 0;
UPDATE tblcurrency_rates SET to_currency_rate = 1.265000 WHERE from_currency_id = 4 AND to_currency_id = 3 AND to_currency_rate = 0;

-- ============================================================================
-- STEP 3: Fix ALL Purchase Invoice VAT entries
-- Problem: VAT debit was going to 230000 instead of 179020 (VAT Paid)
-- ============================================================================

-- Fix VAT DEBIT entries: should be Dr 124 (179020 VAT Paid)
UPDATE tblacc_account_history h
INNER JOIN tblpur_invoices i ON i.id = h.rel_id
INNER JOIN tblpur_vendor v ON v.userid = i.vendor
SET h.account = 124, h.split = v.ledger_account
WHERE h.rel_type = 'purchase_invoice'
  AND h.tax > 0
  AND h.debit > 0
  AND h.account != 124;

-- Fix VAT CREDIT entries: should be Cr vendor's AP ledger
UPDATE tblacc_account_history h
INNER JOIN tblpur_invoices i ON i.id = h.rel_id
INNER JOIN tblpur_vendor v ON v.userid = i.vendor
SET h.account = v.ledger_account, h.split = 124
WHERE h.rel_type = 'purchase_invoice'
  AND h.tax > 0
  AND h.credit > 0
  AND h.split != 124;

-- ============================================================================
-- STEP 4: Fix Purchase Invoice GR/IR entries (credit side = vendor AP)
-- Problem: Some invoices credit wrong AP account
-- ============================================================================

-- Fix main CREDIT entries: should be Cr vendor's AP ledger (not generic 159)
UPDATE tblacc_account_history h
INNER JOIN tblpur_invoices i ON i.id = h.rel_id
INNER JOIN tblpur_vendor v ON v.userid = i.vendor
SET h.account = v.ledger_account, h.split = 167
WHERE h.rel_type = 'purchase_invoice'
  AND h.tax = 0
  AND h.credit > 0
  AND h.split = 167
  AND h.account != v.ledger_account;

-- Fix main DEBIT split: should reference vendor's AP
UPDATE tblacc_account_history h
INNER JOIN tblpur_invoices i ON i.id = h.rel_id
INNER JOIN tblpur_vendor v ON v.userid = i.vendor
SET h.split = v.ledger_account
WHERE h.rel_type = 'purchase_invoice'
  AND h.tax = 0
  AND h.debit > 0
  AND h.account = 167
  AND h.split != v.ledger_account;

-- ============================================================================
-- STEP 5: Fix GRN (stock_import) accounting entries
-- Ensure all use correct PR type ledger as debit, 240000 GR/IR as credit
-- ============================================================================

-- Delete all stock_import entries and reset for regeneration
DELETE FROM tblacc_account_history WHERE rel_type = 'stock_import';
UPDATE tblgoods_receipt SET acc_mapping = 0 WHERE approval = 1 AND is_deleted = 0;

-- NOTE: After running this SQL, run the regeneration PHP script to recreate GRN entries:
-- https://fd098.fortiddns.com:5225/regenerate_grn_accounting_live.php
-- This script reads each GRN's PO → PR type → ledger_account and posts correctly.

-- ============================================================================
-- STEP 6: Fix Purchase Payment entries (credit side = vendor AP)
-- ============================================================================

-- Fix payment DEBIT entries: should be Dr vendor's AP ledger
UPDATE tblacc_account_history h
INNER JOIN tblpur_invoice_payment p ON p.id = h.rel_id
INNER JOIN tblpur_invoices i ON i.id = p.pur_invoice
INNER JOIN tblpur_vendor v ON v.userid = i.vendor
SET h.account = v.ledger_account
WHERE h.rel_type = 'purchase_payment'
  AND h.debit > 0
  AND h.account = 159
  AND v.ledger_account != 159;

-- Fix payment CREDIT split: should reference vendor's AP
UPDATE tblacc_account_history h
INNER JOIN tblpur_invoice_payment p ON p.id = h.rel_id
INNER JOIN tblpur_invoices i ON i.id = p.pur_invoice
INNER JOIN tblpur_vendor v ON v.userid = i.vendor
SET h.split = v.ledger_account
WHERE h.rel_type = 'purchase_payment'
  AND h.credit > 0
  AND h.split = 159
  AND v.ledger_account != 159;

-- ============================================================================
-- STEP 7: Fix PO totals that are 0 but have items
-- ============================================================================
UPDATE tblpur_orders po SET po.subtotal = (SELECT COALESCE(SUM(pod.into_money), 0) FROM tblpur_order_detail pod WHERE pod.pur_order = po.id) WHERE po.subtotal = 0 AND EXISTS (SELECT 1 FROM tblpur_order_detail pod WHERE pod.pur_order = po.id AND pod.into_money > 0);
UPDATE tblpur_orders po SET po.total_tax = (SELECT COALESCE(SUM(pod.tax_value), 0) FROM tblpur_order_detail pod WHERE pod.pur_order = po.id) WHERE po.total_tax = 0 AND EXISTS (SELECT 1 FROM tblpur_order_detail pod WHERE pod.pur_order = po.id AND pod.tax_value > 0);
UPDATE tblpur_orders po SET po.total = (SELECT COALESCE(SUM(pod.total), 0) FROM tblpur_order_detail pod WHERE pod.pur_order = po.id) WHERE po.total = 0 AND EXISTS (SELECT 1 FROM tblpur_order_detail pod WHERE pod.pur_order = po.id AND pod.total > 0);

-- ============================================================================
-- STEP 8: Backfill vendor notes from PR to PO
-- ============================================================================
UPDATE tblpur_orders po 
INNER JOIN tblpur_request pr ON pr.id = po.pur_request 
SET po.vendornote = pr.rq_description 
WHERE (po.vendornote IS NULL OR po.vendornote = '') 
AND pr.rq_description IS NOT NULL 
AND pr.rq_description != '';

-- ============================================================================
-- STEP 9: Verify trial balance
-- ============================================================================
-- After running, check: SELECT SUM(debit) as dr, SUM(credit) as cr, SUM(debit)-SUM(credit) as diff FROM tblacc_account_history;
-- Diff should be 0.00

-- ============================================================================
-- DONE
-- Next: Run regenerate_grn_accounting_live.php to recreate GRN entries
-- ============================================================================
