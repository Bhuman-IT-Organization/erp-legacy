-- Add cost_center and ledger_account columns to purchase request table
ALTER TABLE `tblpur_request` ADD COLUMN `cost_center` INT DEFAULT NULL;
ALTER TABLE `tblpur_request` ADD COLUMN `ledger_account` INT DEFAULT NULL;
