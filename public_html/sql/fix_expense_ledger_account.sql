-- Add ledger_account column to expenses table for double-entry accounting
-- =====================================================

ALTER TABLE `tblexpenses` ADD COLUMN `ledger_account` INT(11) NULL;
