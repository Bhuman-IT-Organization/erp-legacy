-- Add ledger_account column to purchase debit notes
-- This stores the GL account selected when creating a debit note
-- Used during payment process to post to the correct ledger

ALTER TABLE tblpur_debit_notes ADD COLUMN ledger_account INT(11) DEFAULT NULL AFTER reference_no;
