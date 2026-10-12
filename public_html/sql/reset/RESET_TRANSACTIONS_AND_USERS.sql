mysqldump -u root -p nextgen_live > backup_before_reset.sql

-- ===========================================================================
-- RESET SCRIPT - clears transactions and users, keeps the installed setup
-- ===========================================================================
--
--  *** THIS DESTROYS DATA AND CANNOT BE UNDONE. TAKE A FULL BACKUP FIRST. ***
--
--      mysqldump -u root -p nextgen_live > backup_before_reset.sql
--
--  NOT RUN ANYWHERE. Generated only. Read it through before you use it.
--
--
-- WHAT IT DOES
--
--   Clears  every transaction: invoices, purchase orders, GRNs, stock movements,
--           expenses, payments, the general ledger, payslips, attendance, tasks,
--           approvals, activity logs, notifications, sessions and files.
--   Clears  every staff user EXCEPT chatlaerp@gmail.com, plus their permissions,
--           department links and HR records.
--   Clears  the approval settings in every module (section 14A), because each
--           rule names specific staff as approvers and those staff are going.
--   Keeps   modules, settings, chart of accounts, roles, email templates,
--           countries, currencies, taxes, payment modes, departments, cost
--           centres, warehouses, categories, unit types, document templates and
--           every other lookup or master table.
--   Resets  AUTO_INCREMENT everywhere it clears, and the next-number options, so
--           the first new document is number 1 again.
--
--
-- MASTER DATA - ALL CLEARED
--
--      12  customers and their contacts   113 customers, 113 contacts
--      13  vendors and their contacts      77 vendors,  52 contacts
--      14  the item / product catalogue   211 items, 26 groups
--      5   warehouses                       2 warehouses
--
--   Nothing is left commented out except the optional table drops in section 17.
--   This is close to a bare install: you will be re-creating staff, customers,
--   vendors, items, warehouses and the approval chains before the system can be
--   used again.
--
--   Consequences worth knowing:
--     - no customer portal logins survive, they live in tblcontacts
--     - the vendor codes VEN0001 upwards go, so the AP opening balance import in
--       sql/2026-09-06 cannot be re-run afterwards
--     - no items and no warehouses means nothing can be ordered or received until
--       at least one of each exists
--
--   The small lookup lists ARE kept, so you are not re-typing them: item groups
--   are cleared but commodity types, unit types, sub groups, brands, models and
--   series stay, as do payment modes, expense categories, departments and cost
--   centres.
--
--
-- THE LOGIN THAT SURVIVES
--
--   chatlaerp@gmail.com  -  staffid 1, admin, active
--
--   Section 0 refuses to go any further if that account is missing, so the script
--   cannot leave you locked out. Its password, role and admin flag are untouched.
--
--
-- AFTER RUNNING
--
--   Everyone is logged out, including you - tblsessions is cleared. Log back in
--   as chatlaerp@gmail.com.
--
--   File RECORDS are cleared but the files themselves stay on disk. To reclaim
--   the space, empty these folders by hand afterwards:
--       uploads/expenses  uploads/invoices  uploads/purchase  uploads/tasks
--       uploads/projects  uploads/clients   uploads/staff_profile_images
--   Leave uploads/company alone, that holds your logo and favicon.
-- ===========================================================================


-- ---------------------------------------------------------------------------
-- 0. SAFETY CHECK - stop here if the account to keep does not exist
-- ---------------------------------------------------------------------------
-- Both queries must return a row saying OK. If the first one does not, close the
-- session and change nothing: without that account you would have no way back in.

SELECT CASE WHEN COUNT(*) = 1
            THEN 'OK - chatlaerp@gmail.com found, safe to continue'
            ELSE 'STOP - chatlaerp@gmail.com NOT FOUND. Do not run the rest.'
       END AS safety_check,
       COUNT(*) AS accounts_found
FROM `tblstaff`
WHERE `email` = 'chatlaerp@gmail.com';

SELECT CASE WHEN COUNT(*) >= 1
            THEN 'OK - it is an admin, it will keep full access'
            ELSE 'WARNING - not an admin. Set tblstaff.admin = 1 first.'
       END AS admin_check
FROM `tblstaff`
WHERE `email` = 'chatlaerp@gmail.com' AND `admin` = 1;

-- What is about to be removed, for the record
SELECT (SELECT COUNT(*) FROM `tblstaff`)              AS staff_now,
       (SELECT COUNT(*) FROM `tblstaff`
        WHERE `email` <> 'chatlaerp@gmail.com')        AS staff_to_delete,
       (SELECT COUNT(*) FROM `tblpur_invoices`)        AS purchase_invoices,
       (SELECT COUNT(*) FROM `tblpur_orders`)          AS purchase_orders,
       (SELECT COUNT(*) FROM `tblexpenses`)            AS expenses,
       (SELECT COUNT(*) FROM `tblacc_account_history`) AS ledger_entries;


