-- Fix PO #39 (PO00000041) - linked to PR #26 with 65 items
-- Same fix as PO #28

-- Step 1: Delete existing items for PO #39
DELETE FROM tblpur_order_detail WHERE pur_order = 39;

-- Step 2: Insert correct 65 items from PR #26
INSERT INTO tblpur_order_detail (pur_order, item_code, unit_price, quantity, into_money, total, tax_value, tax, tax_rate, tax_name, item_name, description)
SELECT 39, item_code, unit_price, quantity, into_money, total, tax_value, tax, tax_rate, tax_name, item_text, description
FROM tblpur_request_detail WHERE pur_request = 26 ORDER BY prd_id;

-- Step 3: Recalculate PO #39 totals
UPDATE tblpur_orders SET 
    subtotal = (SELECT COALESCE(SUM(into_money), 0) FROM tblpur_order_detail WHERE pur_order = 39),
    total_tax = (SELECT COALESCE(SUM(tax_value), 0) FROM tblpur_order_detail WHERE pur_order = 39),
    total = (SELECT COALESCE(SUM(total), 0) FROM tblpur_order_detail WHERE pur_order = 39)
WHERE id = 39;
