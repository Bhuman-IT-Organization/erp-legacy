-- Update purchase request types with correct GL Account Debit and Credit
-- ledger_account = GL Account Debit
-- credit_ledger = GL Account Credit (240000 GR/IR Clearing for all)

-- First add credit_ledger column if not exists
-- ALTER TABLE tblpur_request_types ADD COLUMN credit_ledger INT(11) NULL AFTER ledger_account;

-- Coded Materials: Debit 150000 Inventory Raw Materials, Credit 240000 GR/IR Clearing
UPDATE tblpur_request_types SET ledger_account = (SELECT id FROM tblacc_accounts WHERE number = '150000' LIMIT 1), credit_ledger = (SELECT id FROM tblacc_accounts WHERE number = '240000' LIMIT 1) WHERE type_code = 'CM';

-- Assets: Debit 100012 Capital Work in Progress, Credit 240000 GR/IR Clearing
UPDATE tblpur_request_types SET ledger_account = (SELECT id FROM tblacc_accounts WHERE number = '100012' LIMIT 1), credit_ledger = (SELECT id FROM tblacc_accounts WHERE number = '240000' LIMIT 1) WHERE type_code = 'AS';

-- Consumables: Debit 150004 Inventory Tooling & Consumables, Credit 240000 GR/IR Clearing
UPDATE tblpur_request_types SET ledger_account = (SELECT id FROM tblacc_accounts WHERE number = '150004' LIMIT 1), credit_ledger = (SELECT id FROM tblacc_accounts WHERE number = '240000' LIMIT 1) WHERE type_code = 'CN';

-- Spares: Debit 150003 Inventory Spare Parts, Credit 240000 GR/IR Clearing
UPDATE tblpur_request_types SET ledger_account = (SELECT id FROM tblacc_accounts WHERE number = '150003' LIMIT 1), credit_ledger = (SELECT id FROM tblacc_accounts WHERE number = '240000' LIMIT 1) WHERE type_code = 'SP';

-- Raw Materials: Debit 150000 Inventory Raw Materials, Credit 240000 GR/IR Clearing
UPDATE tblpur_request_types SET ledger_account = (SELECT id FROM tblacc_accounts WHERE number = '150000' LIMIT 1), credit_ledger = (SELECT id FROM tblacc_accounts WHERE number = '240000' LIMIT 1) WHERE type_code = 'RM';

-- Non-Coded Materials: Credit 240000 GR/IR Clearing (Debit = selected during PR creation)
UPDATE tblpur_request_types SET credit_ledger = (SELECT id FROM tblacc_accounts WHERE number = '240000' LIMIT 1) WHERE type_code = 'NC';

-- Service: Credit 240000 GR/IR Clearing (Debit = selected during PR creation)
UPDATE tblpur_request_types SET credit_ledger = (SELECT id FROM tblacc_accounts WHERE number = '240000' LIMIT 1) WHERE type_code = 'FO';