-- Foreign keys off for the duration. Turned back on in section 16 - do not stop
-- the script part way without running that section.
SET FOREIGN_KEY_CHECKS = 0;
SET SQL_SAFE_UPDATES = 0;


-- ---------------------------------------------------------------------------
-- 1. GENERAL LEDGER AND ACCOUNTING TRANSACTIONS
--    tblacc_accounts (the chart of accounts) is NOT touched, nor are the
--    mapping tables that tell the system which account to use.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tblacc_account_history`;
TRUNCATE TABLE `tblacc_journal_entries`;
TRUNCATE TABLE `tblacc_transfers`;
TRUNCATE TABLE `tblacc_checks`;
TRUNCATE TABLE `tblacc_check_details`;
TRUNCATE TABLE `tblacc_checks_printed`;
TRUNCATE TABLE `tblacc_print_later`;
TRUNCATE TABLE `tblacc_pay_bills`;
TRUNCATE TABLE `tblacc_pay_bill_details`;
TRUNCATE TABLE `tblacc_pay_bill_item_paid`;
TRUNCATE TABLE `tblacc_bank_reconciles`;
TRUNCATE TABLE `tblacc_reconciles`;
TRUNCATE TABLE `tblacc_matched_transactions`;
TRUNCATE TABLE `tblacc_transaction_bankings`;
TRUNCATE TABLE `tblacc_budgets`;
TRUNCATE TABLE `tblacc_budget_details`;
TRUNCATE TABLE `tblacc_plaid_transaction_logs`;
TRUNCATE TABLE `tblacc_income_statement_modifications`;


-- ---------------------------------------------------------------------------
-- 2. SALES - invoices, credit notes, estimates, proposals, contracts
--    Keeps tblcontracts_types and tblestimate_request_status / _forms.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tblinvoices`;
TRUNCATE TABLE `tblinvoicepaymentrecords`;
TRUNCATE TABLE `tblitemable`;
TRUNCATE TABLE `tblcreditnotes`;
TRUNCATE TABLE `tblcreditnote_refunds`;
TRUNCATE TABLE `tblcredits`;
TRUNCATE TABLE `tblestimates`;
TRUNCATE TABLE `tblestimate_requests`;
TRUNCATE TABLE `tblproposals`;
TRUNCATE TABLE `tblproposal_comments`;
TRUNCATE TABLE `tblsubscriptions`;
TRUNCATE TABLE `tblpayment_attempts`;
TRUNCATE TABLE `tbltwocheckout_log`;
TRUNCATE TABLE `tblsales_activity`;
TRUNCATE TABLE `tblsales_returns`;
TRUNCATE TABLE `tblsales_return_items`;
TRUNCATE TABLE `tblcontracts`;
TRUNCATE TABLE `tblcontract_comments`;
TRUNCATE TABLE `tblcontract_renewals`;


-- ---------------------------------------------------------------------------
-- 3. EXPENSES
--    Keeps tblexpenses_categories.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tblexpenses`;
TRUNCATE TABLE `tblexpense_items`;
TRUNCATE TABLE `tblexpense_payments`;


-- ---------------------------------------------------------------------------
-- 4. PURCHASE - requests, orders, invoices, payments, approvals
--    Keeps tblpurchase_option, tblpur_request_types, tblpur_terms,
--    and tblpur_unit. The approval RULES go too - see section 14A.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tblpur_invoices`;
TRUNCATE TABLE `tblpur_invoice_details`;
TRUNCATE TABLE `tblpur_invoice_payment`;
TRUNCATE TABLE `tblpur_orders`;
TRUNCATE TABLE `tblpur_order_detail`;
TRUNCATE TABLE `tblpur_order_payment`;
TRUNCATE TABLE `tblpur_request`;
TRUNCATE TABLE `tblpur_request_detail`;
TRUNCATE TABLE `tblpur_estimates`;
TRUNCATE TABLE `tblpur_estimate_detail`;
TRUNCATE TABLE `tblpur_debits`;
TRUNCATE TABLE `tblpur_debit_notes`;
TRUNCATE TABLE `tblpur_debits_refunds`;
TRUNCATE TABLE `tblpur_advance_allocations`;
TRUNCATE TABLE `tblpur_approval_details`;
TRUNCATE TABLE `tblpur_activity_log`;
TRUNCATE TABLE `tblpur_comments`;
TRUNCATE TABLE `tblpur_contracts`;
TRUNCATE TABLE `tblpur_bid_waivers`;
TRUNCATE TABLE `tblpur_bid_waiver_approvals`;
TRUNCATE TABLE `tblpur_bid_waiver_quotations`;
TRUNCATE TABLE `tblpur_petty_cash_advances`;
TRUNCATE TABLE `tblpur_petty_cash_advance_approvals`;
TRUNCATE TABLE `tblpur_petty_cash_advance_items`;
TRUNCATE TABLE `tblpur_faf_requests`;
TRUNCATE TABLE `tblpur_vendor_opening_balance`;
TRUNCATE TABLE `tblpur_vendor_recommendations`;
TRUNCATE TABLE `tblinv_shipping_charges`;
TRUNCATE TABLE `tblinv_shipping_charge_items`;


