-- Fix purchase invoice accounting entries with missing invoice number and vendor
-- This fixes shipping/discount entries that were posted without number/vendor fields

UPDATE tblacc_account_history h 
INNER JOIN tblpur_invoices i ON i.id = h.rel_id 
SET h.number = i.invoice_number, h.vendor = i.vendor 
WHERE h.rel_type = 'purchase_invoice' 
AND (h.number IS NULL OR h.vendor IS NULL OR h.vendor = 0);

-- Fix shipping expense account option (was 159/AP, should be 290/Freight Expense)
UPDATE tbloptions SET value = '290' WHERE name = 'acc_pur_invoice_shipping_deposit_to';

-- Fix tax mapping purchase_deposit_to (was 159/AP, should be 124/VAT Paid)
UPDATE tblacc_tax_mappings SET purchase_deposit_to = 124 WHERE purchase_deposit_to != 124;

-- Fix reversed shipping entries: swap Dr/Cr accounts
-- Shipping debit entries that went to AP (159) should go to Freight (290)
UPDATE tblacc_account_history h
INNER JOIN tblpur_invoices i ON i.id = h.rel_id
INNER JOIN tblpur_vendor v ON v.userid = i.vendor
SET h.account = 290, h.split = v.ledger_account
WHERE h.rel_type = 'purchase_invoice'
AND h.debit > 0
AND h.description LIKE '%Shipping%'
AND h.account != 290;

-- Shipping credit entries that went to Freight (290) should go to Vendor AP
UPDATE tblacc_account_history h
INNER JOIN tblpur_invoices i ON i.id = h.rel_id
INNER JOIN tblpur_vendor v ON v.userid = i.vendor
SET h.account = v.ledger_account, h.split = 290
WHERE h.rel_type = 'purchase_invoice'
AND h.credit > 0
AND h.description LIKE '%Shipping%'
AND h.account = 290;

-- Fix cron warning
UPDATE tbloptions SET value = '1' WHERE name = 'cron_has_run_from_cli';
UPDATE tbloptions SET value = UNIX_TIMESTAMP() WHERE name = 'last_cron_run';
