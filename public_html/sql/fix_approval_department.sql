-- =====================================================
-- Add department column to approval setting table
-- This stores which department the approval setting applies to
-- =====================================================

ALTER TABLE `tblpur_approval_setting` ADD COLUMN `department` INT NULL DEFAULT NULL AFTER `related`;
