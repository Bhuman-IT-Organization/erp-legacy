-- Fix notification records that have literal text instead of language keys
-- The 'Purchase Quotation Added EST-000001' should use the language key 'purchase_quotation_added'
UPDATE tblnotifications 
SET description = 'purchase_quotation_added' 
WHERE description LIKE 'Purchase Quotation Added%';
