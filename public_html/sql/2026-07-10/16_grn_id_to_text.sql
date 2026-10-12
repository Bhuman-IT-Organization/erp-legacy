-- Change grn_id column to TEXT to store multiple GRN IDs as JSON array
ALTER TABLE tblwh_order_returns MODIFY COLUMN grn_id TEXT DEFAULT NULL;
