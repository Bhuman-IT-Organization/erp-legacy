

-- Add item_description to goods_receipt_detail
ALTER TABLE `tblgoods_receipt_detail`
    ADD COLUMN `item_description` TEXT NULL DEFAULT NULL;
