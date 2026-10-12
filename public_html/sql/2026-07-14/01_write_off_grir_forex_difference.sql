-- Write off GR/IR Clearing forex rounding difference (18.34)
-- Dr 167 (GR/IR Clearing) to zero it out
-- Cr 328 (Service Charges) to absorb the difference

INSERT INTO tblacc_account_history (account, debit, credit, description, number, vendor, split, rel_id, rel_type, date, datecreated, addedfrom) VALUES 
(167, 18.34, 0, 'Forex rounding write-off - GR/IR Clearing', 'WO-001', 0, 328, 0, 'journal', '2026-07-14', NOW(), 1),
(328, 0, 18.34, 'Forex rounding write-off - GR/IR Clearing', 'WO-001', 0, 167, 0, 'journal', '2026-07-14', NOW(), 1);
