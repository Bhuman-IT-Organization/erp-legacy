-- GRN Reversal: Add is_reversed flag to goods_receipt
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `tblgoods_receipt`
    ADD COLUMN IF NOT EXISTS `is_reversed` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_deleted`;

SET FOREIGN_KEY_CHECKS = 1;
