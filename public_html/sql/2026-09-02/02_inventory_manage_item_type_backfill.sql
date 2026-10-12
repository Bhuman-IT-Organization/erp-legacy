-- ============================================================================
-- STEP 2 of 2 - classify existing stock rows as item or asset
-- ============================================================================
--
-- Run 01_inventory_manage_item_type_column.sql first. This file contains no
-- ALTER statements, so it raises no errors and can be run as many times as you
-- like - it only ever fills in rows that are still NULL.
--
-- WHY THIS IS NEEDED
--   Asset purchase orders (tblpur_orders.type = 'AS') are received through the
--   warehouse goods receipt, and Warehouse_model::add_inventory_manage() writes
--   the resulting stock into tblinventory_manage with
--   commodity_id = tblfe_asset_creation.id.
--
--   Item stock uses the same column for tblitems.id, and the id ranges overlap
--   completely - every one of the 32 stock rows in this database has a
--   commodity_id that exists in BOTH tables. So "is this row an asset?" cannot be
--   answered by joining on the id, and an asset inventory screen built that way
--   shows a pH Meter with 19,200 units in stock.
--
--   Warehouse_model::add_inventory_manage() stamps item_type directly from the
--   purchase order type from now on. This is only for rows that predate that.
--
-- HOW THE BACKFILL DECIDES, most reliable first
--   Tier 1  Trace stock row -> goods receipt detail -> goods receipt -> purchase
--           order, matching commodity_id, warehouse_id AND the batch unit price.
--           add_inventory_manage() keys each batch on
--           purchase_price = the receipt line's unit_price, so this pins the
--           exact receipt line. Applied only when the trace finds one type and
--           not the other.
--
--   Tier 2  The same trace without the price, again only when unambiguous.
--           Needed because a receipt in a foreign currency has its unit price
--           divided by the exchange rate before the stock row is written, so the
--           stored price does not equal the receipt line price.
--
--   Left NULL  Anything the tiers disagree on or cannot see. Reported at the end.
--           Deliberately not guessed - a wrong classification either hides real
--           stock or inflates asset values, and both are worse than a row left
--           for a human to decide.
--
-- HOW TO RUN
--   Dry run first. It writes nothing and shows what it would change:
--       set the flag below to 0, run, read the reports.
--   Then set it to 1 and run again to apply.
--
--   On the sample database this classified 14 asset rows and 16 item rows and
--   left 2 for review.
-- ============================================================================

SET @COMMIT_CHANGES = 0;

SELECT CONCAT('Mode: ', IF(@COMMIT_CHANGES = 1, 'APPLY', 'DRY RUN - nothing will be written')) AS mode;

SELECT 'If the next query errors with Unknown column item_type, run 01_inventory_manage_item_type_column.sql first.' AS note;

SELECT COUNT(*) AS rows_still_unclassified
FROM tblinventory_manage
WHERE item_type IS NULL;


-- ----------------------------------------------------------------------------
-- What each tier WOULD set, shown before anything is written
-- ----------------------------------------------------------------------------
SELECT '=== WHAT THE TRACE FINDS (before any change) ===' AS report;

SELECT im.id,
       im.commodity_id,
       im.warehouse_id,
       im.inventory_number AS qty,
       im.purchase_price,
       im.item_type        AS current_value,
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
         ELSE 'cannot tell - left NULL'
       END AS would_set
FROM tblinventory_manage im
ORDER BY im.id;


-- ----------------------------------------------------------------------------
-- Tier 1 - traced with the batch unit price
-- ----------------------------------------------------------------------------
SET @sql := IF(@COMMIT_CHANGES = 1,
"UPDATE tblinventory_manage im
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
                   AND po.type <> 'AS' AND COALESCE(gr.is_deleted,0) = 0)",
"SELECT 'dry run - tier 1 asset not applied' AS skipped");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF(@COMMIT_CHANGES = 1,
"UPDATE tblinventory_manage im
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
                   AND po.type = 'AS' AND COALESCE(gr.is_deleted,0) = 0)",
"SELECT 'dry run - tier 1 item not applied' AS skipped");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ----------------------------------------------------------------------------
-- Tier 2 - same trace, price ignored (foreign currency receipts)
-- ----------------------------------------------------------------------------
SET @sql := IF(@COMMIT_CHANGES = 1,
"UPDATE tblinventory_manage im
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
                   AND po.type <> 'AS' AND COALESCE(gr.is_deleted,0) = 0)",
"SELECT 'dry run - tier 2 asset not applied' AS skipped");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @sql := IF(@COMMIT_CHANGES = 1,
"UPDATE tblinventory_manage im
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
                   AND po.type = 'AS' AND COALESCE(gr.is_deleted,0) = 0)",
"SELECT 'dry run - tier 2 item not applied' AS skipped");
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;


-- ----------------------------------------------------------------------------
-- Result
-- ----------------------------------------------------------------------------
SELECT '=== CLASSIFICATION SUMMARY ===' AS report;

SELECT COALESCE(item_type, '(unclassified)') AS item_type,
       COUNT(*) AS stock_rows,
       SUM(CAST(inventory_number AS DECIMAL(20,4))) AS total_quantity,
       ROUND(SUM(CAST(inventory_number AS DECIMAL(20,4)) * purchase_price), 2) AS stock_value
FROM tblinventory_manage
GROUP BY COALESCE(item_type, '(unclassified)')
ORDER BY item_type;

SELECT '=== ROWS NEEDING A MANUAL DECISION ===' AS report;
SELECT 'Both names below resolve on the same id. Decide which it is, then:' AS note
UNION ALL
SELECT 'UPDATE tblinventory_manage SET item_type = ''item'' WHERE id = <id>;'
UNION ALL
SELECT 'A high quantity of a consumable is almost always item stock.';

SELECT im.id,
       im.commodity_id,
       im.warehouse_id,
       im.inventory_number   AS qty,
       im.purchase_price,
       it.description        AS matches_item_named,
       ac.asset_title        AS matches_asset_named
FROM tblinventory_manage im
LEFT JOIN tblitems it             ON it.id = im.commodity_id
LEFT JOIN tblfe_asset_creation ac ON ac.id = im.commodity_id
WHERE im.item_type IS NULL
ORDER BY im.id;

SELECT '=== ASSET STOCK AS IT WILL APPEAR ON THE ASSET INVENTORY SCREEN ===' AS report;

SELECT im.id, im.commodity_id, ac.code, ac.asset_title,
       im.warehouse_id, im.inventory_number AS qty, im.purchase_price,
       ROUND(CAST(im.inventory_number AS DECIMAL(20,4)) * im.purchase_price, 2) AS stock_value
FROM tblinventory_manage im
JOIN tblfe_asset_creation ac ON ac.id = im.commodity_id
WHERE im.item_type = 'asset'
ORDER BY im.id;