-- ---------------------------------------------------------------------------
-- 5. WAREHOUSE, GRN AND STOCK MOVEMENTS
--
--    The warehouses themselves are cleared too, so stock has nowhere to sit until
--    at least one is created again. Staff-to-warehouse links go with the staff in
--    section 15.
--
--    Keeps the small lookup lists: tblware_*_type, tblwh_sub_group, tblwh_brand,
--    tblwh_model, tblwh_series and tblwh_custom_fields. The approval rules go
--    too - see section 14A.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tblwarehouse`;
TRUNCATE TABLE `tblgoods_receipt`;
TRUNCATE TABLE `tblgoods_receipt_detail`;
TRUNCATE TABLE `tblgoods_receipt_history`;
TRUNCATE TABLE `tblgoods_receipt_history_detail`;
TRUNCATE TABLE `tblgoods_transaction_detail`;
TRUNCATE TABLE `tblgoods_delivery`;
TRUNCATE TABLE `tblgoods_delivery_batch`;
TRUNCATE TABLE `tblgoods_delivery_detail`;
TRUNCATE TABLE `tblgoods_delivery_invoices_pr_orders`;
TRUNCATE TABLE `tblinventory_manage`;
TRUNCATE TABLE `tblinventory_commodity_min`;
TRUNCATE TABLE `tblinternal_delivery_note`;
TRUNCATE TABLE `tblinternal_delivery_note_detail`;
TRUNCATE TABLE `tblstock_take`;
TRUNCATE TABLE `tblstock_take_detail`;
TRUNCATE TABLE `tblwh_activity_log`;
TRUNCATE TABLE `tblwh_approval_details`;
TRUNCATE TABLE `tblwh_loss_adjustment`;
TRUNCATE TABLE `tblwh_loss_adjustment_detail`;
TRUNCATE TABLE `tblwh_order_returns`;
TRUNCATE TABLE `tblwh_order_return_details`;
TRUNCATE TABLE `tblwh_order_returns_refunds`;
TRUNCATE TABLE `tblwh_packing_lists`;
TRUNCATE TABLE `tblwh_packing_list_details`;
TRUNCATE TABLE `tblwh_omni_shipments`;
TRUNCATE TABLE `tblwh_goods_delivery_activity_log`;
TRUNCATE TABLE `tblwh_inventory_serial_numbers`;


-- ---------------------------------------------------------------------------
-- 6. FIXED EQUIPMENT / ASSETS
--    Keeps the setup tables: categories, locations, models, manufacturers,
--    status labels, suppliers, warehouse, custom fields, fieldsets, seats,
--    signers and predefined kits. The approval rules go too - see section 14A.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tblfe_asset_creation`;
TRUNCATE TABLE `tblfe_assets`;
TRUNCATE TABLE `tblfe_log_assets`;
TRUNCATE TABLE `tblfe_depreciations`;
TRUNCATE TABLE `tblfe_depreciation_items`;
TRUNCATE TABLE `tblfe_goods_receipt`;
TRUNCATE TABLE `tblfe_goods_receipt_detail`;
TRUNCATE TABLE `tblfe_goods_delivery`;
TRUNCATE TABLE `tblfe_goods_delivery_detail`;
TRUNCATE TABLE `tblfe_goods_delivery_activity_log`;
TRUNCATE TABLE `tblfe_goods_delivery_invoices_pr_orders`;
TRUNCATE TABLE `tblfe_goods_transaction_detail`;
TRUNCATE TABLE `tblfe_goods_transaction_details`;
TRUNCATE TABLE `tblfe_cart`;
TRUNCATE TABLE `tblfe_cart_detailt`;
TRUNCATE TABLE `tblfe_approval_details`;
TRUNCATE TABLE `tblfe_activity_log`;
TRUNCATE TABLE `tblfe_audit_requests`;
TRUNCATE TABLE `tblfe_audit_detail_requests`;
TRUNCATE TABLE `tblfe_checkin_assets`;
TRUNCATE TABLE `tblfe_asset_maintenances`;
TRUNCATE TABLE `tblfe_packing_lists`;
TRUNCATE TABLE `tblfe_packing_list_details`;
TRUNCATE TABLE `tblfe_refunds`;
TRUNCATE TABLE `tblfe_tickets`;
TRUNCATE TABLE `tblfe_ticket_action_post_internal_notes`;
TRUNCATE TABLE `tblfe_ticket_timeline_logs`;
TRUNCATE TABLE `tblfe_sign_documents`;
TRUNCATE TABLE `tblfe_omni_shipments`;
TRUNCATE TABLE `tblfe_cron_log`;
TRUNCATE TABLE `tblasset_code_requests`;
TRUNCATE TABLE `tblasset_code_request_items`;


