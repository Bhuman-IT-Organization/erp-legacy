-- Regenerate ALL GRN Accounting Entries
-- Logic: Dr = PR type ledger_account (material/expense) | Cr = 240000 GR/IR Clearing (account ID 167)
-- Run on live servers after verifying on dev
-- 
-- NOTE: This SQL version handles simple cases. For complex PR type lookups,
-- use the PHP script version on each server.

-- Step 1: Delete all existing GRN accounting entries
DELETE FROM `tblacc_account_history` WHERE `rel_type` = 'stock_import';

-- Step 2: Mark all GRNs as not yet mapped (so the hook will recreate them)
UPDATE `tblgoods_receipt` SET `acc_mapping` = 0 WHERE `approval` = 1 AND `is_deleted` = 0;

-- After running these queries, trigger re-mapping by visiting each GRN
-- or use the PHP regeneration script for bulk processing.
-- The automatic_stock_import_conversion function will create correct entries
-- using the priority: PR Type Ledger > PO Ledger > PR Own Ledger > Default

