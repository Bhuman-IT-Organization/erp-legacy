-- Add ledger_account column to sales credit notes
-- This stores the GL account selected when creating a credit note
-- Used during payment/application process to post to the correct ledger

ALTER TABLE tblcreditnotes ADD COLUMN ledger_account INT(11) DEFAULT NULL;
