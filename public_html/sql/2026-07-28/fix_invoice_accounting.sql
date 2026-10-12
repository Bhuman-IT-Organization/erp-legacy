-- Fix purchase invoice accounting entries
-- Correct posting: Dr GR/IR (167) / Cr Vendor AP + Dr VAT Paid (124) / Cr Vendor AP
-- Vendor AP = vendor's ledger_account from tblpur_vendor

-- Clear any existing purchase_invoice entries
DELETE FROM tblacc_account_history WHERE rel_type = 'purchase_invoice';

-- INV#1 (id=1): INV00021, vendor=6 (Meridian), ledger=164, subtotal=1038.84, tax=51.94, date=2026-06-02
-- Dr GR/IR Clearing
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom)
VALUES (167, 164, 1038.84, 0, 'INV: INV00021', 'INV00021', 6, 1, 'purchase_invoice', '2026-06-02', NOW(), 1);
-- Cr Vendor AP
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom)
VALUES (164, 167, 0, 1038.84, 'INV: INV00021', 'INV00021', 6, 1, 'purchase_invoice', '2026-06-02', NOW(), 1);
-- Dr VAT Paid
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom, tax)
VALUES (124, 164, 51.94, 0, 'INV: INV00021', 'INV00021', 6, 1, 'purchase_invoice', '2026-06-02', NOW(), 1, 1);
-- Cr Vendor AP (VAT)
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom, tax)
VALUES (164, 124, 0, 51.94, 'INV: INV00021', 'INV00021', 6, 1, 'purchase_invoice', '2026-06-02', NOW(), 1, 1);

-- INV#2 (id=2): INV00022, vendor=11 (TRICE), ledger=159, subtotal=23652.00, tax=1182.60, date=2026-07-08
-- Dr GR/IR Clearing
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom)
VALUES (167, 159, 23652.00, 0, 'INV: INV00022', 'INV00022', 11, 2, 'purchase_invoice', '2026-07-08', NOW(), 1);
-- Cr Vendor AP
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom)
VALUES (159, 167, 0, 23652.00, 'INV: INV00022', 'INV00022', 11, 2, 'purchase_invoice', '2026-07-08', NOW(), 1);
-- Dr VAT Paid
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom, tax)
VALUES (124, 159, 1182.60, 0, 'INV: INV00022', 'INV00022', 11, 2, 'purchase_invoice', '2026-07-08', NOW(), 1, 1);
-- Cr Vendor AP (VAT)
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom, tax)
VALUES (159, 124, 0, 1182.60, 'INV: INV00022', 'INV00022', 11, 2, 'purchase_invoice', '2026-07-08', NOW(), 1, 1);

-- INV#3 (id=3): INV00023, vendor=7 (Amna), ledger=162, subtotal=6350.00, tax=317.50, date=2026-06-05
-- Dr GR/IR Clearing
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom)
VALUES (167, 162, 6350.00, 0, 'INV: INV00023', 'INV00023', 7, 3, 'purchase_invoice', '2026-06-05', NOW(), 1);
-- Cr Vendor AP
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom)
VALUES (162, 167, 0, 6350.00, 'INV: INV00023', 'INV00023', 7, 3, 'purchase_invoice', '2026-06-05', NOW(), 1);
-- Dr VAT Paid
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom, tax)
VALUES (124, 162, 317.50, 0, 'INV: INV00023', 'INV00023', 7, 3, 'purchase_invoice', '2026-06-05', NOW(), 1, 1);
-- Cr Vendor AP (VAT)
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom, tax)
VALUES (162, 124, 0, 317.50, 'INV: INV00023', 'INV00023', 7, 3, 'purchase_invoice', '2026-06-05', NOW(), 1, 1);

-- INV#4 (id=4): INV00024, vendor=15 (AL HIKAM), ledger=164, subtotal=781.00, tax=39.05, date=2026-07-15
-- Dr GR/IR Clearing
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom)
VALUES (167, 164, 781.00, 0, 'INV: INV00024', 'INV00024', 15, 4, 'purchase_invoice', '2026-07-15', NOW(), 1);
-- Cr Vendor AP
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom)
VALUES (164, 167, 0, 781.00, 'INV: INV00024', 'INV00024', 15, 4, 'purchase_invoice', '2026-07-15', NOW(), 1);
-- Dr VAT Paid
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom, tax)
VALUES (124, 164, 39.05, 0, 'INV: INV00024', 'INV00024', 15, 4, 'purchase_invoice', '2026-07-15', NOW(), 1, 1);
-- Cr Vendor AP (VAT)
INSERT INTO tblacc_account_history (account, split, debit, credit, description, number, vendor, rel_id, rel_type, date, datecreated, addedfrom, tax)
VALUES (164, 124, 0, 39.05, 'INV: INV00024', 'INV00024', 15, 4, 'purchase_invoice', '2026-07-15', NOW(), 1, 1);

-- Mark invoices as mapped
UPDATE tblpur_invoices SET acc_mapping = 1 WHERE id IN (1, 2, 3, 4);
