-- ---------------------------------------------------------------------------
-- 01  Cheque register: one row per physical cheque
-- ---------------------------------------------------------------------------
--
-- THE PROBLEM
--
-- The register is a list of cheques, but it was being written as a list of the
-- documents cheques paid. Expenses_model::register_expense_payment_cheque() and
-- Purchase_model::add_invoice_payment() both inserted a row per payment, guarded
-- only by "not twice for the same payment id". A batch payment writes one payment
-- row per document, so one physical cheque produced one register row per document:
--
--   000381  National Bank of Fujairah  ETISALAT                        14 rows   4,349.07
--   000654  NBF                        ROKN AL MANARA TRANSPORT L.L.C   6 rows   1,467.50
--   000392  National Bank of Fujairah  ETIHAD WATER & ELECTRICITY       3 rows  14,707.38
--
-- 23 rows for 3 cheques. Each row held only its own document's slice of the amount
-- and carried its own status and its own approval, so the same cheque could be half
-- approved and half not.
--
-- Both callers also hardcoded status = 1 (Received). That is the entry point of the
-- INCOMING lifecycle. For an outgoing cheque it is not a state the flow knows, so
-- acc_cheque_can_transition() refused every move with "status not in flow" - which
-- is why these cheques could not be issued, presented or cleared at all.
--
--
-- WHAT THIS FILE DOES
--
-- 1. Backs up every row of tblcheque_management.
-- 2. Collapses duplicate rows onto the earliest row of each cheque, setting its
--    amount to the cheque total and listing every document reference it covers.
-- 3. Moves the audit trail of the removed rows onto the surviving row, so no
--    history is lost, and records the merge itself.
-- 4. Deletes the now redundant rows.
-- 5. Moves outgoing cheques out of statuses 1 and 7 into 12 Prepared, the entry
--    point of their own lifecycle.
--
-- The cheque is identified by number + direction + party. Bank name is deliberately
-- NOT part of the key: it is free text and the same bank is typed "NBF" on one row
-- and "National Bank of Fujairah" on another.
--
-- APPROVAL IS DELIBERATELY NOT CARRIED OVER. Some rows sat at approval_status = 1,
-- whose meaning is ambiguous - the original schema comment says 1 = approved, the
-- newer lifecycle code says 1 = awaiting. The surviving row keeps its own value, so
-- a merged cheque may need sending for approval again. That is the safe direction:
-- the alternative is releasing a cheque for issue on the strength of a value nobody
-- can define.
--
-- Re-runnable: the aggregate finds nothing once the rows are collapsed.
-- Reversible: see the rollback at the bottom.
-- ---------------------------------------------------------------------------


-- ---------------------------------------------------------------------------
-- 0. Before
-- ---------------------------------------------------------------------------

SELECT '=== 0a. duplicates now ===' AS report;

SELECT `cheque_number`, `cheque_type`, `rel_name`,
       COUNT(*) AS register_rows, ROUND(SUM(`amount`), 2) AS cheque_total
FROM `tblcheque_management`
WHERE `cheque_number` <> ''
GROUP BY `cheque_number`, `cheque_type`, COALESCE(`rel_id`, 0), `rel_name`
HAVING COUNT(*) > 1
ORDER BY register_rows DESC;

SELECT '=== 0b. statuses now ===' AS report;

SELECT `cheque_type`, `status`, COUNT(*) AS rows_
FROM `tblcheque_management`
GROUP BY `cheque_type`, `status`
ORDER BY `cheque_type`, `status`;


-- ---------------------------------------------------------------------------
-- 1. Backup, before anything changes
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tblcheque_management_bak_20260912` LIKE `tblcheque_management`;

INSERT INTO `tblcheque_management_bak_20260912`
SELECT * FROM `tblcheque_management`
WHERE `id` NOT IN (SELECT `id` FROM `tblcheque_management_bak_20260912`);

SELECT '=== 1. backup ===' AS report;
SELECT COUNT(*) AS rows_backed_up FROM `tblcheque_management_bak_20260912`;


-- ---------------------------------------------------------------------------
-- 2. Which row survives, and what it should hold
-- ---------------------------------------------------------------------------

DROP TABLE IF EXISTS `tblcheque_dedupe_map`;

CREATE TABLE `tblcheque_dedupe_map` (
    `keep_id`       INT(11) NOT NULL,
    `cheque_number` VARCHAR(100) NOT NULL,
    `cheque_type`   VARCHAR(20) NOT NULL,
    `party`         INT(11) NOT NULL,
    `total_amount`  DECIMAL(15,2) NOT NULL,
    `row_count`     INT(11) NOT NULL,
    `refs`          TEXT NULL,
    PRIMARY KEY (`keep_id`),
    KEY `idx_key` (`cheque_number`, `cheque_type`, `party`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tblcheque_dedupe_map`
    (`keep_id`, `cheque_number`, `cheque_type`, `party`, `total_amount`, `row_count`, `refs`)
SELECT
    MIN(`id`),
    `cheque_number`,
    `cheque_type`,
    COALESCE(`rel_id`, 0),
    ROUND(SUM(`amount`), 2),
    COUNT(*),
    GROUP_CONCAT(DISTINCT NULLIF(`invoice_ref`, '') ORDER BY `invoice_ref` SEPARATOR ', ')
FROM `tblcheque_management`
WHERE `cheque_number` <> ''
GROUP BY `cheque_number`, `cheque_type`, COALESCE(`rel_id`, 0)
HAVING COUNT(*) > 1;

