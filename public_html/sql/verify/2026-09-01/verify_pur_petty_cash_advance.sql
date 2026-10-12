-- ---------------------------------------------------------------------------
-- Round-trip check for the Petty Cash Advance schema.
--
-- Creates a request with item lines, walks it through create -> edit -> approve
-- -> reject, reads it back through the same joins the model uses, then rolls
-- everything back. Nothing persists.
--
--   mysql -u root nextgen_live --table -e "source sql/2026-09-01/verify_pur_petty_cash_advance.sql"
-- ---------------------------------------------------------------------------

START TRANSACTION;

-- ── Create, mirroring add_petty_cash_advance() ─────────────────────────────
INSERT INTO `tblpur_petty_cash_advances`
  (`request_no`, `number`, `doc_version`, `doc_effective_date`, `doc_revise`,
   `doc_number`, `doc_approved_by`,
   `employee_id`, `employee`, `department`, `required_date`,
   `purpose_of_advance`, `expense_category`, `amount_requested`, `currency`,
   `advance_required_date`, `payment_priority`,
   `approve_status`, `created_at`, `created_by`)
SELECT 'PCA00000001', 1, '1.0', CURDATE(), NULL,
       (SELECT option_val FROM tblpurchase_option WHERE option_name = 'pur_pca_doc_no'),
       NULL,
       'EMP-0142', (SELECT MIN(staffid) FROM tblstaff),
       (SELECT MIN(departmentid) FROM tbldepartments), '2026-09-15',
       'Site consumables and courier charges for the Fujairah run',
       (SELECT MIN(id) FROM tblexpenses_categories), 1750.00,
       (SELECT id FROM tblcurrencies WHERE isdefault = 1 LIMIT 1),
       '2026-09-15', 'urgent',
       1, NOW(), (SELECT MIN(staffid) FROM tblstaff);

SET @pca = LAST_INSERT_ID();

-- ── Section 3: three item lines ────────────────────────────────────────────
INSERT INTO `tblpur_petty_cash_advance_items`
  (`advance_id`, `description`, `amount`, `vendor_id`, `sort_order`)
SELECT @pca, 'Courier charges', 250.00, (SELECT MIN(userid) FROM tblpur_vendor), 0
UNION ALL SELECT @pca, 'Cleaning consumables', 900.00, (SELECT MAX(userid) FROM tblpur_vendor), 1
UNION ALL SELECT @pca, 'Misc site expenses', 600.00, NULL, 2;

SELECT '--- 1. reads back through the model joins ---' AS step;
SELECT a.request_no, a.employee_id, d.name AS department, c.name AS category,
       a.amount_requested, a.payment_priority, a.approve_status
FROM tblpur_petty_cash_advances a
LEFT JOIN tbldepartments d        ON d.departmentid = a.department
LEFT JOIN tblexpenses_categories c ON c.id          = a.expense_category
WHERE a.id = @pca;

SELECT '--- 2. item lines in entry order, and their total ---' AS step;
SELECT i.sort_order, i.description, i.amount, IFNULL(v.company,'(no vendor)') AS vendor
FROM tblpur_petty_cash_advance_items i
LEFT JOIN tblpur_vendor v ON v.userid = i.vendor_id
WHERE i.advance_id = @pca ORDER BY i.sort_order;

SELECT (SELECT SUM(amount) FROM tblpur_petty_cash_advance_items WHERE advance_id = @pca) AS items_total,
       (SELECT amount_requested FROM tblpur_petty_cash_advances WHERE id = @pca) AS amount_requested,
       CASE WHEN (SELECT SUM(amount) FROM tblpur_petty_cash_advance_items WHERE advance_id = @pca)
                 = (SELECT amount_requested FROM tblpur_petty_cash_advances WHERE id = @pca)
            THEN 'match - no warning shown' ELSE 'mismatch - warning shown' END AS reconciliation;

-- ── Document control: an edit bumps the version ────────────────────────────
UPDATE `tblpur_petty_cash_advances`
SET `doc_version` = CONCAT(CAST(doc_version AS UNSIGNED) + 1, '.0'),
    `doc_revise`  = CURDATE()
WHERE `id` = @pca;