-- ---------------------------------------------------------------------------
-- 7. MANUFACTURING AND QUALITY
--    Keeps tblmrp_option, routings, work centres, working hours, unit measure
--    categories, tblquality_control_points and tblquality_teams. The approval
--    rules go too - see section 14A.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tblmrp_manufacturing_orders`;
TRUNCATE TABLE `tblmrp_manufacturing_order_details`;
TRUNCATE TABLE `tblmrp_work_orders`;
TRUNCATE TABLE `tblmrp_work_order_details`;
TRUNCATE TABLE `tblmrp_work_order_time_trackings`;
TRUNCATE TABLE `tblmrp_bill_of_materials`;
TRUNCATE TABLE `tblmrp_bill_of_material_details`;
TRUNCATE TABLE `tblmrp_bom_changes_logs`;
TRUNCATE TABLE `tblmrp_approval_details`;
TRUNCATE TABLE `tblquality_checks`;
TRUNCATE TABLE `tblquality_check_details`;
TRUNCATE TABLE `tblquality_activity`;
TRUNCATE TABLE `tblquality_alerts`;


-- ---------------------------------------------------------------------------
-- 8. PROJECTS AND TASKS
--    Keeps tbltask_priority_master, tbltask_related_master,
--    tbltask_repeat_master and tbltasks_checklist_templates.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tblprojects`;
TRUNCATE TABLE `tblproject_activity`;
TRUNCATE TABLE `tblproject_files`;
TRUNCATE TABLE `tblproject_members`;
TRUNCATE TABLE `tblproject_notes`;
TRUNCATE TABLE `tblproject_settings`;
TRUNCATE TABLE `tblpinned_projects`;
TRUNCATE TABLE `tblprojectdiscussions`;
TRUNCATE TABLE `tblprojectdiscussioncomments`;
TRUNCATE TABLE `tblmilestones`;
TRUNCATE TABLE `tbltasks`;
TRUNCATE TABLE `tbltask_assigned`;
TRUNCATE TABLE `tbltask_comments`;
TRUNCATE TABLE `tbltask_followers`;
TRUNCATE TABLE `tbltask_checklist_items`;
TRUNCATE TABLE `tbltaskstimers`;
TRUNCATE TABLE `tblchecklist`;
TRUNCATE TABLE `tblchecklist_allocation`;
TRUNCATE TABLE `tblgroup_checklist`;
TRUNCATE TABLE `tblgroup_checklist_allocation`;


-- ---------------------------------------------------------------------------
-- 9. SUPPORT TICKETS AND LEADS
--    Keeps ticket priorities, statuses, predefined replies, spam filters,
--    lead sources and lead statuses.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tbltickets`;
TRUNCATE TABLE `tblticket_replies`;
TRUNCATE TABLE `tblticket_attachments`;
TRUNCATE TABLE `tbltickets_pipe_log`;
TRUNCATE TABLE `tblleads`;
TRUNCATE TABLE `tbllead_activity_log`;
TRUNCATE TABLE `tbllead_integration_emails`;
TRUNCATE TABLE `tblleads_email_integration`;
TRUNCATE TABLE `tblweb_to_lead`;


-- ---------------------------------------------------------------------------
-- 10. HR, PAYROLL, TIMESHEETS AND ATTENDANCE
--     Keeps the configuration: job positions, payroll options and columns,
--     allowance types, contract templates and types, workplaces, training
--     types, shift types, leave types, valid IPs, routes and the HR knowledge
--     base.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tblhrp_biometric_log`;
TRUNCATE TABLE `tblhrp_employees_timesheets`;
TRUNCATE TABLE `tblhrp_employees_timeshee_leaves`;
TRUNCATE TABLE `tblhrp_payslips`;
TRUNCATE TABLE `tblhrp_payslip_details`;
TRUNCATE TABLE `tblhrp_commissions`;
TRUNCATE TABLE `tblhrp_project_costs`;
TRUNCATE TABLE `tblhrp_project_for_staffs`;
TRUNCATE TABLE `tblhrp_staff_insurances`;
TRUNCATE TABLE `tblhrp_salary_deductions`;
TRUNCATE TABLE `tblhrp_employees_value`;
TRUNCATE TABLE `tblhrp_export_jobs`;
TRUNCATE TABLE `tblhrp_income_taxs`;
TRUNCATE TABLE `tblhrp_bonus_kpi`;

