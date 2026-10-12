-- PR Soft Delete: Add is_deleted flag to tblpur_request
SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE `tblpur_request`
    ADD COLUMN IF NOT EXISTS `is_deleted` TINYINT(1) NOT NULL DEFAULT 0;

SET FOREIGN_KEY_CHECKS = 1;
