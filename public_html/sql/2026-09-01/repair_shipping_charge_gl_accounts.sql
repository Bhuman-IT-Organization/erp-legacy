-- ---------------------------------------------------------------------------
-- Repair shipping charge GL entries posted BEFORE the debit-account fix.
--
-- Until now the freight debit was hardcoded to account 112 (Inventory Raw
-- Materials) for every shipping charge. It should follow the purchase order:
--
--   1. tblpur_orders.ledger_account          the account coded on the order
--   2. tblpur_request_types.ledger_account   the default for the order's TYPE
--                                            (Asset, Raw Materials, Spares, ...)
--   3. 112                                   last resort only
--
-- New and re-saved charges already post correctly. This file corrects the
-- entries that were written by the old code and never reposted.
--
-- ONLY the debit account is changed, and only where it disagrees with the
-- resolved account. Amounts, dates, VAT lines and AP credits are untouched, so
-- every entry stays balanced and stays in its original period. The paired
-- credit's `split` column is corrected alongside, since it names the
-- contra-account.
--
-- REVERSIBLE. Step 1 copies every row it is about to touch into
-- tblacc_account_history_bak_20260901. Step 6 shows how to roll back.
--
-- RE-RUNNABLE. Once corrected, the UPDATEs match nothing and report 0 rows.
--
--   mysql -u root nextgen_live -e "source sql/2026-09-01/repair_shipping_charge_gl_accounts.sql"
-- ---------------------------------------------------------------------------

-- ── 1. Backup ──────────────────────────────────────────────────────────────
-- Plain CREATE TABLE, not CREATE TABLE ... LIKE, so the backup keeps whatever
-- rows a previous run already saved instead of being silently replaced.

