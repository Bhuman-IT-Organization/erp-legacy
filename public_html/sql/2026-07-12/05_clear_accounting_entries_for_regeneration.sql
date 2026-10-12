-- Clear existing accounting entries to regenerate with proper details (number, vendor, description)
-- After running this, re-approve POs and GRNs to regenerate entries with full details
-- Or trigger the automatic conversion again

DELETE FROM tblacc_account_history WHERE rel_type IN ('stock_import', 'purchase_order', 'purchase_invoice', 'purchase_payment');

-- Also reset acc_mapping flag on invoices so they get re-converted
UPDATE tblpur_invoices SET acc_mapping = 0 WHERE acc_mapping = 1;
