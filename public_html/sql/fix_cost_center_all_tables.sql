-- Add cost_center and ledger_account to purchase request (if not already added)
-- ALTER TABLE `tblpur_request` ADD COLUMN `cost_center` INT DEFAULT NULL;
-- ALTER TABLE `tblpur_request` ADD COLUMN `ledger_account` INT DEFAULT NULL;

-- Add cost_center and ledger_account to purchase orders
ALTER TABLE `tblpur_orders` ADD COLUMN `cost_center` INT DEFAULT NULL;
ALTER TABLE `tblpur_orders` ADD COLUMN `ledger_account` INT DEFAULT NULL;

-- Add cost_center and ledger_account to purchase estimates
ALTER TABLE `tblpur_estimates` ADD COLUMN `cost_center` INT DEFAULT NULL;
ALTER TABLE `tblpur_estimates` ADD COLUMN `ledger_account` INT DEFAULT NULL;
