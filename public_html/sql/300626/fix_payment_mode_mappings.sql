-- Fix: Payment mode mappings for proper double-entry accounting
-- Date: 2026-06-30
--
-- ISSUE: Payment mode mappings table is empty. When a purchase invoice is paid by Cash,
-- the system uses the generic fallback account (acc_pur_payment_deposit_to) instead of
-- the Cash-in-Hand ledger. This means cash payments never reflect in Cash-in-Hand.
--
-- Payment Modes: 1=Bank, 2=Cash, 3=Cheque
-- 
-- For Purchase Payments (expense side):
--   Debit:  Accounts Payable (87) - reduces what we owe vendor
--   Credit: Cash/Bank/Cheque account - reduces our cash/bank balance
--
-- For Sales Receipts (payment side):
--   Debit:  Cash/Bank/Cheque account - increases our cash/bank balance
--   Credit: Accounts Receivable (1) - reduces what customer owes us

-- Clear any existing mappings (if any)
DELETE FROM `tblacc_payment_mode_mappings` WHERE `payment_mode_id` IN ('1', '2', '3');

-- Bank (payment_mode_id = 1)
-- For sales: Debit Bank (134), Credit AR (1)
-- For purchase: Debit AP (87), Credit Bank (134)
INSERT INTO `tblacc_payment_mode_mappings` 
(`payment_mode_id`, `payment_account`, `deposit_to`, `expense_payment_account`, `expense_deposit_to`, `credit_note_refund_payment_account`, `credit_note_refund_deposit_to`) 
VALUES ('1', 134, 1, 87, 134, 134, 1);

-- Cash (payment_mode_id = 2)
-- For sales: Debit Cash In Hand (133), Credit AR (1)
-- For purchase: Debit AP (87), Credit Cash In Hand (133)
INSERT INTO `tblacc_payment_mode_mappings` 
(`payment_mode_id`, `payment_account`, `deposit_to`, `expense_payment_account`, `expense_deposit_to`, `credit_note_refund_payment_account`, `credit_note_refund_deposit_to`) 
VALUES ('2', 133, 1, 87, 133, 133, 1);

-- Cheque (payment_mode_id = 3)
-- For sales: Debit Post Dated Cheque (166), Credit AR (1)
-- For purchase: Debit AP (87), Credit Post Dated Cheque (166)
INSERT INTO `tblacc_payment_mode_mappings` 
(`payment_mode_id`, `payment_account`, `deposit_to`, `expense_payment_account`, `expense_deposit_to`, `credit_note_refund_payment_account`, `credit_note_refund_deposit_to`) 
VALUES ('3', 166, 1, 87, 166, 166, 1);

-- Ensure payment mode mapping is active
UPDATE `tbloptions` SET `value` = '1' WHERE `name` = 'acc_active_payment_mode_mapping';
INSERT INTO `tbloptions` (`name`, `value`) 
SELECT 'acc_active_payment_mode_mapping', '1' FROM DUAL 
WHERE NOT EXISTS (SELECT 1 FROM `tbloptions` WHERE `name` = 'acc_active_payment_mode_mapping');
