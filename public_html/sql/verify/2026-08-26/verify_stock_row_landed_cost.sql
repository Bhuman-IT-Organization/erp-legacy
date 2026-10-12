-- ============================================================================
-- VERIFICATION ONLY - read only, changes nothing.
-- ============================================================================
--
-- Proves the per stock row landed cost trail used by the Inventory Stock grid:
--
--   tblinventory_manage
--     -> tblgoods_receipt_detail   (item + warehouse + manufacture/expiry/lot)
--     -> tblgoods_receipt
--     -> tblpur_invoices.grn_id
--     -> tblinv_shipping_charges
--
-- Each stock row is one delivery, so it carries the freight from the invoice that
-- brought that batch in - not an average across every delivery.
--
-- The allocated freight uses the shipping Net Total (VAT included) spread pro rata
-- by net line value, matching Purchase_model::get_invoice_landed_costs().
--
-- Change the id below to check a different item. 21 = NPRM3500003.
-- ============================================================================

SET @commodity_id = 21;

SELECT
    im.id                       AS stock_row,
    im.warehouse_id,
    im.inventory_number         AS qty_in_stock,
    im.date_manufacture,
    im.expiry_date,
    im.lot_number,
    im.purchase_price           AS stock_unit_price,
    gr.goods_receipt_code       AS grn,
    i.invoice_number,
    i.vendor_invoice_number,
    d.quantity                  AS invoiced_qty,
    d.unit_price,
    ROUND(d.into_money - COALESCE(d.discount_money, 0), 2) AS line_net,
    COALESCE(sc.shipping_net_total, 0) AS shipping_net_total,
    sc.shipping_numbers,
    -- Allocated freight for this line
    ROUND(
        COALESCE(sc.shipping_net_total, 0)
        * ((d.into_money - COALESCE(d.discount_money, 0)) / NULLIF(inv.invoice_net, 0))
    , 2)                        AS allocated_freight,
    -- Landed unit cost for this batch: what the grid column shows
    ROUND(
        (
            (d.into_money - COALESCE(d.discount_money, 0))
            + COALESCE(ROUND(COALESCE(sc.shipping_net_total, 0)
                * ((d.into_money - COALESCE(d.discount_money, 0)) / NULLIF(inv.invoice_net, 0)), 2), 0)
        ) / NULLIF(d.quantity, 0)
    , 4)                        AS landed_unit_cost
FROM tblinventory_manage im
-- The receipt that delivered this batch
LEFT JOIN tblgoods_receipt_detail grd
       ON grd.commodity_code = im.commodity_id
      AND grd.warehouse_id = im.warehouse_id
      AND grd.date_manufacture <=> im.date_manufacture
      AND grd.expiry_date <=> im.expiry_date
      AND COALESCE(grd.lot_number, '') = COALESCE(im.lot_number, '')
LEFT JOIN tblgoods_receipt gr
       ON gr.id = grd.goods_receipt_id
      AND gr.is_deleted = 0
      AND gr.is_reversed = 0
-- The invoice raised against that receipt. grn_id may list several ids.
LEFT JOIN tblpur_invoices i
       ON FIND_IN_SET(gr.id, i.grn_id)
      AND (i.is_deleted = 0 OR i.is_deleted IS NULL)
-- That invoice's line for this item
LEFT JOIN tblpur_invoice_details d
       ON d.pur_invoice = i.id
      AND d.item_code = im.commodity_id
-- Allocation base
LEFT JOIN (
    SELECT pur_invoice, ROUND(SUM(into_money - COALESCE(discount_money, 0)), 2) AS invoice_net
    FROM tblpur_invoice_details
    GROUP BY pur_invoice
) inv ON inv.pur_invoice = i.id
-- Shipping raised against the invoice
LEFT JOIN (
    SELECT invoice_id,
           SUM(total) AS shipping_net_total,
           GROUP_CONCAT(shipping_number ORDER BY id) AS shipping_numbers
    FROM tblinv_shipping_charges
    GROUP BY invoice_id
) sc ON sc.invoice_id = i.id
WHERE im.commodity_id = @commodity_id
ORDER BY im.id;

-- ---------------------------------------------------------------------------
-- Every stock row must trace to exactly one invoice
-- ---------------------------------------------------------------------------
-- More than one match means the batch keys are not unique enough and the grid
-- would pick the newest receipt. Zero means the row shows "-".
SELECT
    im.id AS stock_row,
    im.commodity_id,
    COUNT(DISTINCT gr.id) AS receipts_matched,
    COUNT(DISTINCT i.id)  AS invoices_matched
FROM tblinventory_manage im
LEFT JOIN tblgoods_receipt_detail grd
       ON grd.commodity_code = im.commodity_id
      AND grd.warehouse_id = im.warehouse_id
      AND grd.date_manufacture <=> im.date_manufacture
      AND grd.expiry_date <=> im.expiry_date
      AND COALESCE(grd.lot_number, '') = COALESCE(im.lot_number, '')
LEFT JOIN tblgoods_receipt gr
       ON gr.id = grd.goods_receipt_id AND gr.is_deleted = 0 AND gr.is_reversed = 0
LEFT JOIN tblpur_invoices i
       ON FIND_IN_SET(gr.id, i.grn_id) AND (i.is_deleted = 0 OR i.is_deleted IS NULL)
WHERE im.commodity_id = @commodity_id
GROUP BY im.id, im.commodity_id
ORDER BY im.id;
