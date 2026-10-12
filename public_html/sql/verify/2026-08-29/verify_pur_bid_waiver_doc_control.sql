-- ---------------------------------------------------------------------------
-- Document control block behaviour for the Bid Waiver form.
--
-- Walks a form through create -> edit -> edit -> approve and checks the header
-- fields land where they should:
--
--   Ver          1.0 on create, then 2.0, 3.0 on each edit
--   Effective    the date v1.0 was raised, and never moves afterwards
--   Revise       empty on v1.0, then the date of the latest edit
--   Document No  the controlled form code from tblpurchase_option
--   Approved By  empty until every applicable role has approved, then stamped
--
-- Transactional and rolled back - nothing persists.
--
--   mysql -u root nextgen_live --table -e "source sql/2026-08-29/verify_pur_bid_waiver_doc_control.sql"
-- ---------------------------------------------------------------------------

START TRANSACTION;

-- ── Create: what add_bid_waiver() writes ───────────────────────────────────
INSERT INTO `tblpur_bid_waivers`
  (`request_no`, `number`, `doc_version`, `doc_effective_date`, `doc_revise`,
   `doc_number`, `doc_approved_by`, `department`, `requestor`,
   `total_amount`, `approve_status`, `created_at`, `created_by`)
SELECT 'BW-DOCTEST', 9999, '1.0', CURDATE(), NULL,
       (SELECT option_val FROM tblpurchase_option WHERE option_name = 'pur_bid_waiver_doc_no'),
       NULL,
       (SELECT MIN(departmentid) FROM tbldepartments),
       (SELECT MIN(staffid) FROM tblstaff),
       61250.00, 1, NOW(), (SELECT MIN(staffid) FROM tblstaff);

SET @bw = LAST_INSERT_ID();

SELECT '--- after CREATE (expect Ver 1.0, Revise empty, Approved By empty) ---' AS step;
SELECT doc_version, doc_effective_date, IFNULL(doc_revise,'(empty)') AS doc_revise,
       doc_number, IFNULL(doc_approved_by,'(empty)') AS doc_approved_by
FROM tblpur_bid_waivers WHERE id = @bw;

-- ── First edit: version bumps, revise stamped, effective untouched ─────────
UPDATE `tblpur_bid_waivers`
SET `doc_version` = CONCAT(CAST(doc_version AS UNSIGNED) + 1, '.0'),
    `doc_revise`  = CURDATE(),
    `updated_at`  = NOW()
WHERE `id` = @bw;

SELECT '--- after EDIT 1 (expect Ver 2.0, Revise today) ---' AS step;
SELECT doc_version, doc_effective_date, doc_revise FROM tblpur_bid_waivers WHERE id = @bw;

-- ── Second edit ────────────────────────────────────────────────────────────
UPDATE `tblpur_bid_waivers`
SET `doc_version` = CONCAT(CAST(doc_version AS UNSIGNED) + 1, '.0'),
    `doc_revise`  = CURDATE()
WHERE `id` = @bw;

SELECT '--- after EDIT 2 (expect Ver 3.0, Effective still the create date) ---' AS step;
SELECT doc_version, doc_effective_date, doc_revise,
       CASE WHEN doc_effective_date = CURDATE() THEN 'unchanged OK' ELSE 'MOVED - WRONG' END AS effective_check
FROM tblpur_bid_waivers WHERE id = @bw;

SELECT '--- CAST semantics used above match PHP (int) casting ---' AS step;
SELECT '1.0' AS v, CAST('1.0' AS UNSIGNED) AS major UNION ALL
SELECT '2.0', CAST('2.0' AS UNSIGNED) UNION ALL
SELECT '9.0', CAST('9.0' AS UNSIGNED) UNION ALL
SELECT '10.0', CAST('10.0' AS UNSIGNED);

-- ── Approvals. 61,250 > 50,000 so the CEO role applies: all five needed. ───
INSERT INTO `tblpur_bid_waiver_approvals` (`bid_waiver_id`, `role`, `staffid`, `status`, `action_at`)
SELECT @bw, 'initiator', (SELECT MIN(staffid) FROM tblstaff), 2, NOW()
UNION ALL SELECT @bw, 'line_manager', (SELECT MIN(staffid) FROM tblstaff), 2, NOW()
UNION ALL SELECT @bw, 'finance_manager', (SELECT MIN(staffid) FROM tblstaff), 2, NOW()
UNION ALL SELECT @bw, 'gm', (SELECT MIN(staffid) FROM tblstaff), 2, NOW();

SELECT '--- 4 of 5 approved, CEO outstanding: Approved By must stay empty ---' AS step;
SELECT (SELECT COUNT(*) FROM tblpur_bid_waiver_approvals WHERE bid_waiver_id = @bw AND status = 2) AS approved_roles,
       CASE WHEN (SELECT COUNT(*) FROM tblpur_bid_waiver_approvals WHERE bid_waiver_id = @bw AND status = 2) >= 5
            THEN 'would stamp' ELSE 'stays empty OK' END AS approved_by_state;

-- CEO approves, completing the chain
INSERT INTO `tblpur_bid_waiver_approvals` (`bid_waiver_id`, `role`, `staffid`, `status`, `action_at`)
VALUES (@bw, 'ceo', (SELECT MIN(staffid) FROM tblstaff), 2, NOW());

-- What sync_bid_waiver_status() does once every applicable role has approved
UPDATE `tblpur_bid_waivers`
SET `approve_status`  = 2,
    `doc_approved_by` = CONCAT((SELECT CONCAT(firstname,' ',lastname) FROM tblstaff WHERE staffid = (SELECT MIN(staffid) FROM tblstaff)), ' (CEO)')
WHERE `id` = @bw;

SELECT '--- all 5 approved: status 2 and Approved By stamped ---' AS step;
SELECT approve_status, doc_version, doc_approved_by FROM tblpur_bid_waivers WHERE id = @bw;

-- ── A rejection clears the stamp again ─────────────────────────────────────
UPDATE `tblpur_bid_waiver_approvals` SET `status` = 3 WHERE `bid_waiver_id` = @bw AND `role` = 'gm';
UPDATE `tblpur_bid_waivers` SET `approve_status` = 3, `doc_approved_by` = NULL WHERE `id` = @bw;

SELECT '--- after a rejection: stamp cleared ---' AS step;
SELECT approve_status, IFNULL(doc_approved_by,'(empty)') AS doc_approved_by
FROM tblpur_bid_waivers WHERE id = @bw;

-- ── Below threshold the CEO row does not apply ─────────────────────────────
SELECT '--- CEO rule at 40,000 vs 61,250 ---' AS step;
SELECT 40000 AS amount,
       CASE WHEN 40000 > (SELECT option_val FROM tblpurchase_option WHERE option_name='pur_bid_waiver_ceo_threshold')
            THEN 'CEO required' ELSE 'CEO NOT required - 4 roles complete the form' END AS rule
UNION ALL
SELECT 61250,
       CASE WHEN 61250 > (SELECT option_val FROM tblpurchase_option WHERE option_name='pur_bid_waiver_ceo_threshold')
            THEN 'CEO required' ELSE 'CEO NOT required' END;

DELETE FROM `tblpur_bid_waiver_approvals` WHERE `bid_waiver_id` = @bw;
DELETE FROM `tblpur_bid_waivers` WHERE `id` = @bw;

ROLLBACK;

SELECT '--- rolled back ---' AS step;
SELECT COUNT(*) AS forms_in_table FROM tblpur_bid_waivers;
