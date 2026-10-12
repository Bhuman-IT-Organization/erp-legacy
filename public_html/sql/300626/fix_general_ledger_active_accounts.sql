-- Fix: General Ledger not showing records
-- Date: 2026-06-30
--
-- ISSUE 1: The general ledger report filters by `active = 1` on the acc_accounts table.
-- Default accounts (IDs 1-87) have `active = 0`, so any account_history entries
-- pointing to those accounts are invisible in the general ledger and other reports.
--
-- ISSUE 2: The general ledger view was only rendering 6 out of 15 account categories.
-- Missing: accounts_receivable, cash_and_cash_equivalents, fixed_assets, 
-- non_current_assets, accounts_payable, non_current_liabilities, other_income, 
-- cost_of_sales, other_expenses. (Fixed in PHP view file)
--
-- FIX: Activate all accounts that are actually used in transactions.

-- Activate key accounts used in the system
UPDATE `tblacc_accounts` SET `active` = 1 WHERE `id` = 87;  -- Accounts Payable (2100)
UPDATE `tblacc_accounts` SET `active` = 1 WHERE `id` = 37;  -- Inventory Asset (1301)
UPDATE `tblacc_accounts` SET `active` = 1 WHERE `id` = 1;   -- Accounts Receivable (1100)
UPDATE `tblacc_accounts` SET `active` = 1 WHERE `id` = 13;  -- Cash and Cash Equivalents (1010)
UPDATE `tblacc_accounts` SET `active` = 1 WHERE `id` = 36;  -- Inventory (1300)
UPDATE `tblacc_accounts` SET `active` = 1 WHERE `id` = 82;  -- Undeposited Funds (1050)
UPDATE `tblacc_accounts` SET `active` = 1 WHERE `id` = 29;  -- VAT Payable (2400)

-- Activate any account that has transactions pointing to it
UPDATE `tblacc_accounts` SET `active` = 1 
WHERE `id` IN (
    SELECT DISTINCT `account` FROM `tblacc_account_history`
) AND `active` = 0;

-- Also activate accounts used as split targets
UPDATE `tblacc_accounts` SET `active` = 1 
WHERE `id` IN (
    SELECT DISTINCT `split` FROM `tblacc_account_history` WHERE `split` > 0
) AND `active` = 0;
