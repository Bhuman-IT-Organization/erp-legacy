-- ============================================================================
-- Why is the Asset Inventory screen empty?
-- ============================================================================
--
-- Read only. Writes nothing. Safe to run on live, safe to re-run.
--
-- Run the whole file and send back the output. Each section answers one
-- question, in the order they have to be true. The first section that looks
-- wrong is the cause.
--
-- If your client stops partway with an error, that error IS the answer - send it
-- along with whatever output you got.
--
-- Most likely explanation, checked in section 2: the backfill was run with
-- @COMMIT_CHANGES left at 0, which is the default. That mode reports what it
-- would do and writes nothing.
-- ============================================================================


SELECT '1. Does the item_type column exist?' AS check_step;
-- Expect exactly one row. No rows means run 01_inventory_manage_item_type_column.sql.
SHOW COLUMNS FROM tblinventory_manage LIKE 'item_type';


SELECT '2. How are the stock rows classified right now?' AS check_step;
-- All rows showing (null) means the backfill has not been applied. Re-run
-- 02_inventory_manage_item_type_backfill.sql with SET @COMMIT_CHANGES = 1;
SELECT COALESCE(item_type, '(null - not classified)') AS item_type,
       COUNT(*)                                      AS stock_rows
FROM tblinventory_manage
GROUP BY COALESCE(item_type, '(null - not classified)')
ORDER BY item_type;


SELECT '3. Is there any stock at all, and any asset definitions?' AS check_step;
SELECT (SELECT COUNT(*) FROM tblinventory_manage)                                  AS stock_rows_total,
       (SELECT COUNT(*) FROM tblinventory_manage
         WHERE CAST(inventory_number AS DECIMAL(20,4)) <> 0)                        AS stock_rows_nonzero,
       (SELECT COUNT(*) FROM tblfe_asset_creation)                                  AS asset_definitions,
       (SELECT COUNT(*) FROM tblpur_orders WHERE type = 'AS')                       AS asset_purchase_orders;


SELECT '4. Do the goods receipt tables have the columns the trace needs?' AS check_step;
-- The backfill reads tblgoods_receipt.is_deleted. If the next result is empty,
-- that column is absent and the trace cannot run as written - tell me and I will
-- adjust it.
SHOW COLUMNS FROM tblgoods_receipt LIKE 'is_deleted';
SHOW COLUMNS FROM tblgoods_receipt LIKE 'pr_order_id';
SHOW COLUMNS FROM tblgoods_receipt_detail LIKE 'commodity_code';
SHOW COLUMNS FROM tblgoods_receipt_detail LIKE 'unit_price';


SELECT '5. Are any goods receipts linked to asset purchase orders?' AS check_step;
-- 0 here means no asset has ever been received on this database, so there is
-- genuinely no asset stock to show and the empty screen is correct.
SELECT COUNT(*) AS asset_grn_lines
FROM tblgoods_receipt_detail grd
JOIN tblgoods_receipt gr ON gr.id = grd.goods_receipt_id
JOIN tblpur_orders po    ON po.id = gr.pr_order_id
WHERE po.type = 'AS';


SELECT '6. What would the backfill decide for each stock row?' AS check_step;
-- This is the decisive section.
--   asset / item      the trace works, so the rows just need the UPDATE applying
--   cannot tell       the trace cannot see the receipt to purchase order link
SELECT im.id,
       im.commodity_id,
       im.warehouse_id,
       im.inventory_number AS qty,
       im.purchase_price,
       COALESCE(im.item_type, '(null)') AS current_value,
       CASE
         WHEN EXISTS (SELECT 1 FROM tblgoods_receipt_detail grd
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
                         AND po.type <> 'AS' AND COALESCE(gr.is_deleted,0) = 0)
           THEN 'asset (tier 1)'
         WHEN EXISTS (SELECT 1 FROM tblgoods_receipt_detail grd
                        JOIN tblgoods_receipt gr ON gr.id = grd.goods_receipt_id
                        JOIN tblpur_orders po    ON po.id = gr.pr_order_id
                       WHERE grd.commodity_code = im.commodity_id
                         AND grd.warehouse_id   = im.warehouse_id
                         AND ROUND(CAST(grd.unit_price AS DECIMAL(15,2)),2) = ROUND(im.purchase_price,2)
                         AND po.type <> 'AS' AND COALESCE(gr.is_deleted,0) = 0)
           THEN 'item (tier 1)'
         WHEN EXISTS (SELECT 1 FROM tblgoods_receipt_detail grd
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
                         AND po.type <> 'AS' AND COALESCE(gr.is_deleted,0) = 0)
           THEN 'asset (tier 2)'
         WHEN EXISTS (SELECT 1 FROM tblgoods_receipt_detail grd
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
                         AND po.type = 'AS' AND COALESCE(gr.is_deleted,0) = 0)
           THEN 'item (tier 2)'
         ELSE 'cannot tell'
       END AS would_set
FROM tblinventory_manage im
ORDER BY im.id;


SELECT '7. Which database and host answered this?' AS check_step;
-- There is more than one backend behind port 5225. Confirm the script and the
-- web request are reaching the same database.
SELECT DATABASE() AS db_name, @@hostname AS db_host, NOW() AS server_time;