TRUNCATE TABLE `tbltimesheets_timesheet`;
TRUNCATE TABLE `tbltimesheets_timekeeper_data`;
TRUNCATE TABLE `tbltimesheets_additional_timesheet`;
TRUNCATE TABLE `tbltimesheets_leave`;
TRUNCATE TABLE `tbltimesheets_requisition_leave`;
TRUNCATE TABLE `tbltimesheets_approval_details`;
TRUNCATE TABLE `tbltimesheets_latch_timesheet`;
TRUNCATE TABLE `tbltimesheets_log_send_notify`;
TRUNCATE TABLE `tbltimesheets_kteco_attendance_logs`;
TRUNCATE TABLE `tbltimesheets_kteco_employees`;
TRUNCATE TABLE `tbltimesheets_kteco_sync_states`;
TRUNCATE TABLE `tbltimesheets_go_bussiness_advance_payment`;
TRUNCATE TABLE `tbltimesheets_day_off`;
TRUNCATE TABLE `tbltimesheets_shift_sc`;
TRUNCATE TABLE `tbltimesheets_shiftwork_sc`;
TRUNCATE TABLE `tbltimesheets_workplace_assign`;

TRUNCATE TABLE `tblcheck_in_out`;
TRUNCATE TABLE `tblhikvision_sync_log`;
TRUNCATE TABLE `tblmanage_leave`;
TRUNCATE TABLE `tblleave_of_the_year`;
TRUNCATE TABLE `tblday_off`;

TRUNCATE TABLE `tblhr_staff_contract`;
TRUNCATE TABLE `tblhr_staff_contract_detail`;
TRUNCATE TABLE `tblhr_education`;
TRUNCATE TABLE `tblhr_dependent_person`;
TRUNCATE TABLE `tblhr_allocation_asset`;
TRUNCATE TABLE `tblhr_checklist_allocation`;
TRUNCATE TABLE `tblhr_group_checklist_allocation`;
TRUNCATE TABLE `tblhr_procedure_retire`;
TRUNCATE TABLE `tblhr_procedure_retire_manage`;
TRUNCATE TABLE `tblhr_procedure_retire_of_staff`;
TRUNCATE TABLE `tblhr_procedure_retire_of_staff_by_id`;
TRUNCATE TABLE `tblhr_list_staff_quitting_work`;
TRUNCATE TABLE `tblhr_rec_transfer_records`;
TRUNCATE TABLE `tblhr_training_allocation`;
TRUNCATE TABLE `tblhr_views_tracking`;
TRUNCATE TABLE `tblhr_salary_form`;
TRUNCATE TABLE `tblbonus_discipline`;
TRUNCATE TABLE `tblbonus_discipline_detail`;
TRUNCATE TABLE `tblrec_transfer_records`;
TRUNCATE TABLE `tbltransfer_records_reception`;
TRUNCATE TABLE `tbltraining_allocation`;


