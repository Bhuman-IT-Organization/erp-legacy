-- Add grn_id column to purchase invoices table to store linked GRN IDs
ALTER TABLE `tblpur_invoices` ADD COLUMN `grn_id` TEXT DEFAULT NULL;
