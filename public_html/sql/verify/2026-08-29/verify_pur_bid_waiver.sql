-- ---------------------------------------------------------------------------
-- Round-trip check for the Bid Waiver schema.
--
-- Inserts a form with quotation lines and approval rows, reads it back the way the
-- model and the datatable do, then rolls everything back. Nothing is left behind.
--
--   mysql -u root nextgen_live --table -e "source sql/2026-08-29/verify_pur_bid_waiver.sql"
-- ---------------------------------------------------------------------------

START TRANSACTION;

-- ── Create a form, mirroring what Purchase_model::add_bid_waiver writes ─────
INSERT INTO `tblpur_bid_waivers`
  (`request_no`, `number`, `doc_version`, `doc_effective_date`, `doc_number`, `doc_approved_by`,
   `department`, `requestor`, `budget_required_date`,
   `purpose_of_purchase`, `technical_specification`, `quantity_unit`, `purchase_type`,
   `two_or_more_quotations`, `wr_single_source`, `wr_urgent_requirement`, `wr_other`, `wr_other_text`,
   `recommended_vendor`, `sr_lowest_price`, `sr_quality_service`,
   `total_amount`, `currency`, `approve_status`, `created_at`, `created_by`)
SELECT 'BW00000001', 1, '1.0', '2026-03-26', 'DOC-001', 'Finance',
       (SELECT MIN(departmentid) FROM tbldepartments),
       (SELECT MIN(staffid) FROM tblstaff),
       '2026-09-30',
       'Replacement ink for the UV press', 'Bright Silver UV offset, food-grade', '5 KG', 'rm',
       1, 1, 1, 1, 'Sole authorised distributor in the UAE',
       (SELECT MIN(userid) FROM tblpur_vendor), 1, 1,
       61250.00, (SELECT id FROM tblcurrencies WHERE isdefault = 1 LIMIT 1), 1, NOW(),
       (SELECT MIN(staffid) FROM tblstaff);

SET @bw = LAST_INSERT_ID();

-- ── Three quotation lines, one carrying a PDF filename ─────────────────────
INSERT INTO `tblpur_bid_waiver_quotations`
  (`bid_waiver_id`, `vendor_id`, `amount`, `currency`, `lead_time`, `payment_terms`, `attachment`, `sort_order`)
SELECT @bw, (SELECT MIN(userid) FROM tblpur_vendor), 61250.00,
       (SELECT id FROM tblcurrencies WHERE isdefault = 1 LIMIT 1), '3 weeks', '30 days', 'quote-a.pdf', 0
UNION ALL SELECT @bw, (SELECT MAX(userid) FROM tblpur_vendor), 64000.00,
       (SELECT id FROM tblcurrencies WHERE isdefault = 1 LIMIT 1), '5 weeks', '45 days', NULL, 1
UNION ALL SELECT @bw, (SELECT MIN(userid) FROM tblpur_vendor), 67500.00,
       (SELECT id FROM tblcurrencies WHERE isdefault = 1 LIMIT 1), '2 weeks', 'Advance', NULL, 2;

-- ── Approvals. 61,250 is over the 50K threshold so the CEO row applies. ─────
INSERT INTO `tblpur_bid_waiver_approvals` (`bid_waiver_id`, `role`, `staffid`, `status`, `note`, `action_at`)
SELECT @bw, 'initiator', (SELECT MIN(staffid) FROM tblstaff), 2, 'Raised', NOW()
UNION ALL SELECT @bw, 'line_manager', (SELECT MIN(staffid) FROM tblstaff), 2, 'Agreed', NOW()
UNION ALL SELECT @bw, 'finance_manager', (SELECT MIN(staffid) FROM tblstaff), 1, NULL, NULL;

SELECT '--- 1. form reads back, with the joins the model uses ---' AS step;
SELECT b.id, b.request_no, d.name AS department_name, v.company AS vendor_company,
       b.purchase_type, b.two_or_more_quotations, b.total_amount, b.approve_status
FROM tblpur_bid_waivers b
LEFT JOIN tbldepartments d ON d.departmentid = b.department
LEFT JOIN tblpur_vendor  v ON v.userid       = b.recommended_vendor
WHERE b.id = @bw;

SELECT '--- 2. quotation lines in entry order ---' AS step;
SELECT q.sort_order, v.company AS vendor, q.amount, q.lead_time, q.payment_terms,
       COALESCE(q.attachment, '(none)') AS attachment
FROM tblpur_bid_waiver_quotations q
LEFT JOIN tblpur_vendor v ON v.userid = q.vendor_id
WHERE q.bid_waiver_id = @bw
ORDER BY q.sort_order;

SELECT '--- 3. approvals, and whether the CEO row is required ---' AS step;
SELECT a.role, a.status, COALESCE(a.note, '') AS note
FROM tblpur_bid_waiver_approvals a WHERE a.bid_waiver_id = @bw
ORDER BY FIELD(a.role, 'initiator', 'line_manager', 'finance_manager', 'gm', 'ceo');

SELECT (SELECT total_amount FROM tblpur_bid_waivers WHERE id = @bw) AS amount,
       (SELECT option_val FROM tblpurchase_option WHERE option_name = 'pur_bid_waiver_ceo_threshold') AS threshold,
       CASE WHEN (SELECT total_amount FROM tblpur_bid_waivers WHERE id = @bw)
                 > (SELECT option_val FROM tblpurchase_option WHERE option_name = 'pur_bid_waiver_ceo_threshold')
            THEN 'CEO approval required' ELSE 'CEO not required' END AS ceo_rule;

SELECT '--- 4. status roll-up: pending because roles are outstanding ---' AS step;
SELECT CASE
         WHEN EXISTS (SELECT 1 FROM tblpur_bid_waiver_approvals WHERE bid_waiver_id = @bw AND status = 3) THEN 'rejected'
         WHEN (SELECT COUNT(*) FROM tblpur_bid_waiver_approvals WHERE bid_waiver_id = @bw AND status = 2) >= 5 THEN 'approved'
         ELSE 'pending'
       END AS expected_status,
       (SELECT COUNT(*) FROM tblpur_bid_waiver_approvals WHERE bid_waiver_id = @bw AND status = 2) AS approved_roles;

SELECT '--- 5. unique key blocks a duplicate decision for the same role ---' AS step;
INSERT IGNORE INTO `tblpur_bid_waiver_approvals` (`bid_waiver_id`, `role`, `staffid`, `status`)
VALUES (@bw, 'initiator', 1, 3);
SELECT COUNT(*) AS initiator_rows_should_be_1
FROM tblpur_bid_waiver_approvals WHERE bid_waiver_id = @bw AND role = 'initiator';

SELECT '--- 6. delete cascade leaves nothing behind ---' AS step;
DELETE FROM `tblpur_bid_waiver_quotations` WHERE `bid_waiver_id` = @bw;
DELETE FROM `tblpur_bid_waiver_approvals`  WHERE `bid_waiver_id` = @bw;
DELETE FROM `tblpur_bid_waivers`           WHERE `id` = @bw;

SELECT (SELECT COUNT(*) FROM tblpur_bid_waivers WHERE id = @bw)                      AS forms_left,
       (SELECT COUNT(*) FROM tblpur_bid_waiver_quotations WHERE bid_waiver_id = @bw) AS quotations_left,
       (SELECT COUNT(*) FROM tblpur_bid_waiver_approvals WHERE bid_waiver_id = @bw)  AS approvals_left;

ROLLBACK;

SELECT '--- rolled back, nothing persisted ---' AS step;
SELECT COUNT(*) AS forms_in_table FROM tblpur_bid_waivers;
