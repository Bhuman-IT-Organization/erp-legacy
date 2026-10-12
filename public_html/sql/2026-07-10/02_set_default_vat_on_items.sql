-- Set VAT (tax_id=1, 5%) as default tax for all items
UPDATE tblitems SET tax = 1 WHERE (tax = 0 OR tax IS NULL);
