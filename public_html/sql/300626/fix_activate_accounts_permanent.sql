-- PERMANENT FIX: Activate all accounts used in transactions
-- Run this on BOTH localhost and 61 server
-- Date: 2026-06-30
--
-- This MUST be run every time after generate_accounting_entries_live.php 
-- or any bulk accounting entry generation script is executed.

-- Activate all accounts that have entries in account_history
UPDATE `tblacc_accounts` SET `active` = 1 
WHERE `id` IN (SELECT DISTINCT `account` FROM `tblacc_account_history`) AND `active` = 0;

-- Activate all accounts used as split (contra) accounts
UPDATE `tblacc_accounts` SET `active` = 1 
WHERE `id` IN (SELECT DISTINCT `split` FROM `tblacc_account_history` WHERE `split` > 0) AND `active` = 0;

-- Verify trial balance is balanced
SELECT 
  SUM(debit) as total_debit, 
  SUM(credit) as total_credit, 
  SUM(debit) - SUM(credit) as difference 
FROM `tblacc_account_history`;
