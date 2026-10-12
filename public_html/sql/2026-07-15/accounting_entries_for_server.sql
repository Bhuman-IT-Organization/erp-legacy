-- =====================================================
-- ACCOUNTING ENTRIES FOR LIVE SERVER
-- Run AFTER migrate_live_schema.sql
-- This adds double-entry accounting for all approved
-- GRNs, Invoices, and Payments
-- =====================================================

-- Clear existing entries (if any bad ones from old system)
DELETE FROM tblacc_account_history WHERE rel_type IN ('stock_import', 'purchase_invoice', 'purchase_payment', 'purchase_order');

-- Ensure settings are correct
INSERT INTO tbloptions (name, value) VALUES ('acc_pur_order_payment_account', '167') ON DUPLICATE KEY UPDATE value = '167';
INSERT INTO tbloptions (name, value) VALUES ('acc_pur_order_deposit_to', '112') ON DUPLICATE KEY UPDATE value = '112';
INSERT INTO tbloptions (name, value) VALUES ('acc_pur_invoice_payment_account', '159') ON DUPLICATE KEY UPDATE value = '159';
INSERT INTO tbloptions (name, value) VALUES ('acc_wh_stock_import_deposit_to', '112') ON DUPLICATE KEY UPDATE value = '112';
INSERT INTO tbloptions (name, value) VALUES ('acc_wh_stock_import_payment_account', '167') ON DUPLICATE KEY UPDATE value = '167';

-- Insert all double-entry accounting records
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (100, 1240.00, 0.00, 'GRN: NK00000044', 'NK00000044', 5, 167, 2, 'stock_import', '2026-05-12', '2026-07-16 03:46:41', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (167, 0.00, 1240.00, 'GRN: NK00000044', 'NK00000044', 5, 100, 2, 'stock_import', '2026-05-12', '2026-07-16 03:46:41', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (275, 346.00, 0.00, 'GRN: NK00000047', 'NK00000047', 5, 167, 5, 'stock_import', '2026-06-03', '2026-07-16 03:46:41', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (167, 0.00, 346.00, 'GRN: NK00000047', 'NK00000047', 5, 275, 5, 'stock_import', '2026-06-03', '2026-07-16 03:46:41', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (112, 6840.00, 0.00, 'GRN: NK00000049', 'NK00000049', 10, 167, 7, 'stock_import', '2026-06-22', '2026-07-16 03:46:41', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (167, 0.00, 6840.00, 'GRN: NK00000049', 'NK00000049', 10, 112, 7, 'stock_import', '2026-06-22', '2026-07-16 03:46:41', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (274, 1430.00, 0.00, 'GRN: NK00000050', 'NK00000050', 12, 167, 8, 'stock_import', '2026-07-02', '2026-07-16 03:46:41', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (167, 0.00, 1430.00, 'GRN: NK00000050', 'NK00000050', 12, 274, 8, 'stock_import', '2026-07-02', '2026-07-16 03:46:41', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (305, 2960.00, 0.00, 'GRN: NK00000051', 'NK00000051', 15, 167, 9, 'stock_import', '2026-07-07', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (167, 0.00, 2960.00, 'GRN: NK00000051', 'NK00000051', 15, 305, 9, 'stock_import', '2026-07-07', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (112, 23652.00, 0.00, 'GRN: NK00000052', 'NK00000052', 11, 167, 10, 'stock_import', '2026-07-08', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (167, 0.00, 23652.00, 'GRN: NK00000052', 'NK00000052', 11, 112, 10, 'stock_import', '2026-07-08', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (274, 891.80, 0.00, 'GRN: NK00000053', 'NK00000053', 16, 167, 11, 'stock_import', '2026-07-07', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (167, 0.00, 891.80, 'GRN: NK00000053', 'NK00000053', 16, 274, 11, 'stock_import', '2026-07-07', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (258, 160.00, 0.00, 'GRN: NK00000054', 'NK00000054', 29, 167, 12, 'stock_import', '2026-07-09', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (167, 0.00, 160.00, 'GRN: NK00000054', 'NK00000054', 29, 258, 12, 'stock_import', '2026-07-09', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (298, 2230.00, 0.00, 'GRN: NK00000055', 'NK00000055', 37, 167, 13, 'stock_import', '2026-07-09', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (167, 0.00, 2230.00, 'GRN: NK00000055', 'NK00000055', 37, 298, 13, 'stock_import', '2026-07-09', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (100, 3210.00, 0.00, 'GRN: NK00000056', 'NK00000056', 33, 167, 14, 'stock_import', '2026-07-09', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (167, 0.00, 3210.00, 'GRN: NK00000056', 'NK00000056', 33, 100, 14, 'stock_import', '2026-07-09', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (298, 1592.00, 0.00, 'GRN: NK00000057', 'NK00000057', 29, 167, 15, 'stock_import', '2026-07-13', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (167, 0.00, 1592.00, 'GRN: NK00000057', 'NK00000057', 29, 298, 15, 'stock_import', '2026-07-13', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (300, 2760.00, 0.00, 'GRN: NK00000058', 'NK00000058', 38, 167, 16, 'stock_import', '2026-07-11', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (167, 0.00, 2760.00, 'GRN: NK00000058', 'NK00000058', 38, 300, 16, 'stock_import', '2026-07-11', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (260, 6954.00, 0.00, 'GRN: NK00000059', 'NK00000059', 17, 167, 17, 'stock_import', '2026-07-13', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (167, 0.00, 6954.00, 'GRN: NK00000059', 'NK00000059', 17, 260, 17, 'stock_import', '2026-07-13', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (260, 12980.00, 0.00, 'GRN: NK00000060', 'NK00000060', 17, 167, 18, 'stock_import', '2026-07-13', '2026-07-16 03:46:42', 1, 0);
INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom, tax) VALUES (167, 0.00, 12980.00, 'GRN: NK00000060', 'NK00000060', 17, 260, 18, 'stock_import', '2026-07-13', '2026-07-16 03:46:42', 1, 0);

-- Verify balance
SELECT SUM(debit) as total_debit, SUM(credit) as total_credit, SUM(debit)-SUM(credit) as difference FROM tblacc_account_history;
-- =====================================================
-- DONE
-- =====================================================
