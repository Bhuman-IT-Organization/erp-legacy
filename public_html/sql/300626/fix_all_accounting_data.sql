-- Fix All Accounting Data - Double Entry & Ledger Corrections
-- Date: 2026-06-30
-- Run this on 61 server after uploading PHP files

-- ============================================================
-- 1. ACTIVATE ALL ACCOUNTS USED IN TRANSACTIONS
-- ============================================================
UPDATE `tblacc_accounts` SET `active` = 1 WHERE `id` IN (87, 37, 1, 13, 36, 82, 29);

UPDATE `tblacc_accounts` SET `active` = 1 
WHERE `id` IN (SELECT DISTINCT `account` FROM `tblacc_account_history`) AND `active` = 0;

UPDATE `tblacc_accounts` SET `active` = 1 
WHERE `id` IN (SELECT DISTINCT `split` FROM `tblacc_account_history` WHERE `split` > 0) AND `active` = 0;

-- ============================================================
-- 2. PAYMENT MODE MAPPINGS (Cash/Bank/Cheque -> correct ledgers)
-- ============================================================
DELETE FROM `tblacc_payment_mode_mappings` WHERE `payment_mode_id` IN ('1', '2', '3');

-- Bank (mode 1): Credit NBF Bank (134)
INSERT INTO `tblacc_payment_mode_mappings` 
(`payment_mode_id`, `payment_account`, `deposit_to`, `expense_payment_account`, `expense_deposit_to`, `credit_note_refund_payment_account`, `credit_note_refund_deposit_to`) 
VALUES ('1', 134, 1, 87, 134, 134, 1);

-- Cash (mode 2): Credit Cash In Hand (133)
INSERT INTO `tblacc_payment_mode_mappings` 
(`payment_mode_id`, `payment_account`, `deposit_to`, `expense_payment_account`, `expense_deposit_to`, `credit_note_refund_payment_account`, `credit_note_refund_deposit_to`) 
VALUES ('2', 133, 1, 87, 133, 133, 1);

-- Cheque (mode 3): Credit Post Dated Cheque (166)
INSERT INTO `tblacc_payment_mode_mappings` 
(`payment_mode_id`, `payment_account`, `deposit_to`, `expense_payment_account`, `expense_deposit_to`, `credit_note_refund_payment_account`, `credit_note_refund_deposit_to`) 
VALUES ('3', 166, 1, 87, 166, 166, 1);

-- Enable payment mode mapping
UPDATE `tbloptions` SET `value` = '1' WHERE `name` = 'acc_active_payment_mode_mapping';
INSERT INTO `tbloptions` (`name`, `value`) 
SELECT 'acc_active_payment_mode_mapping', '1' FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `tbloptions` WHERE `name` = 'acc_active_payment_mode_mapping');

-- Enable account numbers display
UPDATE `tbloptions` SET `value` = '1' WHERE `name` = 'acc_enable_account_numbers';
UPDATE `tbloptions` SET `value` = '1' WHERE `name` = 'acc_show_account_numbers';

-- ============================================================
-- 3. FIX EXISTING ACCOUNT HISTORY DATA (correct double-entry)
-- ============================================================

-- Purchase Order (id 61-62): Debit Inventory Raw Materials (112), Credit GR Clearing (167)
UPDATE `tblacc_account_history` SET `account` = 112, `split` = 167 WHERE `id` = 61;
UPDATE `tblacc_account_history` SET `account` = 167, `split` = 112 WHERE `id` = 62;

-- Stock Import/GRN (id 69-70): Debit Inventory Raw Materials (112), Credit GR Clearing (167)
UPDATE `tblacc_account_history` SET `account` = 112, `split` = 167 WHERE `id` = 69;
UPDATE `tblacc_account_history` SET `account` = 167, `split` = 112 WHERE `id` = 70;

-- Purchase Payment #1 - Cheque (id 71-72): Debit AP (87), Credit PDC (166)
UPDATE `tblacc_account_history` SET `account` = 87, `split` = 166, `description` = 'Bluechip Computer Systems LLC' WHERE `id` = 71;
UPDATE `tblacc_account_history` SET `account` = 166, `split` = 87, `description` = 'Bluechip Computer Systems LLC' WHERE `id` = 72;

-- Purchase Payment #2 - Cash (id 73-74): Debit AP (87), Credit Cash In Hand (133)
UPDATE `tblacc_account_history` SET `account` = 87, `split` = 133, `description` = 'Bluechip Computer Systems LLC' WHERE `id` = 73;
UPDATE `tblacc_account_history` SET `account` = 133, `split` = 87, `description` = 'Bluechip Computer Systems LLC' WHERE `id` = 74;

-- Purchase Payment #3 - Bank (id 75-76): Debit AP (87), Credit NBF Bank (134)
UPDATE `tblacc_account_history` SET `account` = 87, `split` = 134, `description` = 'Bluechip Computer Systems LLC' WHERE `id` = 75;
UPDATE `tblacc_account_history` SET `account` = 134, `split` = 87, `description` = 'Bluechip Computer Systems LLC' WHERE `id` = 76;

-- Purchase Payment #4 - Cash (id 77-78): Debit AP (87), Credit Cash In Hand (133)
UPDATE `tblacc_account_history` SET `account` = 87, `split` = 133, `description` = 'Bluechip Computer Systems LLC' WHERE `id` = 77;
UPDATE `tblacc_account_history` SET `account` = 133, `split` = 87, `description` = 'Bluechip Computer Systems LLC' WHERE `id` = 78;
