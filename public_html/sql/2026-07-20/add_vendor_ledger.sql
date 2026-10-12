-- Add ledger_account column to pur_vendor table
ALTER TABLE `tblpur_vendor` ADD COLUMN `ledger_account` INT(11) DEFAULT NULL AFTER `vendor_code_prefix`;

-- Set default ledger for all vendors: 159 = 230000 A/Payable Stock Vendors - Local
UPDATE `tblpur_vendor` SET `ledger_account` = 159 WHERE `ledger_account` IS NULL;
