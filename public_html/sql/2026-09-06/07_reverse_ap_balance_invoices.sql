-- ---------------------------------------------------------------------------
-- Undo step 06
-- ---------------------------------------------------------------------------
--
-- Removes every invoice record created from the AP opening balance sheet and
-- nothing else. The 'OB' prefix is what identifies them; real purchase invoices
-- use the 'INV' prefix from the pur_inv_prefix option, so there is no overlap.
--
-- Run this if the numbers need reworking, then re-run 06.
--
-- tblpur_vendor_opening_balance and the staging table are left alone - this only
-- undoes the invoice records, not the imported data.
-- ---------------------------------------------------------------------------

-- Look before you delete.
SELECT COUNT(*) AS invoices_to_be_removed,
       ROUND(SUM(`total`), 2) AS total_aed
FROM `tblpur_invoices`
WHERE `invoice_number` LIKE 'OB%';


-- Nothing else references these rows: they were inserted with no purchase order,
-- no GRN, no payments and acc_mapping = 0. These two deletes are here in case
-- somebody recorded a payment against one by hand.
DELETE FROM `tblpur_invoice_payment`
WHERE `pur_invoice` IN (
    SELECT `id` FROM `tblpur_invoices` WHERE `invoice_number` LIKE 'OB%'
);

DELETE FROM `tblitemable`
WHERE `rel_type` = 'pur_invoice'
  AND `rel_id` IN (
    SELECT `id` FROM `tblpur_invoices` WHERE `invoice_number` LIKE 'OB%'
);

DELETE FROM `tblpur_invoices`
WHERE `invoice_number` LIKE 'OB%';


-- Expect 0.
SELECT COUNT(*) AS invoices_left
FROM `tblpur_invoices`
WHERE `invoice_number` LIKE 'OB%';
