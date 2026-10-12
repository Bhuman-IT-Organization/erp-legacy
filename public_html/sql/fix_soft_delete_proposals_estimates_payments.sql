-- Soft delete columns for Proposals, Estimates/Sales Orders, Invoice Payments
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `tblproposals`
    ADD COLUMN IF NOT EXISTS `is_deleted`   TINYINT(1)   NOT NULL DEFAULT 0    AFTER `date_converted`,
    ADD COLUMN IF NOT EXISTS `date_deleted` DATETIME     NULL     DEFAULT NULL  AFTER `is_deleted`,
    ADD INDEX  IF NOT EXISTS `idx_proposals_is_deleted` (`is_deleted`);

ALTER TABLE `tblestimates`
    ADD COLUMN IF NOT EXISTS `is_deleted`   TINYINT(1)   NOT NULL DEFAULT 0    AFTER `invoiceid`,
    ADD COLUMN IF NOT EXISTS `date_deleted` DATETIME     NULL     DEFAULT NULL  AFTER `is_deleted`,
    ADD INDEX  IF NOT EXISTS `idx_estimates_is_deleted` (`is_deleted`);

ALTER TABLE `tblinvoicepaymentrecords`
    ADD COLUMN IF NOT EXISTS `is_deleted`   TINYINT(1)   NOT NULL DEFAULT 0    AFTER `daterecorded`,
    ADD COLUMN IF NOT EXISTS `date_deleted` DATETIME     NULL     DEFAULT NULL  AFTER `is_deleted`,
    ADD INDEX  IF NOT EXISTS `idx_payments_is_deleted` (`is_deleted`);

SET FOREIGN_KEY_CHECKS = 1;
