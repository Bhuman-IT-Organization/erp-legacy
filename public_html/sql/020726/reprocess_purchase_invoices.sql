-- Reprocess Purchase Invoices that were approved but not converted to accounting
-- Each invoice: Dr GR/IR Clearing (subtotal), Dr VAT Input (vat), Cr AP (total)

-- INV00021: subtotal=1240, vat=62, total=1302
INSERT INTO tblacc_account_history (account, debit, credit, split, rel_id, rel_type, date, datecreated, addedfrom, description) VALUES
(167, 1240.00, 0, 87, 1, 'purchase_invoice', '2026-06-29', NOW(), 1, ''),
(87, 0, 1240.00, 167, 1, 'purchase_invoice', '2026-06-29', NOW(), 1, ''),
(356, 62.00, 0, 87, 1, 'purchase_invoice', '2026-06-29', NOW(), 1, ''),
(87, 0, 62.00, 356, 1, 'purchase_invoice', '2026-06-29', NOW(), 1, '');

-- INV00022: subtotal=7270, vat=363.50, total=7633.50
INSERT INTO tblacc_account_history (account, debit, credit, split, rel_id, rel_type, date, datecreated, addedfrom, description) VALUES
(167, 7270.00, 0, 87, 2, 'purchase_invoice', '2026-06-29', NOW(), 1, ''),
(87, 0, 7270.00, 167, 2, 'purchase_invoice', '2026-06-29', NOW(), 1, ''),
(356, 363.50, 0, 87, 2, 'purchase_invoice', '2026-06-29', NOW(), 1, ''),
(87, 0, 363.50, 356, 2, 'purchase_invoice', '2026-06-29', NOW(), 1, '');

-- INV00023: subtotal=346, vat=17.30, total=363.30
INSERT INTO tblacc_account_history (account, debit, credit, split, rel_id, rel_type, date, datecreated, addedfrom, description) VALUES
(167, 346.00, 0, 87, 3, 'purchase_invoice', '2026-06-30', NOW(), 1, ''),
(87, 0, 346.00, 167, 3, 'purchase_invoice', '2026-06-30', NOW(), 1, ''),
(356, 17.30, 0, 87, 3, 'purchase_invoice', '2026-06-30', NOW(), 1, ''),
(87, 0, 17.30, 356, 3, 'purchase_invoice', '2026-06-30', NOW(), 1, '');

-- INV00024: subtotal=5500, vat=275, total=5775
INSERT INTO tblacc_account_history (account, debit, credit, split, rel_id, rel_type, date, datecreated, addedfrom, description) VALUES
(167, 5500.00, 0, 87, 4, 'purchase_invoice', '2026-07-01', NOW(), 1, ''),
(87, 0, 5500.00, 167, 4, 'purchase_invoice', '2026-07-01', NOW(), 1, ''),
(356, 275.00, 0, 87, 4, 'purchase_invoice', '2026-07-01', NOW(), 1, ''),
(87, 0, 275.00, 356, 4, 'purchase_invoice', '2026-07-01', NOW(), 1, '');

-- INV00025: subtotal=40000, vat=2000, total=42000
INSERT INTO tblacc_account_history (account, debit, credit, split, rel_id, rel_type, date, datecreated, addedfrom, description) VALUES
(167, 40000.00, 0, 87, 5, 'purchase_invoice', '2026-07-02', NOW(), 1, ''),
(87, 0, 40000.00, 167, 5, 'purchase_invoice', '2026-07-02', NOW(), 1, ''),
(356, 2000.00, 0, 87, 5, 'purchase_invoice', '2026-07-02', NOW(), 1, ''),
(87, 0, 2000.00, 356, 5, 'purchase_invoice', '2026-07-02', NOW(), 1, '');

-- INV00026: subtotal=16000, vat=800, total=16800
INSERT INTO tblacc_account_history (account, debit, credit, split, rel_id, rel_type, date, datecreated, addedfrom, description) VALUES
(167, 16000.00, 0, 87, 6, 'purchase_invoice', '2026-07-02', NOW(), 1, ''),
(87, 0, 16000.00, 167, 6, 'purchase_invoice', '2026-07-02', NOW(), 1, ''),
(356, 800.00, 0, 87, 6, 'purchase_invoice', '2026-07-02', NOW(), 1, ''),
(87, 0, 800.00, 356, 6, 'purchase_invoice', '2026-07-02', NOW(), 1, '');

-- Mark invoices as converted
UPDATE tblpur_invoices SET acc_mapping = 1 WHERE id IN (1,2,3,4,5,6) AND acc_mapping = 0;