-- ---------------------------------------------------------------------------
-- 11. CHEQUES, LOGS, NOTIFICATIONS, FILES AND SESSIONS
--     Clearing tblsessions logs everyone out, including you.
--     tblfiles holds the file RECORDS; the files stay on disk, see the note at
--     the top of this script.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tblcheque_management`;
TRUNCATE TABLE `tblcheque_transactions`;
TRUNCATE TABLE `tblactivity_log`;
TRUNCATE TABLE `tblnotifications`;
TRUNCATE TABLE `tblsessions`;
TRUNCATE TABLE `tblviews_tracking`;
TRUNCATE TABLE `tbltracked_mails`;
TRUNCATE TABLE `tblmail_queue`;
TRUNCATE TABLE `tblscheduled_emails`;
TRUNCATE TABLE `tblapi_login_audit`;
TRUNCATE TABLE `tblapi_refresh_tokens`;
TRUNCATE TABLE `tbluser_auto_login`;
TRUNCATE TABLE `tbldismissed_announcements`;
TRUNCATE TABLE `tblreminders`;
TRUNCATE TABLE `tblevents`;
TRUNCATE TABLE `tblnotes`;
TRUNCATE TABLE `tbltodos`;
TRUNCATE TABLE `tblnewsfeed_posts`;
TRUNCATE TABLE `tblnewsfeed_post_comments`;
TRUNCATE TABLE `tblnewsfeed_post_likes`;
TRUNCATE TABLE `tblnewsfeed_comment_likes`;
TRUNCATE TABLE `tblfiles`;
TRUNCATE TABLE `tblshared_customer_files`;
TRUNCATE TABLE `tblcustomfieldsvalues`;
TRUNCATE TABLE `tbltaggables`;
TRUNCATE TABLE `tbltags`;
TRUNCATE TABLE `tblvault`;
TRUNCATE TABLE `tblgdpr_requests`;
TRUNCATE TABLE `tblconsents`;
TRUNCATE TABLE `tblconsent_purposes`;
TRUNCATE TABLE `tblform_results`;


-- ---------------------------------------------------------------------------
-- 12. CUSTOMERS AND CONTACTS          -- ACTIVE
--     113 customers and 113 contacts, including the customer portal logins that
--     hang off tblcontacts. Nobody will be able to log into the client portal
--     afterwards until customers are re-created.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tblclients`;
TRUNCATE TABLE `tblcontacts`;
TRUNCATE TABLE `tblcontact_permissions`;
TRUNCATE TABLE `tblcustomer_admins`;
TRUNCATE TABLE `tblcustomers_groups`;
TRUNCATE TABLE `tblcustomer_groups`;


-- ---------------------------------------------------------------------------
-- 13. VENDORS AND VENDOR CONTACTS     -- ACTIVE
--     77 vendors and 52 contacts.
--
--     This also removes the vendor codes VEN0001 upwards. The AP opening balance
--     files in sql/2026-09-06 match on those codes, so that import cannot be
--     re-run against this database once this has gone through. If you still need
--     it, load the opening balances BEFORE running this reset, or comment this
--     section back out.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tblpur_vendor`;
TRUNCATE TABLE `tblpur_contacts`;
TRUNCATE TABLE `tblpur_vendor_admin`;
TRUNCATE TABLE `tblpur_vendor_cate`;
TRUNCATE TABLE `tblpur_vendor_items`;
TRUNCATE TABLE `tblitems_of_vendor`;


-- ---------------------------------------------------------------------------
-- 14. ITEM / PRODUCT CATALOGUE        -- ACTIVE
--     211 items and 26 groups. Everything a purchase order or a stock movement
--     is built from, so the system starts with an empty catalogue and items have
--     to be created before anything can be ordered or received.
--
--     The commodity and unit type lookups are NOT cleared - tblware_commodity_type
--     (6 rows), tblware_unit_type (21) and tblwh_sub_group (5). They are small
--     lookup lists that new items pick from, so keeping them saves re-typing.
--     Add them here if you want a completely bare start.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tblitems`;
TRUNCATE TABLE `tblitems_groups`;
TRUNCATE TABLE `tblitem_tax`;
TRUNCATE TABLE `tblrelated_items`;
TRUNCATE TABLE `tblservices`;


-- ---------------------------------------------------------------------------
-- 14A. APPROVAL SETTINGS - the approval RULES, across every module
--
--      These are the chains that say who approves a purchase request, a GRN, a
--      work order and so on. Each row names specific staff ids as approvers.
--
--      Cleared deliberately: section 15 deletes every staff account except
--      chatlaerp@gmail.com, so any rule left behind would point at staff who no
--      longer exist and would silently block or misroute the first documents
--      raised on the clean system. Better to have no chains than broken ones.
--
--      Rebuild them afterwards under each module's approval settings screen,
--      once the new staff accounts exist.
-- ---------------------------------------------------------------------------
TRUNCATE TABLE `tblpur_approval_setting`;
TRUNCATE TABLE `tblfe_approval_setting`;
TRUNCATE TABLE `tblwh_approval_setting`;
TRUNCATE TABLE `tblmrp_approval_setting`;
TRUNCATE TABLE `tbltimesheets_approval_setting`;


-- ---------------------------------------------------------------------------
-- 15. STAFF USERS - everyone except chatlaerp@gmail.com
--
--     The keeper is matched on EMAIL, not on staffid, so this stays correct on
--     any copy of the database. Their password, admin flag and role are not
--     touched.
--
--     The child tables go first, keyed on the same email lookup, so nothing is
--     left orphaned. tblstaff itself is deleted with a plain comparison because
--     MySQL will not let you subquery the table you are deleting from.
-- ---------------------------------------------------------------------------

