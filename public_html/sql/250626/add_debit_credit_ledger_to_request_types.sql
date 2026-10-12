-- Add credit_ledger column to tblpur_request_types
-- ledger_account = GL Account Debit (already exists)
-- credit_ledger = GL Account Credit (new)
ALTER TABLE tblpur_request_types 
ADD COLUMN credit_ledger INT(11) NULL AFTER ledger_account;
