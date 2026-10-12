-- ---------------------------------------------------------------------------
-- Cheque lifecycle and department based approval
--
-- Adds the department a cheque belongs to, so the approval chain configured in
-- Accounting > Settings > Approval Settings can be resolved per department the
-- same way asset requests are.
--
-- Status codes are APPENDED, never renumbered - existing rows keep their
-- meaning. The two lifecycles are:
--
--   Incoming (sales)   1 Received  -> 3 Deposited -> 11 In Clearing -> 4 Cleared
--                      3 or 11     -> 5 Bounced
--                      any open    -> 6 Cancelled
--
--   Outgoing (purchase) 12 Prepared -> 8 Issued -> 13 Presented -> 4 Cleared
--                       13          -> 14 Returned
--                       any open    -> 6 Cancelled
--
-- Leaving 12 Prepared requires an approved chain, which is why approval_status
-- is indexed here.
--
-- Idempotent: the ALTERs are guarded, so it is safe to run more than once.
-- ---------------------------------------------------------------------------

-- department: which approval chain applies. NULL / 0 falls back to the chain
-- configured for all departments.
SET @add_department := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `tblcheque_management` ADD COLUMN `department` INT(11) NULL DEFAULT NULL AFTER `bank_account_id`',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tblcheque_management'
      AND COLUMN_NAME  = 'department'
);
PREPARE stmt FROM @add_department;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- When the cheque was sent for approval. Distinct from approved_at, so a cheque
-- awaiting approval can be told apart from one never submitted.
SET @add_sent := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `tblcheque_management` ADD COLUMN `approval_sent_at` DATETIME NULL DEFAULT NULL AFTER `approval_status`',
        'DO 0'
    )
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tblcheque_management'
      AND COLUMN_NAME  = 'approval_sent_at'
);
PREPARE stmt FROM @add_sent;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @add_idx := (
    SELECT IF(
        COUNT(*) = 0,
        'ALTER TABLE `tblcheque_management` ADD INDEX `approval_status` (`approval_status`)',
        'DO 0'
    )
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME   = 'tblcheque_management'
      AND INDEX_NAME   = 'approval_status'
);
PREPARE stmt FROM @add_idx;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Existing outgoing cheques still sitting at 1 Received have no meaning in the
-- outgoing lifecycle, whose entry point is 12 Prepared. Moved so they are not
-- stranded outside their own flow. Incoming cheques are left alone.
UPDATE `tblcheque_management`
SET `status` = 12
WHERE `cheque_type` = 'outgoing'
  AND `status` = 1;
