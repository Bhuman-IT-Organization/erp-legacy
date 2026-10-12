-- ============================================================================
-- TRUNCATE ALL TRANSACTIONAL DATA
-- Keeps: Admin user (chatlaerp@gmail.com), Accounting chart of accounts & settings
-- Removes: All purchase, warehouse, sales, HR, quality, and other transactional data
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ═══════════════════════════════════════════════════════════════════════════════
-- STAFF: Keep only admin (staffid=1, chatlaerp@gmail.com), remove others
-- ═══════════════════════════════════════════════════════════════════════════════
DELETE FROM tblstaff WHERE staffid != 1;
DELETE FROM tblstaff_departments WHERE staffid != 1;
DELETE FROM tblstaff_permissions WHERE staff_id != 1;

-- ═══════════════════════════════════════════════════════════════════════════════
-- PURCHASE MODULE - Truncate all
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblpur_request;
TRUNCATE TABLE tblpur_request_detail;
TRUNCATE TABLE tblpur_orders;
TRUNCATE TABLE tblpur_order_detail;
TRUNCATE TABLE tblpur_invoices;
TRUNCATE TABLE tblpur_invoice_details;
TRUNCATE TABLE tblpur_invoice_payment;
TRUNCATE TABLE tblpur_order_payment;
TRUNCATE TABLE tblpur_approval_details;
TRUNCATE TABLE tblpur_contracts;
TRUNCATE TABLE tblpur_estimates;
TRUNCATE TABLE tblpur_estimate_detail;

-- Reset purchase number sequences
UPDATE tblpurchase_option SET option_val = '1' WHERE option_name = 'next_pr_number';
UPDATE tblpurchase_option SET option_val = '1' WHERE option_name = 'next_po_number';
UPDATE tblpurchase_option SET option_val = '1' WHERE option_name = 'next_inv_number';
UPDATE tblpurchase_option SET option_val = '1' WHERE option_name = 'next_est_number';

-- ═══════════════════════════════════════════════════════════════════════════════
-- WAREHOUSE / INVENTORY MODULE - Truncate all
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblgoods_receipt;
TRUNCATE TABLE tblgoods_receipt_detail;
TRUNCATE TABLE tblgoods_receipt_history;
TRUNCATE TABLE tblgoods_receipt_history_detail;
TRUNCATE TABLE tblgoods_delivery;
TRUNCATE TABLE tblgoods_delivery_detail;
TRUNCATE TABLE tblgoods_delivery_batch;
TRUNCATE TABLE tblgoods_delivery_invoices_pr_orders;
TRUNCATE TABLE tblgoods_transaction_detail;
TRUNCATE TABLE tblinventory_manage;
TRUNCATE TABLE tblinventory_commodity_min;
TRUNCATE TABLE tblwh_approval_details;
TRUNCATE TABLE tblwh_loss_adjustment;
TRUNCATE TABLE tblwh_loss_adjustment_detail;
TRUNCATE TABLE tblinternal_delivery_note;
TRUNCATE TABLE tblinternal_delivery_note_detail;
TRUNCATE TABLE tblwh_inventory_serial_numbers;

-- ═══════════════════════════════════════════════════════════════════════════════
-- ITEMS (Products) - Truncate all
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblitems;
TRUNCATE TABLE tblitemable;
TRUNCATE TABLE tblitem_tax;

-- ═══════════════════════════════════════════════════════════════════════════════
-- SALES MODULE - Truncate all
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblestimates;
TRUNCATE TABLE tblinvoices;
TRUNCATE TABLE tblinvoicepaymentrecords;
TRUNCATE TABLE tblcreditnotes;
TRUNCATE TABLE tblcredits;
TRUNCATE TABLE tblproposals;
TRUNCATE TABLE tblsubscriptions;

-- ═══════════════════════════════════════════════════════════════════════════════
-- CUSTOMERS / CLIENTS
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblclients;
TRUNCATE TABLE tblcontacts;
TRUNCATE TABLE tblcustomer_groups;
TRUNCATE TABLE tblcustomer_admins;

-- ═══════════════════════════════════════════════════════════════════════════════
-- PROJECTS & TASKS
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblprojects;
TRUNCATE TABLE tblproject_files;
TRUNCATE TABLE tblproject_members;
TRUNCATE TABLE tblproject_notes;
TRUNCATE TABLE tblproject_activity;
TRUNCATE TABLE tbltasks;
TRUNCATE TABLE tbltask_assigned;
TRUNCATE TABLE tbltask_comments;
TRUNCATE TABLE tbltask_checklist_items;
TRUNCATE TABLE tbltask_followers;

