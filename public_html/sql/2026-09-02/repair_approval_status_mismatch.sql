-- ============================================================================
-- Realign purchase request / purchase order status with their approval records
-- ============================================================================
--
-- WHY
--   The document status column and the approval rows in tblpur_approval_details
--   can disagree. Purchase request 87 is the example: its only approval row is
--   approve = 2 (approved) yet status is still 5 (Sent for Approval), so every
--   screen reports it as awaiting approval.
--
--   approve_request() does set the status once check_approval_details() returns
--   true, so the live path is correct. Records can still be left behind by a
--   failed request, by an approval recorded while the request was routed to a
--   different backend, or by a direct database edit. This script brings the
--   status column back in line with the approval rows that actually exist.
--
-- RULES APPLIED
--   any approval row rejected (3)          -> status 3  Rejected
--   rows exist and every one approved (2)  -> status 2  Approved
--   at least one row still undecided (0)   -> status 5  Sent for Approval
--   no approval rows at all                -> left alone. Could be a draft that
--                                             was never sent, or the stuck state
--                                             where no approver was resolved.
--                                             Guessing here would be wrong.
--
--   Only rows that actually disagree are touched, and drafts (1) and cancelled
--   (4) documents are never changed - a draft has no business being retitled by
--   a repair script, and cancelled is a deliberate end state.
--
-- HOW TO RUN
--   Dry run first. It writes nothing and prints what it would change:
--       mysql -u root -p dbname < repair_approval_status_mismatch.sql
--
--   To apply, set the flag on the next line to 1 and run again.
--
--   Idempotent - running it twice changes nothing the second time.
-- ============================================================================

SET @COMMIT_CHANGES = 0;

SELECT CONCAT('Mode: ', IF(@COMMIT_CHANGES = 1, 'APPLY CHANGES', 'DRY RUN - nothing will be written')) AS mode;


-- ----------------------------------------------------------------------------
-- Derive the correct status for every document that has approval rows.
-- approve is varchar, so it is compared as a string and coalesced for safety.
-- ----------------------------------------------------------------------------
DROP TEMPORARY TABLE IF EXISTS tmp_approval_state;
CREATE TEMPORARY TABLE tmp_approval_state AS
SELECT
    rel_type,
    rel_id,
    COUNT(*)                                                       AS rows_total,
    SUM(COALESCE(approve, '0') = '3')                              AS rows_rejected,
    SUM(COALESCE(approve, '0') = '0')                              AS rows_pending,
    SUM(COALESCE(approve, '0') = '2')                              AS rows_approved,
    CASE
        WHEN SUM(COALESCE(approve, '0') = '3') > 0 THEN 3
        WHEN SUM(COALESCE(approve, '0') = '0') > 0 THEN 5
        WHEN SUM(COALESCE(approve, '0') = '2') = COUNT(*) THEN 2
        ELSE NULL
    END                                                            AS correct_status
FROM tblpur_approval_details
WHERE rel_type IN ('pur_request', 'pur_order')
GROUP BY rel_type, rel_id;


-- ----------------------------------------------------------------------------
-- Purchase requests that disagree
-- ----------------------------------------------------------------------------
SELECT '=== PURCHASE REQUESTS TO CHANGE ===' AS report;

SELECT r.id,
       r.pur_rq_code,
       r.status                AS status_now,
       s.correct_status        AS status_should_be,
       s.rows_total,
       s.rows_approved,
       s.rows_pending,
       s.rows_rejected
FROM tblpur_request r
JOIN tmp_approval_state s
  ON s.rel_type = 'pur_request' AND s.rel_id = r.id
WHERE s.correct_status IS NOT NULL
  AND r.status <> s.correct_status
  AND r.status NOT IN (1, 4)
ORDER BY r.id;


-- ----------------------------------------------------------------------------
-- Purchase orders that disagree
-- ----------------------------------------------------------------------------
SELECT '=== PURCHASE ORDERS TO CHANGE ===' AS report;

SELECT o.id,
       o.pur_order_number,
       o.approve_status        AS status_now,
       s.correct_status        AS status_should_be,
       s.rows_total,
       s.rows_approved,
       s.rows_pending,
       s.rows_rejected
FROM tblpur_orders o
JOIN tmp_approval_state s
  ON s.rel_type = 'pur_order' AND s.rel_id = o.id
WHERE s.correct_status IS NOT NULL
  AND o.approve_status <> s.correct_status
  AND o.approve_status NOT IN (1, 4)
ORDER BY o.id;


-- ----------------------------------------------------------------------------
-- Documents sitting in "Sent for Approval" with no approver at all.
-- Reported, never auto-changed. These are the ones a user cannot progress
-- because there is nobody to approve them; they need an approver assigning or
-- re-sending through the form.
-- ----------------------------------------------------------------------------
SELECT '=== SENT FOR APPROVAL BUT NO APPROVER ASSIGNED (manual attention) ===' AS report;

