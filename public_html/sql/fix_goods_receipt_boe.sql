-- Add BOE column to goods_receipt table
-- =====================================================

ALTER TABLE `tblgoods_receipt` ADD COLUMN `boe` VARCHAR(200) NULL;