-- ═══════════════════════════════════════════════════════════════════════════════
-- TICKETS / SUPPORT
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tbltickets;
TRUNCATE TABLE tblticket_replies;
TRUNCATE TABLE tblticket_attachments;

-- ═══════════════════════════════════════════════════════════════════════════════
-- LEADS
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblleads;
TRUNCATE TABLE tblleads_email_integration;

-- ═══════════════════════════════════════════════════════════════════════════════
-- EXPENSES
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblexpenses;

-- ═══════════════════════════════════════════════════════════════════════════════
-- CONTRACTS
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblcontracts;

-- ═══════════════════════════════════════════════════════════════════════════════
-- FILES / ATTACHMENTS
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblfiles;

-- ═══════════════════════════════════════════════════════════════════════════════
-- NOTIFICATIONS & ACTIVITY LOG
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblnotifications;
TRUNCATE TABLE tblactivity_log;
TRUNCATE TABLE tbltodos;
TRUNCATE TABLE tblreminders;
TRUNCATE TABLE tblnewsfeed_posts;
TRUNCATE TABLE tblnewsfeed_post_likes;
TRUNCATE TABLE tblnewsfeed_post_comments;
TRUNCATE TABLE tblnewsfeed_comment_likes;

-- ═══════════════════════════════════════════════════════════════════════════════
-- SESSIONS & LOGS
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblsessions;
TRUNCATE TABLE tbluser_auto_login;

-- ═══════════════════════════════════════════════════════════════════════════════
-- HR / PAYROLL / TIMESHEETS (if tables exist)
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblhrp_biometric_log;
TRUNCATE TABLE tblhrp_employees_timesheets;
TRUNCATE TABLE tbltimesheets_timesheet;
TRUNCATE TABLE tbltimesheets_timekeeper_data;
TRUNCATE TABLE tblcheck_in_out;
TRUNCATE TABLE tblhikvision_sync_log;

-- ═══════════════════════════════════════════════════════════════════════════════
-- QUALITY MODULE
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblquality_checks;
TRUNCATE TABLE tblquality_check_details;
TRUNCATE TABLE tblquality_alerts;
TRUNCATE TABLE tblquality_activity;
TRUNCATE TABLE tblquality_control_points;

-- ═══════════════════════════════════════════════════════════════════════════════
-- FIXED EQUIPMENT / ASSETS
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblfe_assets;
TRUNCATE TABLE tblfe_asset_creation;
TRUNCATE TABLE tblfe_approval_details;

-- ═══════════════════════════════════════════════════════════════════════════════
-- ACCOUNTING - ONLY TRUNCATE TRANSACTION DATA (keep chart of accounts & settings)
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblacc_account_history;
TRUNCATE TABLE tblacc_journal_entries;
TRUNCATE TABLE tblacc_transfers;
TRUNCATE TABLE tblacc_transaction_bankings;
TRUNCATE TABLE tblacc_reconciles;
-- DO NOT TRUNCATE: tblacc_accounts, tblacc_account_types, tblacc_account_type_details
-- These contain the chart of accounts configuration

-- ═══════════════════════════════════════════════════════════════════════════════
-- TAGS
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tbltaggables;

-- ═══════════════════════════════════════════════════════════════════════════════
-- CUSTOM FIELD VALUES (keep field definitions, remove values)
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblcustomfieldsvalues;

-- ═══════════════════════════════════════════════════════════════════════════════
-- NOTES
-- ═══════════════════════════════════════════════════════════════════════════════
TRUNCATE TABLE tblnotes;

-- ═══════════════════════════════════════════════════════════════════════════════
-- Reset admin password change date so they don't get locked out
-- ═══════════════════════════════════════════════════════════════════════════════
UPDATE tblstaff SET last_password_change = NOW() WHERE staffid = 1;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================================
-- DONE. System is clean with only:
-- - Admin user (chatlaerp@gmail.com)
-- - Chart of accounts & accounting settings
-- - System settings & configurations
-- - Module settings (purchase options, warehouse options, etc.)
-- - Roles & permissions definitions
-- - Email templates
-- - Tax rates, currencies, payment modes
-- ============================================================================
