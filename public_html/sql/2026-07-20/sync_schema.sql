-- Schema sync: Add missing columns from master (nextgen_latest150726) to inova and live
-- Date: 2026-07-20
-- Run on both nextgen_inova and nextgen_live databases

ALTER TABLE `tblinvoicepaymentrecords` ADD COLUMN `gl_account` int(11) DEFAULT NULL;
ALTER TABLE `tblpur_order_payment` ADD COLUMN `gl_account` int(11) DEFAULT NULL;
