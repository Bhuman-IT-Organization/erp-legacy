-- ---------------------------------------------------------------------------
-- 02  Cheque register: soft delete
-- ---------------------------------------------------------------------------
--
-- cheque_management_delete() removed the row outright AND removed its whole audit
-- trail from tblcheque_transactions with it. A cheque is a financial record: who
-- wrote it, for how much, who approved it and when it cleared. Deleting that leaves
-- no trace that the cheque ever existed, and no way to answer why a payment has no
-- cheque behind it.
--
-- Adds the three columns a soft delete needs. The row stays, the audit trail stays,
-- and every list, count and report filters it out instead.
--
-- Idempotent: each ALTER is guarded, so running this more than once is safe.
-- ---------------------------------------------------------------------------

SET @add_is_deleted := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `tblcheque_management` ADD COLUMN `is_deleted` TINYINT(1) NOT NULL DEFAULT 0 AFTER `notes`',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tblcheque_management'
      AND COLUMN_NAME  = 'is_deleted'
);
PREPARE stmt FROM @add_is_deleted;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Who removed it and when. Without these a soft delete is just a hidden row - there
-- is nothing to say who hid it.
SET @add_deleted_by := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `tblcheque_management` ADD COLUMN `deleted_by` INT(11) NULL DEFAULT NULL AFTER `is_deleted`',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tblcheque_management'
      AND COLUMN_NAME  = 'deleted_by'
);
PREPARE stmt FROM @add_deleted_by;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_deleted_at := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `tblcheque_management` ADD COLUMN `deleted_at` DATETIME NULL DEFAULT NULL AFTER `deleted_by`',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tblcheque_management'
      AND COLUMN_NAME  = 'deleted_at'
);
PREPARE stmt FROM @add_deleted_at;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Every list query filters on this column
SET @add_idx := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `tblcheque_management` ADD INDEX `idx_is_deleted` (`is_deleted`)',
        'DO 0'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tblcheque_management'
      AND INDEX_NAME   = 'idx_is_deleted'
);
PREPARE stmt FROM @add_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;


-- ---------------------------------------------------------------------------
-- Verification
-- ---------------------------------------------------------------------------

SELECT '=== columns added ===' AS report;

SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME   = 'tblcheque_management'
  AND COLUMN_NAME IN ('is_deleted', 'deleted_by', 'deleted_at')
ORDER BY ORDINAL_POSITION;

SELECT '=== nothing is deleted yet ===' AS report;

SELECT `is_deleted`, COUNT(*) AS rows_
FROM `tblcheque_management`
GROUP BY `is_deleted`;


-- ---------------------------------------------------------------------------
-- ROLLBACK
--
-- ALTER TABLE `tblcheque_management`
--   DROP COLUMN `is_deleted`,
--   DROP COLUMN `deleted_by`,
--   DROP COLUMN `deleted_at`;
--
-- Dropping is_deleted makes previously hidden cheques visible again, which is the
-- correct outcome: the rows were never removed.
-- ---------------------------------------------------------------------------
