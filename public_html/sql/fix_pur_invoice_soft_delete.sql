-- Add soft delete column to purchase invoices table
-- =====================================================

ALTER TABLE `tblpur_invoices` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0;
