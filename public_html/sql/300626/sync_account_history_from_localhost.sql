-- Sync tblacc_account_history from localhost (nextgen(9)) to 61 server (nextgen_latest(15))
-- Date: 2026-06-30
--
-- ISSUE: The 61 server account_history entries are pointing to wrong/default account IDs
-- (37=Inventory Asset, 87=Accounts Payable) instead of the custom accounts
-- (112=Inventory Raw Materials 150000, 134=NBF Bank 190010, 166=Post Dated Cheque 237512, 167=Goods Receipt Clearing 240000)
--
-- PREREQUISITE: Accounts 112, 134, 166, 167 must exist on the 61 server.
-- If they don't exist, you need to create them first or import them from localhost.
-- Run this query on 61 server to verify: SELECT id, name, number FROM tblacc_accounts WHERE id IN (112, 134, 166, 167);

-- Fix Purchase Order entries (should debit Inventory Raw Materials, credit GR Clearing)
UPDATE `tblacc_account_history` SET `account` = 112, `split` = 167 WHERE `id` = 61;
UPDATE `tblacc_account_history` SET `account` = 167, `split` = 112 WHERE `id` = 62;

-- Fix Stock Import / Inventory Receiving Voucher entries
UPDATE `tblacc_account_history` SET `account` = 112, `split` = 167 WHERE `id` = 69;
UPDATE `tblacc_account_history` SET `account` = 167, `split` = 112 WHERE `id` = 70;

-- Fix Purchase Payment #1 (1000.00 via Cheque/PDC)
-- Debit AP (87), Credit Post Dated Cheque (166)
UPDATE `tblacc_account_history` SET `account` = 87, `split` = 166 WHERE `id` = 71;
UPDATE `tblacc_account_history` SET `account` = 166, `split` = 87 WHERE `id` = 72;

-- Fix Purchase Payment #2 (302.00 via Bank)
-- Debit AP (87), Credit NBF Bank (134)
UPDATE `tblacc_account_history` SET `account` = 87, `split` = 134 WHERE `id` = 73;
UPDATE `tblacc_account_history` SET `account` = 134, `split` = 87 WHERE `id` = 74;

-- Fix Purchase Payment #3 (7633.50 via Bank)
-- Debit AP (87), Credit NBF Bank (134)
UPDATE `tblacc_account_history` SET `account` = 87, `split` = 134 WHERE `id` = 75;
UPDATE `tblacc_account_history` SET `account` = 134, `split` = 87 WHERE `id` = 76;
