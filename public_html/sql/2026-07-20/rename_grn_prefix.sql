-- ============================================================
-- RENAME GRN PREFIX: NK → GR
-- Updates all GRN codes and references in the database
-- Run on LIVE SERVER
-- ============================================================

-- 1. Update goods_receipt code
UPDATE `tblgoods_receipt` SET `goods_receipt_code` = REPLACE(`goods_receipt_code`, 'NK', 'GR') WHERE `goods_receipt_code` LIKE 'NK%';

-- 2. Update accounting entries that reference GRN numbers
UPDATE `tblacc_account_history` SET `number` = REPLACE(`number`, 'NK', 'GR') WHERE `number` LIKE 'NK%' AND `rel_type` = 'stock_import';

-- 3. Update accounting entry descriptions
UPDATE `tblacc_account_history` SET `description` = REPLACE(`description`, 'NK', 'GR') WHERE `description` LIKE '%NK0000%';

-- 4. Update the GRN prefix option/setting (so new GRNs start with GR)
UPDATE `tbloptions` SET `value` = 'GR' WHERE `name` = 'wh_goods_receipt_prefix' AND `value` = 'NK';

-- Check if it's in warehouse_option table instead
UPDATE `tbloptions` SET `value` = 'GR' WHERE `name` = 'goods_receipt_prefix' AND `value` = 'NK';

