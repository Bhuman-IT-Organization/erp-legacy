-- Fix PO totals that are 0 but have items with values
-- This recalculates subtotal, total_tax, and total from PO detail items

-- Update subtotal (sum of into_money from items)
UPDATE tblpur_orders po
SET po.subtotal = (
    SELECT COALESCE(SUM(pod.into_money), 0) 
    FROM tblpur_order_detail pod 
    WHERE pod.pur_order = po.id
)
WHERE po.subtotal = 0 
AND EXISTS (SELECT 1 FROM tblpur_order_detail pod WHERE pod.pur_order = po.id AND pod.into_money > 0);

-- Update total_tax (sum of tax_value from items)
UPDATE tblpur_orders po
SET po.total_tax = (
    SELECT COALESCE(SUM(pod.tax_value), 0) 
    FROM tblpur_order_detail pod 
    WHERE pod.pur_order = po.id
)
WHERE po.total_tax = 0 
AND EXISTS (SELECT 1 FROM tblpur_order_detail pod WHERE pod.pur_order = po.id AND pod.tax_value > 0);

-- Update total (sum of total from items, or subtotal + tax if total_money not reliable)
UPDATE tblpur_orders po
SET po.total = (
    SELECT COALESCE(SUM(pod.total), 0) 
    FROM tblpur_order_detail pod 
    WHERE pod.pur_order = po.id
)
WHERE po.total = 0 
AND EXISTS (SELECT 1 FROM tblpur_order_detail pod WHERE pod.pur_order = po.id AND pod.total > 0);
