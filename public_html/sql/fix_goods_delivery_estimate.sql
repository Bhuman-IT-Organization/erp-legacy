-- Add estimate_id column to goods_delivery table for Sales Order (Estimate) reference
-- =====================================================

ALTER TABLE `tblgoods_delivery` ADD COLUMN `estimate_id` INT(11) NULL;
