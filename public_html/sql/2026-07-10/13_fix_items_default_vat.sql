-- Set VAT (tax_id=1, 5%) as default tax for ALL items
-- This ensures VAT auto-applies when items are added to invoices/estimates
UPDATE tblitems SET tax = 1 WHERE (tax = 0 OR tax IS NULL);
