-- Set VAT (tax_id=1, 5%) as default tax for all items
-- This ensures VAT is auto-selected when adding items to estimates/invoices
UPDATE tblitems SET tax = 1 WHERE (tax = 0 OR tax IS NULL);
