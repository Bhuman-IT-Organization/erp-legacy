-- Add soft delete column to purchase invoice payment table
-- =====================================================

ALTER TABLE `tblpur_invoice_payment` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0;
