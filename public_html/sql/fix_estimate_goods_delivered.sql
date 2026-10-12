-- Add goods_delivered flag to estimates table
-- Prevents invoicing until goods delivery is created
-- =====================================================

ALTER TABLE `tblestimates` ADD COLUMN `goods_delivered` TINYINT(1) NOT NULL DEFAULT 0;
