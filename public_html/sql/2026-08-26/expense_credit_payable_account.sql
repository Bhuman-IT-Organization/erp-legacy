-- ============================================================================
-- Credit expenses: post the credit side to the vendor's accounts payable
-- ============================================================================
--
-- An expense recorded with the "Credit" payment mode is owed to the vendor. It
-- was defaulting its credit side to 180090 Prepaid Expenses (account id 129),
-- which is a current ASSET. The double entry balanced, but the balance sheet
-- then read as though the company had prepaid the supplier rather than owed
-- them, and the amount never reached A/P ageing.
--
-- The correct credit account is the vendor's own accounts payable account
-- (tblpur_vendor.ledger_account) - the same account the purchase invoice and
-- batch payment paths already post to, so a vendor's A/P balance stays in one
-- place. 230005 A/P Service Vendors (id 164) is the fallback when a vendor has
-- no account set, or when the expense has no vendor at all.
--
-- This file also backfills `number` and `description` on existing expense
-- ledger rows. They were being written empty, which left the Number column
-- blank in the account register.
--
-- Written without information_schema and without stored procedures because the
-- live MySQL user is denied access to information_schema. Re-runnable: every
-- statement is either an idempotent UPDATE or guarded by a NOT EXISTS.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. Fallback credit account: 180090 Prepaid Expenses -> 230005 A/P Service
-- ---------------------------------------------------------------------------
-- Resolved by account number rather than a hard coded id so this works on any
-- database where the chart of accounts was seeded independently.
UPDATE `tbloptions`
SET `value` = (SELECT `id` FROM `tblacc_accounts` WHERE `number` = '230005' LIMIT 1)
WHERE `name` = 'expense_credit_gl_account'
  AND EXISTS (SELECT 1 FROM (SELECT `id` FROM `tblacc_accounts` WHERE `number` = '230005' LIMIT 1) x)
  AND `value` <> (SELECT `id` FROM `tblacc_accounts` WHERE `number` = '230005' LIMIT 1);

-- Create the option if it is missing entirely (fresh install ordering)
INSERT INTO `tbloptions` (`name`, `value`, `autoload`)
SELECT 'expense_credit_gl_account',
       (SELECT `id` FROM `tblacc_accounts` WHERE `number` = '230005' LIMIT 1),
       1
FROM (SELECT 1) t
WHERE NOT EXISTS (SELECT 1 FROM `tbloptions` WHERE `name` = 'expense_credit_gl_account')
  AND EXISTS (SELECT 1 FROM `tblacc_accounts` WHERE `number` = '230005');

-- ---------------------------------------------------------------------------
-- 2. Repoint the stored credit account on existing credit expenses
-- ---------------------------------------------------------------------------
-- Only expenses that are still sitting on the old asset default are touched, so
-- a deliberate manual choice (expense 7 already used A/P Service Vendors) is
-- left exactly as it was.
UPDATE `tblexpenses` e
LEFT JOIN `tblpur_vendor` v ON v.`userid` = e.`vendor`
SET e.`credit_gl_account` = COALESCE(
        NULLIF(v.`ledger_account`, 0),
        (SELECT `value` FROM `tbloptions` WHERE `name` = 'expense_credit_gl_account' LIMIT 1)
    )
WHERE e.`expinv_number` IS NOT NULL
  AND e.`expinv_number` <> ''
  AND e.`credit_gl_account` = (SELECT `id` FROM `tblacc_accounts` WHERE `number` = '180090' LIMIT 1);

-- ---------------------------------------------------------------------------
-- 3. Repoint the posted ledger rows to match
-- ---------------------------------------------------------------------------
-- The credit leg carries the payable account...
UPDATE `tblacc_account_history` h
JOIN `tblexpenses` e
  ON e.`id` = h.`rel_id`
 AND h.`rel_type` = 'expense'
SET h.`account` = e.`credit_gl_account`
WHERE e.`expinv_number` IS NOT NULL
  AND e.`expinv_number` <> ''
  AND e.`credit_gl_account` IS NOT NULL
  AND e.`credit_gl_account` > 0
  AND h.`credit` > 0
  AND h.`debit` = 0
  AND h.`account` <> e.`credit_gl_account`;

-- ...and the debit legs point at it through `split`, which drives the "Split"
-- column in the register.
UPDATE `tblacc_account_history` h
JOIN `tblexpenses` e
  ON e.`id` = h.`rel_id`
 AND h.`rel_type` = 'expense'
SET h.`split` = e.`credit_gl_account`
WHERE e.`expinv_number` IS NOT NULL
  AND e.`expinv_number` <> ''
  AND e.`credit_gl_account` IS NOT NULL
  AND e.`credit_gl_account` > 0
  AND h.`debit` > 0
  AND h.`split` <> e.`credit_gl_account`;

-- The credit leg's own `split` points back at the debited expense account. Left
-- alone deliberately - it already holds the correct value.

-- ---------------------------------------------------------------------------
-- 4. Reference number on every expense ledger row
-- ---------------------------------------------------------------------------
-- Credit expenses use their EXPINV number, everything else gets EXP-<id>. Only
-- blank values are filled, so this never overwrites a real reference.
UPDATE `tblacc_account_history` h
JOIN `tblexpenses` e
  ON e.`id` = h.`rel_id`
 AND h.`rel_type` = 'expense'
SET h.`number` = CASE
        WHEN e.`expinv_number` IS NOT NULL AND e.`expinv_number` <> '' THEN e.`expinv_number`
        ELSE CONCAT('EXP-', e.`id`)
    END
WHERE h.`number` IS NULL OR h.`number` = '';

-- ---------------------------------------------------------------------------
-- 5. Description on every expense ledger row
-- ---------------------------------------------------------------------------
-- The note is optional and was usually empty. Fall back to the expense name,
-- then the category name, so the register always reads as something.
UPDATE `tblacc_account_history` h
JOIN `tblexpenses` e
  ON e.`id` = h.`rel_id`
 AND h.`rel_type` = 'expense'
LEFT JOIN `tblexpenses_categories` c ON c.`id` = e.`category`
SET h.`description` = COALESCE(
        NULLIF(TRIM(e.`expense_name`), ''),
        NULLIF(TRIM(c.`name`), ''),
        'Expense'
    )
WHERE h.`description` IS NULL OR TRIM(h.`description`) = '';

-- ---------------------------------------------------------------------------
-- Verification (run manually, not part of the migration)
-- ---------------------------------------------------------------------------
-- SELECT e.id, e.expinv_number, e.vendor, v.ledger_account AS vendor_ap,
--        e.credit_gl_account, e.ledger_account AS debit_account
-- FROM tblexpenses e
-- LEFT JOIN tblpur_vendor v ON v.userid = e.vendor
-- WHERE e.expinv_number IS NOT NULL AND e.expinv_number <> '';
--
-- SELECT h.rel_id, a.number, a.name, h.debit, h.credit, h.number, h.description
-- FROM tblacc_account_history h
-- JOIN tblacc_accounts a ON a.id = h.account
-- WHERE h.rel_type = 'expense'
-- ORDER BY h.rel_id, h.id;
--
-- Every expense row should balance:
-- SELECT rel_id, SUM(debit) AS dr, SUM(credit) AS cr
-- FROM tblacc_account_history WHERE rel_type = 'expense'
-- GROUP BY rel_id HAVING ROUND(SUM(debit),2) <> ROUND(SUM(credit),2);
