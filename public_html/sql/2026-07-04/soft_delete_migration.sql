-- ============================================================
-- SOFT DELETE MIGRATION
-- Convert hard deletes to soft deletes across all modules
-- ============================================================

-- Add is_deleted and date_deleted columns where missing

-- Expenses table
ALTER TABLE tblexpenses ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE tblexpenses ADD COLUMN IF NOT EXISTS date_deleted DATETIME NULL DEFAULT NULL;

-- Sales invoices (auto-migrated in code but ensure exists)
ALTER TABLE tblinvoices ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE tblinvoices ADD COLUMN IF NOT EXISTS date_deleted DATETIME NULL DEFAULT NULL;

-- Goods delivery (auto-migrated in code but ensure exists)
ALTER TABLE tblgoods_delivery ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE tblgoods_delivery ADD COLUMN IF NOT EXISTS date_deleted DATETIME NULL DEFAULT NULL;

-- Purchase invoices (already has is_deleted)
-- tblpur_invoices - already has is_deleted column

-- Purchase orders (already has is_deleted)
-- tblpur_orders - already has is_deleted column

-- Goods receipt (already has is_deleted)
-- tblgoods_receipt - already has is_deleted column

-- Purchase payments (already has is_deleted)
-- tblpur_invoice_payment - already has is_deleted column

-- Sales payments
ALTER TABLE tblinvoicepaymentrecords ADD COLUMN IF NOT EXISTS is_deleted TINYINT(1) NOT NULL DEFAULT 0;

-- ============================================================
-- SUMMARY OF SOFT DELETE BEHAVIOR
-- ============================================================
-- Entity              | Table                    | is_deleted | Reverses Accounting
-- -------------------|--------------------------|------------|--------------------
-- Purchase Invoice   | tblpur_invoices          | YES        | YES (hook)
-- Purchase Order     | tblpur_orders            | YES        | YES (hook)
-- GRN               | tblgoods_receipt          | YES        | YES (hook)
-- Goods Delivery    | tblgoods_delivery         | YES        | YES (hook)
-- Sales Invoice     | tblinvoices               | YES        | YES (hook)
-- Expenses          | tblexpenses               | YES        | YES (hook)
-- Purchase Payment  | tblpur_invoice_payment    | YES        | YES (hook)
-- Sales Payment     | tblinvoicepaymentrecords  | YES        | YES (hook)
-- ============================================================
