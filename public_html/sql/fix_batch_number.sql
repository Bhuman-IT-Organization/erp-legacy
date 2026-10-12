-- Add batch_number column to goods_receipt_detail table
ALTER TABLE `tblgoods_receipt_detail` ADD COLUMN `batch_number` VARCHAR(100) DEFAULT NULL;

-- Also add to history detail table
ALTER TABLE `tblgoods_receipt_history_detail` ADD COLUMN `batch_number` VARCHAR(100) DEFAULT NULL;
