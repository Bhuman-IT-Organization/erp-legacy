-- ============================================================================
-- STEP 1 of 2 - add the item_type column to tblinventory_manage
-- ============================================================================
--
-- Run this first, then run 02_inventory_manage_item_type_backfill.sql.
--
-- IF YOU SEE THIS, IT IS FINE AND YOU ARE DONE WITH STEP 1:
--     #1060 - Duplicate column name 'item_type'
--     #1061 - Duplicate key name 'idx_inventory_manage_item_type'
--
-- Both mean the column and index already exist, so there is nothing to add.
-- Move on to step 2.
--
-- The two statements are in their own file precisely because of that. phpMyAdmin
-- stops at the first error, so when these were in the same file as the backfill,
-- a re-run raised #1060 here and silently skipped everything after it - the
-- column got added but nothing was ever classified.
--
-- The existence check is not done with information_schema on purpose: the live
-- database user cannot read it.
--
-- Run the two statements one at a time if your client aborts the whole file on
-- the first error.
-- ============================================================================

ALTER TABLE `tblinventory_manage`
    ADD COLUMN `item_type` VARCHAR(20) NULL DEFAULT NULL
    COMMENT 'item = tblitems stock, asset = tblfe_asset_creation stock, NULL = unclassified legacy row';

ALTER TABLE `tblinventory_manage`
    ADD INDEX `idx_inventory_manage_item_type` (`item_type`);

-- Confirm it is there. Expect one row named item_type.
SHOW COLUMNS FROM `tblinventory_manage` LIKE 'item_type';
