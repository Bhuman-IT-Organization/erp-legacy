-- Add new columns to existing tables

-- GL Account on payment tables
ALTER TABLE tblpur_invoice_payment ADD COLUMN IF NOT EXISTS gl_account INT(11) DEFAULT NULL;
ALTER TABLE tblpur_order_payment ADD COLUMN IF NOT EXISTS gl_account INT(11) DEFAULT NULL;
ALTER TABLE tblinvoicepaymentrecords ADD COLUMN IF NOT EXISTS gl_account INT(11) DEFAULT NULL;

-- Soft delete columns
ALTER TABLE tblexpenses ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE tblexpenses ADD COLUMN IF NOT EXISTS date_deleted DATETIME NULL DEFAULT NULL;
ALTER TABLE tblinvoices ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE tblinvoices ADD COLUMN IF NOT EXISTS date_deleted DATETIME NULL DEFAULT NULL;
ALTER TABLE tblgoods_delivery ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE tblgoods_delivery ADD COLUMN IF NOT EXISTS date_deleted DATETIME NULL DEFAULT NULL;
ALTER TABLE tblinvoicepaymentrecords ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;

-- Sales invoice references
ALTER TABLE tblinvoices ADD COLUMN IF NOT EXISTS sales_order_id INT(11) DEFAULT NULL;
ALTER TABLE tblinvoices ADD COLUMN IF NOT EXISTS delivery_voucher_ids TEXT DEFAULT NULL;

-- Purchase return GRN reference
ALTER TABLE tblwh_order_returns ADD COLUMN IF NOT EXISTS grn_id INT(11) DEFAULT NULL AFTER rel_id;