DELETE FROM `tblstaff_permissions`
WHERE `staff_id` NOT IN (
    SELECT `staffid` FROM (SELECT `staffid` FROM `tblstaff`
                           WHERE `email` = 'chatlaerp@gmail.com') AS keeper
);

DELETE FROM `tblstaff_departments`
WHERE `staffid` NOT IN (
    SELECT `staffid` FROM (SELECT `staffid` FROM `tblstaff`
                           WHERE `email` = 'chatlaerp@gmail.com') AS keeper
);

DELETE FROM `tblwh_staff_warehouses`
WHERE `staff_id` NOT IN (
    SELECT `staffid` FROM (SELECT `staffid` FROM `tblstaff`
                           WHERE `email` = 'chatlaerp@gmail.com') AS keeper
);

DELETE FROM `tbluser_meta`
WHERE `staff_id` NOT IN (
    SELECT `staffid` FROM (SELECT `staffid` FROM `tblstaff`
                           WHERE `email` = 'chatlaerp@gmail.com') AS keeper
);

-- And finally the accounts themselves
DELETE FROM `tblstaff` WHERE `email` <> 'chatlaerp@gmail.com';


-- ---------------------------------------------------------------------------
-- 16. RESET COUNTERS AND DOCUMENT NUMBERING
--
--     TRUNCATE already reset AUTO_INCREMENT on everything above. tblstaff was
--     cleared with DELETE, so its counter is set by hand to just past the
--     surviving account.
--
--     The next-number options are what the forms read to build INV00065,
--     PO00000092 and so on. Without this the first new document would carry on
--     from the old sequence.
-- ---------------------------------------------------------------------------

ALTER TABLE `tblstaff` AUTO_INCREMENT = 2;

-- Purchase module numbering
UPDATE `tblpurchase_option` SET `option_val` = 1
WHERE `option_name` IN (
    'next_inv_number', 'next_po_number', 'next_pr_number',
    'next_estimate_number', 'next_debit_note_number'
);

-- Core numbering held in tbloptions
UPDATE `tbloptions` SET `value` = 1
WHERE `name` IN (
    'next_invoice_number', 'next_estimate_number', 'next_proposal_number',
    'next_credit_note_number', 'next_contract_number', 'next_expense_number',
    'next_payment_number'
);

SET FOREIGN_KEY_CHECKS = 1;
SET SQL_SAFE_UPDATES = 1;


-- ---------------------------------------------------------------------------
-- 17. OPTIONAL - drop the leftover working tables
--     None of these belong to the application. They are the import staging
--     table and the backups taken during earlier data fixes. Dropping them is
--     tidy but not required.
-- ---------------------------------------------------------------------------
-- DROP TABLE IF EXISTS `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`;
-- DROP TABLE IF EXISTS `tblacc_account_history_bak_20260901`;
-- DROP TABLE IF EXISTS `tblacc_mapping_reset_log_20260901`;
-- DROP TABLE IF EXISTS `tbloptions_email_signature_bak_20260901`;
-- DROP TABLE IF EXISTS `tblinv_shipping_charge_items_orphans_bak_20260901`;


-- ===========================================================================
-- 18. VERIFY - every count should be 0, and staff should be exactly 1
-- ===========================================================================

SELECT 'staff remaining'      AS what, COUNT(*) AS n FROM `tblstaff`
UNION ALL SELECT 'the keeper', COUNT(*) FROM `tblstaff` WHERE `email` = 'chatlaerp@gmail.com'
UNION ALL SELECT 'staff permissions', COUNT(*) FROM `tblstaff_permissions`
UNION ALL SELECT 'ledger entries', COUNT(*) FROM `tblacc_account_history`
UNION ALL SELECT 'purchase invoices', COUNT(*) FROM `tblpur_invoices`
UNION ALL SELECT 'purchase orders', COUNT(*) FROM `tblpur_orders`
UNION ALL SELECT 'purchase requests', COUNT(*) FROM `tblpur_request`
UNION ALL SELECT 'expenses', COUNT(*) FROM `tblexpenses`
UNION ALL SELECT 'expense payments', COUNT(*) FROM `tblexpense_payments`
UNION ALL SELECT 'goods receipts', COUNT(*) FROM `tblgoods_receipt`
UNION ALL SELECT 'stock rows', COUNT(*) FROM `tblinventory_manage`
UNION ALL SELECT 'assets', COUNT(*) FROM `tblfe_asset_creation`
UNION ALL SELECT 'tasks', COUNT(*) FROM `tbltasks`
UNION ALL SELECT 'attendance rows', COUNT(*) FROM `tblcheck_in_out`
UNION ALL SELECT 'timesheet rows', COUNT(*) FROM `tbltimesheets_timesheet`
UNION ALL SELECT 'activity log', COUNT(*) FROM `tblactivity_log`
UNION ALL SELECT 'sessions', COUNT(*) FROM `tblsessions`;


