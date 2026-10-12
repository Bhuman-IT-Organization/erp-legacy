-- Add asset_uid column to all purchase detail tables
-- =====================================================

ALTER TABLE `tblpur_request_detail` ADD COLUMN `asset_uid` VARCHAR(50) NULL;
ALTER TABLE `tblpur_order_detail` ADD COLUMN `asset_uid` VARCHAR(50) NULL;
ALTER TABLE `tblpur_invoice_details` ADD COLUMN `asset_uid` VARCHAR(50) NULL;
ALTER TABLE `tblgoods_receipt_detail` ADD COLUMN `asset_uid` VARCHAR(50) NULL;
