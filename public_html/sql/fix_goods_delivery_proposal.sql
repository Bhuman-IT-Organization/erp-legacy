-- Add proposal_id column to goods_delivery table for Sales Proposal reference
-- =====================================================

ALTER TABLE `tblgoods_delivery` ADD COLUMN `proposal_id` INT(11) NULL;
