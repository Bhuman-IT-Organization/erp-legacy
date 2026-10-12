-- Add credit_gl_account column to tblexpenses
-- This allows expenses without customer/vendor to specify a credit GL account directly
ALTER TABLE tblexpenses ADD COLUMN credit_gl_account INT(11) DEFAULT NULL AFTER ledger_account;
