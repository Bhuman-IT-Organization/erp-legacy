-- Add cheque fields to sales invoice payment records table
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `tblinvoicepaymentrecords`
    ADD COLUMN IF NOT EXISTS `cheque_bank_name` VARCHAR(200) NULL DEFAULT NULL AFTER `note`,
    ADD COLUMN IF NOT EXISTS `cheque_number`    VARCHAR(100) NULL DEFAULT NULL AFTER `cheque_bank_name`,
    ADD COLUMN IF NOT EXISTS `cheque_date`      DATE         NULL DEFAULT NULL AFTER `cheque_number`,
    ADD COLUMN IF NOT EXISTS `cheque_attachment` VARCHAR(500) NULL DEFAULT NULL AFTER `cheque_date`;

SET FOREIGN_KEY_CHECKS = 1;