CREATE TABLE IF NOT EXISTS `tblacc_account_history_bak_20260901` (
  `id`          INT(11) NOT NULL,
  `account`     INT(11) NULL,
  `split`       INT(11) NULL,
  `debit`       DECIMAL(15,2) NULL,
  `credit`      DECIMAL(15,2) NULL,
  `date`        DATE NULL,
  `rel_id`      INT(11) NULL,
  `rel_type`    VARCHAR(64) NULL,
  `backed_up`   DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tblacc_account_history_bak_20260901`
  (`id`, `account`, `split`, `debit`, `credit`, `date`, `rel_id`, `rel_type`, `backed_up`)
SELECT ah.`id`, ah.`account`, ah.`split`, ah.`debit`, ah.`credit`, ah.`date`,
       ah.`rel_id`, ah.`rel_type`, NOW()
FROM   `tblacc_account_history` ah
WHERE  ah.`rel_type` = 'inv_shipping_charge';

-- ── 2. What the account SHOULD be, per charge ─────────────────────────────
-- Mirrors _shipping_charge_debit_account() in the controller exactly.

DROP TEMPORARY TABLE IF EXISTS `tmp_sc_target`;

CREATE TEMPORARY TABLE `tmp_sc_target` AS
SELECT  sc.`id`                AS shipping_charge_id,
        sc.`shipping_number`,
        po.`pur_order_number`,
        po.`type`              AS po_type,
        COALESCE(
            -- 1. the order's own account, if it exists
            (SELECT a1.`id` FROM `tblacc_accounts` a1
              WHERE a1.`id` = NULLIF(po.`ledger_account`, 0)),
            -- 2. the default for the order's type, if it exists
            (SELECT a2.`id` FROM `tblacc_accounts` a2
               JOIN `tblpur_request_types` rt ON rt.`ledger_account` = a2.`id`
              WHERE rt.`type_code` = po.`type`),
            -- 3. last resort
            112
        )                      AS target_account
FROM    `tblinv_shipping_charges` sc
LEFT JOIN `tblpur_invoices` i  ON i.`id`  = sc.`invoice_id`
LEFT JOIN `tblpur_orders`   po ON po.`id` = i.`pur_order`;

-- ── 3. Preview: what will change ──────────────────────────────────────────

SELECT '--- Entries that will be corrected ---' AS step;

SELECT  t.`shipping_number`,
        t.`pur_order_number`,
        t.`po_type`,
        rt.`name`            AS item_type,
        ah.`id`              AS gl_row,
        ah.`debit`,
        ah.`date`,
        ah.`account`         AS posted_to,
        pa.`name`            AS posted_to_name,
        t.`target_account`   AS should_be,
        ta.`name`            AS should_be_name
FROM    `tblacc_account_history` ah
JOIN    `tmp_sc_target` t   ON t.`shipping_charge_id` = ah.`rel_id`
LEFT JOIN `tblpur_request_types` rt ON rt.`type_code` = t.`po_type`
LEFT JOIN `tblacc_accounts` pa ON pa.`id` = ah.`account`
LEFT JOIN `tblacc_accounts` ta ON ta.`id` = t.`target_account`
WHERE   ah.`rel_type` = 'inv_shipping_charge'
  AND   ah.`debit`  > 0
  AND   ah.`account` <> 124              -- leave the VAT debit alone
  AND   ah.`account` <> t.`target_account`
ORDER BY t.`shipping_charge_id`;

-- ── 4. Correct the goods debit line ───────────────────────────────────────

UPDATE `tblacc_account_history` ah
JOIN   `tmp_sc_target` t ON t.`shipping_charge_id` = ah.`rel_id`
SET    ah.`account` = t.`target_account`
WHERE  ah.`rel_type` = 'inv_shipping_charge'
  AND  ah.`debit`  > 0
  AND  ah.`account` <> 124
  AND  ah.`account` <> t.`target_account`;

-- ── 5. Correct the contra-account on the paired AP credit ─────────────────
-- The credit that offsets the goods debit carries the freight account in
-- `split`. Only the credit whose amount matches the goods debit is touched, so
-- the VAT credit keeps pointing at 124.

UPDATE `tblacc_account_history` ah
JOIN   `tmp_sc_target` t ON t.`shipping_charge_id` = ah.`rel_id`
SET    ah.`split` = t.`target_account`
WHERE  ah.`rel_type` = 'inv_shipping_charge'
  AND  ah.`credit` > 0
  AND  ah.`split` <> 124
  AND  ah.`split` <> t.`target_account`
  AND  ah.`credit` = (
         SELECT d.`debit` FROM `tblacc_account_history` d
         WHERE  d.`rel_type` = 'inv_shipping_charge'
           AND  d.`rel_id`   = ah.`rel_id`
           AND  d.`debit`    > 0
           AND  d.`account`  = t.`target_account`
         LIMIT 1
       );

-- ── 6. Verify ─────────────────────────────────────────────────────────────

SELECT '--- After repair: every charge vs its target account ---' AS step;

SELECT  t.`shipping_number`,
        t.`pur_order_number`,
        rt.`name`   AS item_type,
        pa.`name`   AS debited_to,
        ta.`name`   AS target,
        CASE WHEN ah.`id` IS NULL          THEN 'no GL entries posted'
             WHEN ah.`account` = t.`target_account` THEN 'ok'
             ELSE 'STILL WRONG' END AS status
FROM    `tmp_sc_target` t
LEFT JOIN `tblacc_account_history` ah
       ON ah.`rel_type` = 'inv_shipping_charge'
      AND ah.`rel_id`   = t.`shipping_charge_id`
      AND ah.`debit`    > 0
      AND ah.`account` <> 124
LEFT JOIN `tblpur_request_types` rt ON rt.`type_code` = t.`po_type`
LEFT JOIN `tblacc_accounts` pa ON pa.`id` = ah.`account`
LEFT JOIN `tblacc_accounts` ta ON ta.`id` = t.`target_account`
ORDER BY t.`shipping_charge_id`;

SELECT '--- Every shipping charge entry must still balance ---' AS step;

SELECT  ah.`rel_id` AS shipping_charge_id,
        ROUND(SUM(ah.`debit`), 2)  AS total_debit,
        ROUND(SUM(ah.`credit`), 2) AS total_credit,
        CASE WHEN ROUND(SUM(ah.`debit`), 2) = ROUND(SUM(ah.`credit`), 2)
             THEN 'balanced' ELSE 'OUT OF BALANCE' END AS status
FROM    `tblacc_account_history` ah
WHERE   ah.`rel_type` = 'inv_shipping_charge'
GROUP BY ah.`rel_id`
ORDER BY ah.`rel_id`;

SELECT '--- Dates must be unchanged ---' AS step;

SELECT  ah.`rel_id` AS shipping_charge_id,
        GROUP_CONCAT(DISTINCT ah.`date`)  AS dates_now,
        GROUP_CONCAT(DISTINCT bk.`date`)  AS dates_before
FROM    `tblacc_account_history` ah
JOIN    `tblacc_account_history_bak_20260901` bk ON bk.`id` = ah.`id`
WHERE   ah.`rel_type` = 'inv_shipping_charge'
GROUP BY ah.`rel_id`
ORDER BY ah.`rel_id`;

DROP TEMPORARY TABLE IF EXISTS `tmp_sc_target`;

-- ---------------------------------------------------------------------------
-- ROLLBACK, if ever needed:
--
--   UPDATE tblacc_account_history ah
--   JOIN   tblacc_account_history_bak_20260901 bk ON bk.id = ah.id
--   SET    ah.account = bk.account, ah.split = bk.split;
--
-- Once you are satisfied, the backup can be dropped:
--   DROP TABLE tblacc_account_history_bak_20260901;
-- ---------------------------------------------------------------------------