-- And the setup that must still be intact
SELECT 'modules'          AS what, COUNT(*) AS n FROM `tblmodules`
UNION ALL SELECT 'settings (tbloptions)', COUNT(*) FROM `tbloptions`
UNION ALL SELECT 'chart of accounts', COUNT(*) FROM `tblacc_accounts`
UNION ALL SELECT 'roles', COUNT(*) FROM `tblroles`
UNION ALL SELECT 'email templates', COUNT(*) FROM `tblemailtemplates`
UNION ALL SELECT 'countries', COUNT(*) FROM `tblcountries`
UNION ALL SELECT 'currencies', COUNT(*) FROM `tblcurrencies`
UNION ALL SELECT 'payment modes', COUNT(*) FROM `tblpayment_modes`
UNION ALL SELECT 'departments', COUNT(*) FROM `tbldepartments`
UNION ALL SELECT 'cost centres', COUNT(*) FROM `tblcost_centers`
UNION ALL SELECT 'expense categories', COUNT(*) FROM `tblexpenses_categories`
UNION ALL SELECT 'commodity types (lookup)', COUNT(*) FROM `tblware_commodity_type`
UNION ALL SELECT 'unit types (lookup)', COUNT(*) FROM `tblware_unit_type`
UNION ALL SELECT 'warehouse sub groups (lookup)', COUNT(*) FROM `tblwh_sub_group`;


-- Master data cleared by sections 5, 12, 13, 14 and 14A. All zero.
SELECT 'customers'        AS what, COUNT(*) AS n FROM `tblclients`
UNION ALL SELECT 'customer contacts', COUNT(*) FROM `tblcontacts`
UNION ALL SELECT 'vendors', COUNT(*) FROM `tblpur_vendor`
UNION ALL SELECT 'vendor contacts', COUNT(*) FROM `tblpur_contacts`
UNION ALL SELECT 'items', COUNT(*) FROM `tblitems`
UNION ALL SELECT 'item groups', COUNT(*) FROM `tblitems_groups`
UNION ALL SELECT 'warehouses', COUNT(*) FROM `tblwarehouse`
UNION ALL SELECT 'purchase approval rules', COUNT(*) FROM `tblpur_approval_setting`
UNION ALL SELECT 'asset approval rules', COUNT(*) FROM `tblfe_approval_setting`
UNION ALL SELECT 'warehouse approval rules', COUNT(*) FROM `tblwh_approval_setting`
UNION ALL SELECT 'manufacturing approval rules', COUNT(*) FROM `tblmrp_approval_setting`
UNION ALL SELECT 'timesheet approval rules', COUNT(*) FROM `tbltimesheets_approval_setting`;


-- Expected result:
--
--   ZERO                                KEPT
--   ----                                ----
--   everything in the first block       modules .................. 9
--   customers ................. 0       settings ................. 776
--   customer contacts ......... 0       chart of accounts ........ 360
--   vendors ................... 0       roles .................... 23
--   vendor contacts ........... 0       email templates .......... 2862
--   items ..................... 0       countries ................ 250
--   item groups ............... 0       currencies ............... 5
--   warehouses ................ 0       payment modes ............ 4
--   purchase approval rules ... 0       departments .............. 9
--   asset approval rules ...... 0       cost centres ............. 15
--   warehouse approval rules .. 0       expense categories ....... 4
--   manufacturing appr. rules . 0       commodity types .......... 6
--   timesheet approval rules .. 0       unit types ............... 21
--                                       warehouse sub groups ..... 5
--   EXCEPT
--   ------
--   staff remaining ........... 1
--   the keeper ................ 1
--   staff permissions ......... whatever the keeper holds
--
--
-- WHAT TO SET UP AFTER THE RESET, IN THIS ORDER
--
--   1  staff accounts and their roles
--   2  the approval chains in each module - nothing that needs approval can be
--      routed until these exist, and they name staff, so staff come first
--   3  at least one warehouse
--   4  items
--   5  vendors and customers
--
--   Settings, the chart of accounts, taxes, payment modes, departments, cost
--   centres and every lookup list are already in place, so none of that needs
--   redoing.
-- ===========================================================================
