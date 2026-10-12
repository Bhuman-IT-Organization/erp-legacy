-- Add received_qty and pending_qty columns to goods_receipt_detail table
ALTER TABLE `tblgoods_receipt_detail` ADD COLUMN `received_qty` DECIMAL(15,2) DEFAULT NULL;
ALTER TABLE `tblgoods_receipt_detail` ADD COLUMN `pending_qty` DECIMAL(15,2) DEFAULT NULL;
