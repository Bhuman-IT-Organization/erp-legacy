-- Soft delete columns for Goods Delivery and Invoices
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `tblgoods_delivery`
    ADD COLUMN IF NOT EXISTS `is_deleted`   TINYINT(1) NOT NULL DEFAULT 0   AFTER `approval`,
    ADD COLUMN IF NOT EXISTS `date_deleted` DATETIME   NULL     DEFAULT NULL AFTER `is_deleted`,
    ADD INDEX  IF NOT EXISTS `idx_gd_is_deleted` (`is_deleted`);

ALTER TABLE `tblinvoices`
    ADD COLUMN IF NOT EXISTS `is_deleted`   TINYINT(1) NOT NULL DEFAULT 0   AFTER `status`,
    ADD COLUMN IF NOT EXISTS `date_deleted` DATETIME   NULL     DEFAULT NULL AFTER `is_deleted`,
    ADD INDEX  IF NOT EXISTS `idx_inv_is_deleted` (`is_deleted`);

-- Also ensure estimate_id and proposal_id exist on goods_delivery
ALTER TABLE `tblgoods_delivery`
    ADD COLUMN IF NOT EXISTS `estimate_id`  INT(11) NULL DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS `proposal_id`  INT(11) NULL DEFAULT NULL;

SET FOREIGN_KEY_CHECKS = 1;
