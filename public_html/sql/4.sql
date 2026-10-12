
-- Add is_deleted to pur_request
ALTER TABLE `tblpur_request`
    ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0;

