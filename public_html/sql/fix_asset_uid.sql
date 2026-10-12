-- Add asset_uid column to fe_assets table
-- This stores a timestamp-based unique Asset ID generated on creation
-- =====================================================

ALTER TABLE `tblfe_assets` ADD COLUMN `asset_uid` VARCHAR(50) NULL;