SELECT '--- 3. after an edit: Ver 2.0, Revise stamped, Effective unmoved ---' AS step;
SELECT doc_version, doc_effective_date, doc_revise, doc_number FROM tblpur_petty_cash_advances WHERE id = @pca;

-- ── Section 4: FOUR roles, no CEO on this form ─────────────────────────────
INSERT INTO `tblpur_petty_cash_advance_approvals` (`advance_id`, `role`, `staffid`, `status`, `action_at`)
SELECT @pca, 'initiator', (SELECT MIN(staffid) FROM tblstaff), 2, NOW()
UNION ALL SELECT @pca, 'line_manager', (SELECT MIN(staffid) FROM tblstaff), 2, NOW()
UNION ALL SELECT @pca, 'finance_manager', (SELECT MIN(staffid) FROM tblstaff), 2, NOW();

SELECT '--- 4. three of four approved: still pending ---' AS step;
SELECT (SELECT COUNT(*) FROM tblpur_petty_cash_advance_approvals WHERE advance_id = @pca AND status = 2) AS approved_roles,
       CASE WHEN (SELECT COUNT(*) FROM tblpur_petty_cash_advance_approvals WHERE advance_id = @pca AND status = 2) >= 4
            THEN 'approved' ELSE 'pending OK' END AS rolled_up_status;

INSERT INTO `tblpur_petty_cash_advance_approvals` (`advance_id`, `role`, `staffid`, `status`, `action_at`)
VALUES (@pca, 'gm', (SELECT MIN(staffid) FROM tblstaff), 2, NOW());

UPDATE `tblpur_petty_cash_advances`
SET `approve_status` = 2,
    `doc_approved_by` = CONCAT((SELECT CONCAT(firstname,' ',lastname) FROM tblstaff WHERE staffid = (SELECT MIN(staffid) FROM tblstaff)), ' (GM)')
WHERE `id` = @pca;

SELECT '--- 5. all four approved: status 2, Approved By stamped ---' AS step;
SELECT approve_status, doc_version, doc_approved_by FROM tblpur_petty_cash_advances WHERE id = @pca;

SELECT '--- 6. unique key blocks a duplicate decision per role ---' AS step;
INSERT IGNORE INTO `tblpur_petty_cash_advance_approvals` (`advance_id`, `role`, `staffid`, `status`)
VALUES (@pca, 'gm', 1, 3);
SELECT COUNT(*) AS gm_rows_should_be_1
FROM tblpur_petty_cash_advance_approvals WHERE advance_id = @pca AND role = 'gm';

-- ── A rejection rejects the request and clears the stamp ───────────────────
UPDATE `tblpur_petty_cash_advance_approvals` SET `status` = 3 WHERE `advance_id` = @pca AND `role` = 'finance_manager';
UPDATE `tblpur_petty_cash_advances` SET `approve_status` = 3, `doc_approved_by` = NULL WHERE `id` = @pca;

SELECT '--- 7. after a rejection: status 3, stamp cleared ---' AS step;
SELECT approve_status, IFNULL(doc_approved_by,'(empty)') AS doc_approved_by
FROM tblpur_petty_cash_advances WHERE id = @pca;

-- ── Delete cascade ─────────────────────────────────────────────────────────
DELETE FROM `tblpur_petty_cash_advance_items`     WHERE `advance_id` = @pca;
DELETE FROM `tblpur_petty_cash_advance_approvals` WHERE `advance_id` = @pca;
DELETE FROM `tblpur_petty_cash_advances`          WHERE `id` = @pca;

SELECT '--- 8. delete leaves nothing behind ---' AS step;
SELECT (SELECT COUNT(*) FROM tblpur_petty_cash_advances WHERE id = @pca) AS forms_left,
       (SELECT COUNT(*) FROM tblpur_petty_cash_advance_items WHERE advance_id = @pca) AS items_left,
       (SELECT COUNT(*) FROM tblpur_petty_cash_advance_approvals WHERE advance_id = @pca) AS approvals_left;

ROLLBACK;

SELECT '--- rolled back ---' AS step;
SELECT COUNT(*) AS rows_in_table FROM tblpur_petty_cash_advances;
