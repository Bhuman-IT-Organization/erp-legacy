-- ============================================================================
-- RUN THIS ONE. It writes immediately - there is no dry run flag to set.
-- ============================================================================
--
-- Run it on EVERY database that serves the app: inova, nextgen_live, and any
-- other copy behind the load balancer.
--
-- Prerequisite: the item_type column must exist. If the first statement fails
-- with "Unknown column 'item_type'", run 01_inventory_manage_item_type_column.sql
-- first, then come back here.
--
-- Safe to run more than once. Every statement only touches rows where item_type
-- is still NULL, so a second run changes nothing.
--
-- Why this file exists: 02_inventory_manage_item_type_backfill.sql defaults to
-- SET @COMMIT_CHANGES = 0, which reports what it would do and writes nothing.
-- Running it unchanged looks like it worked but leaves every row unclassified,
-- and the Asset Inventory screen shows no records because an unclassified row is
-- deliberately excluded. This file has no such switch.
--
-- What it does: works out whether each stock row came from an asset purchase
-- order or a normal one, by tracing it back through the goods receipt. Needed
-- because tblinventory_manage.commodity_id holds a tblitems id for normal stock
-- and a tblfe_asset_creation id for asset stock, and those id ranges overlap
-- completely - so the id alone cannot tell you which kind of row it is.
-- ============================================================================


-- ----------------------------------------------------------------------------
-- Step 1 of 4 - asset rows, matched on the batch unit price (most precise)
-- ----------------------------------------------------------------------------
UPDATE tblinventory_manage im
   SET im.item_type = 'asset'
 WHERE im.item_type IS NULL
   AND EXISTS (SELECT 1 FROM tblgoods_receipt_detail grd
                 JOIN tblgoods_receipt gr ON gr.id = grd.goods_receipt_id
                 JOIN tblpur_orders po    ON po.id = gr.pr_order_id
                WHERE grd.commodity_code = im.commodity_id
                  AND grd.warehouse_id   = im.warehouse_id
                  AND ROUND(CAST(grd.unit_price AS DECIMAL(15,2)),2) = ROUND(im.purchase_price,2)
                  AND po.type = 'AS' AND COALESCE(gr.is_deleted,0) = 0)
   AND NOT EXISTS (SELECT 1 FROM tblgoods_receipt_detail grd
                 JOIN tblgoods_receipt gr ON gr.id = grd.goods_receipt_id
                 JOIN tblpur_orders po    ON po.id = gr.pr_order_id
                WHERE grd.commodity_code = im.commodity_id
                  AND grd.warehouse_id   = im.warehouse_id
                  AND ROUND(CAST(grd.unit_price AS DECIMAL(15,2)),2) = ROUND(im.purchase_price,2)
                  AND po.type <> 'AS' AND COALESCE(gr.is_deleted,0) = 0);


-- ----------------------------------------------------------------------------
-- Step 2 of 4 - item rows, matched on the batch unit price
-- ----------------------------------------------------------------------------
UPDATE tblinventory_manage im
   SET im.item_type = 'item'
 WHERE im.item_type IS NULL
   AND EXISTS (SELECT 1 FROM tblgoods_receipt_detail grd
                 JOIN tblgoods_receipt gr ON gr.id = grd.goods_receipt_id
                 JOIN tblpur_orders po    ON po.id = gr.pr_order_id
                WHERE grd.commodity_code = im.commodity_id
                  AND grd.warehouse_id   = im.warehouse_id
                  AND ROUND(CAST(grd.unit_price AS DECIMAL(15,2)),2) = ROUND(im.purchase_price,2)
                  AND po.type <> 'AS' AND COALESCE(gr.is_deleted,0) = 0)
   AND NOT EXISTS (SELECT 1 FROM tblgoods_receipt_detail grd
                 JOIN tblgoods_receipt gr ON gr.id = grd.goods_receipt_id
                 JOIN tblpur_orders po    ON po.id = gr.pr_order_id
                WHERE grd.commodity_code = im.commodity_id
                  AND grd.warehouse_id   = im.warehouse_id
                  AND ROUND(CAST(grd.unit_price AS DECIMAL(15,2)),2) = ROUND(im.purchase_price,2)
                  AND po.type = 'AS' AND COALESCE(gr.is_deleted,0) = 0);


