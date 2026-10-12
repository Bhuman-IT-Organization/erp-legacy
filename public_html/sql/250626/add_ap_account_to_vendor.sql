-- Add ap_account column to tblpur_vendor for vendor-specific AP account mapping
ALTER TABLE tblpur_vendor ADD COLUMN ap_account INT(11) NULL DEFAULT NULL;

-- Default all vendors to general Accounts Payable (2100)
-- UPDATE tblpur_vendor SET ap_account = 87 WHERE ap_account IS NULL;
