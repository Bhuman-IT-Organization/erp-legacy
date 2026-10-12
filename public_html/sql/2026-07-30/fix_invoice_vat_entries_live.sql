-- ============================================================================
-- Fix Purchase Invoice VAT Entries on Live Server
-- Problem: VAT debit was going to wrong account (230000 instead of 179020 VAT Paid)
--          VAT credit was going to generic 230000 instead of vendor's specific AP
-- ============================================================================

-- Step 1: Fix the option so future invoices post correctly
-- acc_pur_tax_deposit_to should be 124 (179020 VAT Paid) — the DEBIT side for tax
UPDATE tbloptions SET value = '124' WHERE name = 'acc_pur_tax_deposit_to';

-- Step 2: Fix all existing wrong VAT DEBIT entries
-- These are entries where tax > 0 AND debit > 0 AND account is NOT 124 (VAT Paid)
-- They should be Dr 124 (VAT Paid) with split = vendor's AP ledger
UPDATE tblacc_account_history h
INNER JOIN tblpur_invoices i ON i.id = h.rel_id
INNER JOIN tblpur_vendor v ON v.userid = i.vendor
SET h.account = 124,
    h.split = v.ledger_account
WHERE h.rel_type = 'purchase_invoice'
  AND h.tax > 0
  AND h.debit > 0
  AND h.account != 124;

-- Step 3: Fix all existing wrong VAT CREDIT entries
-- These are entries where tax > 0 AND credit > 0 AND account is NOT the vendor's AP
-- They should be Cr vendor's AP with split = 124 (VAT Paid)
UPDATE tblacc_account_history h
INNER JOIN tblpur_invoices i ON i.id = h.rel_id
INNER JOIN tblpur_vendor v ON v.userid = i.vendor
SET h.account = v.ledger_account,
    h.split = 124
WHERE h.rel_type = 'purchase_invoice'
  AND h.tax > 0
  AND h.credit > 0
  AND h.account != v.ledger_account;