SELECT '=== 2. cheques to collapse ===' AS report;
SELECT `keep_id`, `cheque_number`, `row_count`, `total_amount`, `refs`
FROM `tblcheque_dedupe_map` ORDER BY `row_count` DESC;


-- ---------------------------------------------------------------------------
-- 3. The surviving row becomes the whole cheque
-- ---------------------------------------------------------------------------

UPDATE `tblcheque_management` c
JOIN `tblcheque_dedupe_map` m ON m.`keep_id` = c.`id`
SET c.`amount`      = m.`total_amount`,
    c.`invoice_ref` = m.`refs`;

SELECT '=== 3. survivors updated ===' AS report, ROW_COUNT() AS rows_changed;


-- ---------------------------------------------------------------------------
-- 4. Keep the history: move the removed rows' audit trail to the survivor
-- ---------------------------------------------------------------------------

UPDATE `tblcheque_transactions` t
JOIN `tblcheque_management` c ON c.`id` = t.`cheque_id`
JOIN `tblcheque_dedupe_map` m
      ON m.`cheque_number` = c.`cheque_number`
     AND m.`cheque_type`   = c.`cheque_type`
     AND m.`party`         = COALESCE(c.`rel_id`, 0)
SET t.`cheque_id` = m.`keep_id`
WHERE t.`cheque_id` <> m.`keep_id`;

SELECT '=== 4. audit rows repointed ===' AS report, ROW_COUNT() AS rows_changed;

INSERT INTO `tblcheque_transactions`
    (`cheque_id`, `action`, `from_status`, `to_status`, `action_date`, `user_id`, `notes`)
SELECT
    m.`keep_id`,
    'merged_duplicates',
    c.`status`,
    c.`status`,
    NOW(),
    NULL,
    CONCAT('Collapsed ', m.`row_count`, ' register rows for cheque ', m.`cheque_number`,
           ' into one. Amount set to the cheque total ', m.`total_amount`,
           '. Documents covered: ', COALESCE(m.`refs`, '-'), '.')
FROM `tblcheque_dedupe_map` m
JOIN `tblcheque_management` c ON c.`id` = m.`keep_id`;


-- ---------------------------------------------------------------------------
-- 5. Remove the redundant rows
-- ---------------------------------------------------------------------------

DELETE c FROM `tblcheque_management` c
JOIN `tblcheque_dedupe_map` m
      ON m.`cheque_number` = c.`cheque_number`
     AND m.`cheque_type`   = c.`cheque_type`
     AND m.`party`         = COALESCE(c.`rel_id`, 0)
WHERE c.`id` <> m.`keep_id`;

SELECT '=== 5. redundant rows removed ===' AS report, ROW_COUNT() AS rows_removed;


-- ---------------------------------------------------------------------------
-- 6. Put outgoing cheques inside their own lifecycle
--
-- 1 Received is the incoming entry point; 7 Post-Dated is in neither flow. The PDC
-- marker is the is_pdc flag, which is left alone, so nothing is lost by moving a
-- post dated cheque to Prepared.
-- ---------------------------------------------------------------------------

UPDATE `tblcheque_management`
SET `status` = 12
WHERE `cheque_type` = 'outgoing'
  AND `status` IN (1, 7);

SELECT '=== 6. outgoing cheques moved to Prepared ===' AS report, ROW_COUNT() AS rows_changed;


-- ---------------------------------------------------------------------------
-- 7. After
-- ---------------------------------------------------------------------------

SELECT '=== 7a. any duplicates left (want none) ===' AS report;

SELECT `cheque_number`, `cheque_type`, COUNT(*) AS register_rows
FROM `tblcheque_management`
WHERE `cheque_number` <> ''
GROUP BY `cheque_number`, `cheque_type`, COALESCE(`rel_id`, 0)
HAVING COUNT(*) > 1;

SELECT '=== 7b. the three cheques, now one row each ===' AS report;

SELECT `id`, `cheque_number`, `cheque_bank_name`, `rel_name`, `amount`, `status`,
       `approval_status`, `invoice_ref`
FROM `tblcheque_management`
WHERE `cheque_number` IN ('000381', '000654', '000392')
ORDER BY `cheque_number`;

SELECT '=== 7c. statuses after ===' AS report;

SELECT `cheque_type`, `status`, COUNT(*) AS rows_
FROM `tblcheque_management`
GROUP BY `cheque_type`, `status`
ORDER BY `cheque_type`, `status`;

SELECT '=== 7d. register total is unchanged ===' AS report;

SELECT
    (SELECT ROUND(SUM(`amount`), 2) FROM `tblcheque_management_bak_20260912`) AS total_before,
    (SELECT ROUND(SUM(`amount`), 2) FROM `tblcheque_management`)              AS total_after,
    (SELECT COUNT(*) FROM `tblcheque_management_bak_20260912`)                AS rows_before,
    (SELECT COUNT(*) FROM `tblcheque_management`)                             AS rows_after;

DROP TABLE `tblcheque_dedupe_map`;


-- ---------------------------------------------------------------------------
-- 8. ROLLBACK
--
-- Restores every row exactly as it was. Run the whole block.
-- ---------------------------------------------------------------------------
--
-- DELETE FROM `tblcheque_management`;
-- INSERT INTO `tblcheque_management` SELECT * FROM `tblcheque_management_bak_20260912`;
-- DELETE FROM `tblcheque_transactions` WHERE `action` = 'merged_duplicates';
--
-- The repointed audit rows stay on the surviving cheque. They are history either way
-- and no row is lost; only which cheque they hang off differs.
--
-- DROP TABLE `tblcheque_management_bak_20260912`;
-- ---------------------------------------------------------------------------