-- ----------------------------------------------------------------------------
-- Step 3 of 4 - asset rows, price ignored.
-- A receipt in a foreign currency has its unit price divided by the exchange
-- rate before the stock row is written, so the stored price does not equal the
-- receipt line price and step 1 cannot match it.
-- ----------------------------------------------------------------------------
UPDATE tblinventory_manage im
   SET im.item_type = 'asset'
 WHERE im.item_type IS NULL
   AND EXISTS (SELECT 1 FROM tblgoods_receipt_detail grd
                 JOIN tblgoods_receipt gr ON gr.id = grd.goods_receipt_id
                 JOIN tblpur_orders po    ON po.id = gr.pr_order_id
                WHERE grd.commodity_code = im.commodity_id
                  AND grd.warehouse_id   = im.warehouse_id
                  AND po.type = 'AS' AND COALESCE(gr.is_deleted,0) = 0)
   AND NOT EXISTS (SELECT 1 FROM tblgoods_receipt_detail grd
                 JOIN tblgoods_receipt gr ON gr.id = grd.goods_receipt_id
                 JOIN tblpur_orders po    ON po.id = gr.pr_order_id
                WHERE grd.commodity_code = im.commodity_id
                  AND grd.warehouse_id   = im.warehouse_id
                  AND po.type <> 'AS' AND COALESCE(gr.is_deleted,0) = 0);


-- ----------------------------------------------------------------------------
-- Step 4 of 4 - item rows, price ignored
-- ----------------------------------------------------------------------------
UPDATE tblinventory_manage im
   SET im.item_type = 'item'
 WHERE im.item_type IS NULL
   AND EXISTS (SELECT 1 FROM tblgoods_receipt_detail grd
                 JOIN tblgoods_receipt gr ON gr.id = grd.goods_receipt_id
                 JOIN tblpur_orders po    ON po.id = gr.pr_order_id
                WHERE grd.commodity_code = im.commodity_id
                  AND grd.warehouse_id   = im.warehouse_id
                  AND po.type <> 'AS' AND COALESCE(gr.is_deleted,0) = 0)
   AND NOT EXISTS (SELECT 1 FROM tblgoods_receipt_detail grd
                 JOIN tblgoods_receipt gr ON gr.id = grd.goods_receipt_id
                 JOIN tblpur_orders po    ON po.id = gr.pr_order_id
                WHERE grd.commodity_code = im.commodity_id
                  AND grd.warehouse_id   = im.warehouse_id
                  AND po.type = 'AS' AND COALESCE(gr.is_deleted,0) = 0);


-- ============================================================================
-- CHECK - read the two results below before you leave this page
-- ============================================================================

-- 1. asset must be greater than zero. If it is 0, the screen will still be empty
--    and the second result explains why.
SELECT COALESCE(item_type, 'NOT CLASSIFIED') AS item_type,
       COUNT(*)                              AS stock_rows,
       ROUND(SUM(CAST(inventory_number AS DECIMAL(20,4)) * purchase_price), 2) AS stock_value
FROM tblinventory_manage
GROUP BY COALESCE(item_type, 'NOT CLASSIFIED')
ORDER BY item_type;


-- 2. Exactly what the Asset Inventory screen will list. Rows with zero quantity
--    are hidden on screen by default, which is why this filters them out too.
SELECT im.id            AS stock_row,
       ac.code          AS asset_code,
       ac.asset_title,
       im.warehouse_id,
       im.inventory_number AS qty,
       im.purchase_price,
       ROUND(CAST(im.inventory_number AS DECIMAL(20,4)) * im.purchase_price, 2) AS stock_value
FROM tblinventory_manage im
JOIN tblfe_asset_creation ac ON ac.id = im.commodity_id
WHERE im.item_type = 'asset'
  AND CAST(im.inventory_number AS DECIMAL(20,4)) <> 0
ORDER BY ac.code DESC;
