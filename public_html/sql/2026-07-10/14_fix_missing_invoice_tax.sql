-- Fix all invoices that have total showing VAT but tblitem_tax is empty
-- This adds VAT 5% to each invoice item that has no tax record

-- Step 1: Insert missing tax records for invoice items
INSERT INTO tblitem_tax (itemid, rel_id, rel_type, taxrate, taxname)
SELECT i.id, i.rel_id, 'invoice', 5.00, 'VAT'
FROM tblitemable i
WHERE i.rel_type = 'invoice'
AND NOT EXISTS (
    SELECT 1 FROM tblitem_tax t 
    WHERE t.itemid = i.id AND t.rel_id = i.rel_id AND t.rel_type = 'invoice'
);

-- Step 2: Recalculate total_tax for all invoices where it's 0 but total > subtotal
UPDATE tblinvoices 
SET total_tax = ROUND(subtotal * 0.05, 2)
WHERE total_tax = 0 
AND total > subtotal 
AND (total - subtotal) > 0;
