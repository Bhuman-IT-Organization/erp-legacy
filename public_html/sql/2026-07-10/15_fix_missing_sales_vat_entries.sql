-- Fix missing VAT accounting entries for sales invoices
-- Adds Dr AR(117) and Cr VAT Received(123) for invoices that have total_tax > 0
-- but no VAT accounting entry exists

INSERT INTO tblacc_account_history (account, debit, credit, rel_id, rel_type, date, datecreated, addedfrom, customer, split, tax, paid)
SELECT 
    117, inv.total_tax, 0.00, inv.id, 'invoice', inv.date, NOW(), 1, inv.clientid, 123, 1, 0
FROM tblinvoices inv
WHERE inv.total_tax > 0
AND inv.id NOT IN (
    SELECT rel_id FROM tblacc_account_history 
    WHERE rel_type = 'invoice' AND tax = 1 AND debit > 0
);

INSERT INTO tblacc_account_history (account, debit, credit, rel_id, rel_type, date, datecreated, addedfrom, customer, split, tax, paid)
SELECT 
    123, 0.00, inv.total_tax, inv.id, 'invoice', inv.date, NOW(), 1, inv.clientid, 117, 1, 0
FROM tblinvoices inv
WHERE inv.total_tax > 0
AND inv.id NOT IN (
    SELECT rel_id FROM tblacc_account_history 
    WHERE rel_type = 'invoice' AND tax = 1 AND credit > 0
);
