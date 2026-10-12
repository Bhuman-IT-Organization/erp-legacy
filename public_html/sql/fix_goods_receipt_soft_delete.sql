-- Add soft delete column to goods_receipt table
-- =====================================================

ALTER TABLE `tblgoods_receipt` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0;
