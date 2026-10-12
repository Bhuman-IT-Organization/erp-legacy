-- ============================================================
-- Opening balance rows show a blank Number and Payee in the register
--
-- The account register resolves Payee from tblacc_account_history.vendor /
-- .customer and Number from .number. The opening balance upload wrote neither,
-- and also left description and split empty, and posted with rel_id = 0 - so
-- there is no source document to read them back from either. In the register the
-- rows appear completely blank (seen on account 134, 190010 NBF Bank AED).
--
-- An opening balance genuinely has no vendor or customer: its counterparty is
-- 900000 Initial Balance Upload Account. So:
--
--   * number      -> OB-00001... , a stable reference derived from the row id
--   * description -> "Opening balance upload"
--   * split       -> the offsetting account, which lets the register show
--                    "Initial Balance Upload Account" in place of a payee
--
-- Only touches rows that are actually blank, so it is safe to re-run and cannot
-- overwrite anything entered by hand.
-- ============================================================

-- ── 1. Pair each opening balance row with its offsetting account ──
-- The upload posts in pairs: one line against 900000 and one against the bank or
-- cash account, same date and amount. Each side's split is the other side.
SET @ob_account = (SELECT `id` FROM `tblacc_accounts` WHERE `number` = '900000' LIMIT 1);

DROP TEMPORARY TABLE IF EXISTS tmp_ob_pairs;

CREATE TEMPORARY TABLE tmp_ob_pairs AS
SELECT h.`id`,
       CASE WHEN h.`account` = @ob_account
            THEN (
                SELECT o.`account` FROM `tblacc_account_history` o
                WHERE o.`rel_type` = 'deposit'
                  AND o.`date`     = h.`date`
                  AND o.`account` <> @ob_account
                  AND ROUND(o.`debit` + o.`credit`, 2) = ROUND(h.`debit` + h.`credit`, 2)
                LIMIT 1
            )
            ELSE @ob_account
       END AS split_account
FROM `tblacc_account_history` h
WHERE h.`rel_type` = 'deposit'
  AND (h.`rel_id` IS NULL OR h.`rel_id` = 0)
  AND (h.`split` IS NULL OR h.`split` = 0);

UPDATE `tblacc_account_history` h
JOIN tmp_ob_pairs p ON p.`id` = h.`id`
SET h.`split` = COALESCE(p.split_account, 0)
WHERE p.split_account IS NOT NULL;

DROP TEMPORARY TABLE IF EXISTS tmp_ob_pairs;

-- ── 2. Give every blank opening balance row a reference ──────
UPDATE `tblacc_account_history`
SET `number` = CONCAT('OB-', LPAD(`id`, 5, '0'))
WHERE `rel_type` = 'deposit'
  AND (`rel_id` IS NULL OR `rel_id` = 0)
  AND (`number` IS NULL OR `number` = '');

-- ── 3. And a description, so the row reads as something ──────
UPDATE `tblacc_account_history`
SET `description` = 'Opening balance upload'
WHERE `rel_type` = 'deposit'
  AND (`rel_id` IS NULL OR `rel_id` = 0)
  AND (`description` IS NULL OR `description` = '');
