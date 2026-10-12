-- ============================================================
-- FIX GRN #27 (NK00000069) - Missing Accounting Entries
-- PO #43, Type FO, Ledger: 275 (420027 IT Expenses - Factory)
-- Run on LIVE SERVER
-- ============================================================

-- Delete any existing entries for GRN 27
DELETE FROM `tblacc_account_history` WHERE `rel_type` = 'stock_import' AND `rel_id` = 27;

-- GRN #27: Dr IT Expenses (275), Cr GR/IR Clearing (167) = 1038.84
INSERT INTO `tblacc_account_history` (`account`, `split`, `debit`, `credit`, `description`, `number`, `vendor`, `rel_id`, `rel_type`, `date`, `datecreated`, `addedfrom`)
VALUES (275, 167, 1038.84, 0, 'GRN: NK00000069', 'NK00000069', 6, 27, 'stock_import', '2026-06-02', NOW(), 1);

INSERT INTO `tblacc_account_history` (`account`, `split`, `debit`, `credit`, `description`, `number`, `vendor`, `rel_id`, `rel_type`, `date`, `datecreated`, `addedfrom`)
VALUES (167, 275, 0, 1038.84, 'GRN: NK00000069', 'NK00000069', 6, 27, 'stock_import', '2026-06-02', NOW(), 1);

-- Mark GRN as mapped
UPDATE `tblgoods_receipt` SET `acc_mapping` = 1 WHERE `id` = 27;

-- ============================================================
-- RESULT:
-- 420027 IT Expenses:     Dr 1,038.84 (expense recognized at GRN)
-- 240000 GR/IR Clearing:  Cr 1,038.84 (GRN) + Dr 1,038.84 (Invoice) = net 0
-- 230000 A/P Vendor:      Cr 1,090.78 (invoice total incl VAT)
-- 179020 VAT Paid:        Dr 51.94 (input tax)
-- ============================================================
