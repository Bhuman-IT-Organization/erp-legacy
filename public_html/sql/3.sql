
-- Add is_deleted to pur_orders
ALTER TABLE `tblpur_orders`
    ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0;
