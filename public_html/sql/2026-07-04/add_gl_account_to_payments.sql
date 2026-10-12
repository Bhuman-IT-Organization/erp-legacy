-- Add GL Account field to payment tables for user-selected ledger account
-- This allows users to specify which bank/cash account is debited/credited

-- Purchase invoice payments
ALTER TABLE tblpur_invoice_payment ADD COLUMN IF NOT EXISTS gl_account INT(11) DEFAULT NULL AFTER paymentmode;

-- Purchase order payments
ALTER TABLE tblpur_order_payment ADD COLUMN IF NOT EXISTS gl_account INT(11) DEFAULT NULL AFTER paymentmode;

-- Sales invoice payments
ALTER TABLE tblinvoicepaymentrecords ADD COLUMN IF NOT EXISTS gl_account INT(11) DEFAULT NULL AFTER paymentmode;