SELECT 'pur_request' AS doc, r.id, r.pur_rq_code AS code, r.status AS status_now
FROM tblpur_request r
LEFT JOIN tmp_approval_state s ON s.rel_type = 'pur_request' AND s.rel_id = r.id
WHERE r.status = 5 AND s.rel_id IS NULL AND COALESCE(r.is_deleted, 0) = 0
UNION ALL
SELECT 'pur_order', o.id, o.pur_order_number, o.approve_status
FROM tblpur_orders o
LEFT JOIN tmp_approval_state s ON s.rel_type = 'pur_order' AND s.rel_id = o.id
WHERE o.approve_status = 5 AND s.rel_id IS NULL AND COALESCE(o.is_deleted, 0) = 0;


-- ----------------------------------------------------------------------------
-- Back up the current values, then apply. Both are skipped on a dry run.
-- The backup table is suffixed with the date so repeat runs do not overwrite it.
-- ----------------------------------------------------------------------------
SET @backup := CONCAT('tblpur_status_repair_log_', DATE_FORMAT(NOW(), '%Y%m%d'));

SET @sql := IF(@COMMIT_CHANGES = 1,
    CONCAT('CREATE TABLE IF NOT EXISTS ', @backup, ' (
        doc          VARCHAR(20)  NOT NULL,
        doc_id       INT          NOT NULL,
        code         VARCHAR(100) NULL,
        status_before INT         NULL,
        status_after  INT         NULL,
        changed_at   DATETIME     NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'),
    'SELECT ''dry run - backup table not created'' AS skipped');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Record what is about to change
SET @sql := IF(@COMMIT_CHANGES = 1,
    CONCAT('INSERT INTO ', @backup, ' (doc, doc_id, code, status_before, status_after, changed_at)
        SELECT ''pur_request'', r.id, r.pur_rq_code, r.status, s.correct_status, NOW()
        FROM tblpur_request r
        JOIN tmp_approval_state s ON s.rel_type = ''pur_request'' AND s.rel_id = r.id
        WHERE s.correct_status IS NOT NULL AND r.status <> s.correct_status AND r.status NOT IN (1, 4)'),
    'SELECT ''dry run - nothing logged'' AS skipped');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@COMMIT_CHANGES = 1,
    CONCAT('INSERT INTO ', @backup, ' (doc, doc_id, code, status_before, status_after, changed_at)
        SELECT ''pur_order'', o.id, o.pur_order_number, o.approve_status, s.correct_status, NOW()
        FROM tblpur_orders o
        JOIN tmp_approval_state s ON s.rel_type = ''pur_order'' AND s.rel_id = o.id
        WHERE s.correct_status IS NOT NULL AND o.approve_status <> s.correct_status AND o.approve_status NOT IN (1, 4)'),
    'SELECT ''dry run - nothing logged'' AS skipped');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Apply
SET @sql := IF(@COMMIT_CHANGES = 1,
    'UPDATE tblpur_request r
       JOIN tmp_approval_state s ON s.rel_type = ''pur_request'' AND s.rel_id = r.id
        SET r.status = s.correct_status
      WHERE s.correct_status IS NOT NULL AND r.status <> s.correct_status AND r.status NOT IN (1, 4)',
    'SELECT ''dry run - purchase requests unchanged'' AS skipped');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(@COMMIT_CHANGES = 1,
    'UPDATE tblpur_orders o
       JOIN tmp_approval_state s ON s.rel_type = ''pur_order'' AND s.rel_id = o.id
        SET o.approve_status = s.correct_status
      WHERE s.correct_status IS NOT NULL AND o.approve_status <> s.correct_status AND o.approve_status NOT IN (1, 4)',
    'SELECT ''dry run - purchase orders unchanged'' AS skipped');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;


-- ----------------------------------------------------------------------------
-- Confirm nothing is left disagreeing
-- ----------------------------------------------------------------------------
SELECT '=== REMAINING MISMATCHES (should be 0 after an apply run) ===' AS report;

SELECT (
    SELECT COUNT(*) FROM tblpur_request r
    JOIN tmp_approval_state s ON s.rel_type = 'pur_request' AND s.rel_id = r.id
    WHERE s.correct_status IS NOT NULL AND r.status <> s.correct_status AND r.status NOT IN (1, 4)
) AS pur_requests_left,
(
    SELECT COUNT(*) FROM tblpur_orders o
    JOIN tmp_approval_state s ON s.rel_type = 'pur_order' AND s.rel_id = o.id
    WHERE s.correct_status IS NOT NULL AND o.approve_status <> s.correct_status AND o.approve_status NOT IN (1, 4)
) AS pur_orders_left;

DROP TEMPORARY TABLE IF EXISTS tmp_approval_state;
