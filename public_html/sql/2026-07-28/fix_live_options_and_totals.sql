-- Enable GRN to keep PO currency (not convert to AED)
UPDATE tbloptions SET value = '1' WHERE name = 'goods_receipt_do_not_convert_to_base_currency';

-- Fix PO totals that are 0 but have items
UPDATE tblpur_orders po SET po.subtotal = (SELECT COALESCE(SUM(pod.into_money), 0) FROM tblpur_order_detail pod WHERE pod.pur_order = po.id) WHERE po.subtotal = 0 AND EXISTS (SELECT 1 FROM tblpur_order_detail pod WHERE pod.pur_order = po.id AND pod.into_money > 0);

UPDATE tblpur_orders po SET po.total_tax = (SELECT COALESCE(SUM(pod.tax_value), 0) FROM tblpur_order_detail pod WHERE pod.pur_order = po.id) WHERE po.total_tax = 0 AND EXISTS (SELECT 1 FROM tblpur_order_detail pod WHERE pod.pur_order = po.id AND pod.tax_value > 0);

UPDATE tblpur_orders po SET po.total = (SELECT COALESCE(SUM(pod.total), 0) FROM tblpur_order_detail pod WHERE pod.pur_order = po.id) WHERE po.total = 0 AND EXISTS (SELECT 1 FROM tblpur_order_detail pod WHERE pod.pur_order = po.id AND pod.total > 0);
