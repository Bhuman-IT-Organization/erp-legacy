-- ============================================================
-- Expenses on credit -> vendor-facing expense invoice (EXPINV)
--
-- An expense bought on credit is not paid at the time it is recorded: the vendor
-- is owed the money. It therefore needs a document the vendor can be shown, and
-- it has to appear on their statement.
--
--   * A "Credit" payment mode, flagged expenses_only so it shows on the expense
--     form and nowhere else. Invoice payment screens already exclude
--     expenses_only modes (see Purchase::batch_payment_modal and
--     Admin\Expenses which loads modes with invoices_only != 1), so no other
--     form can pick it up.
--   * Its credit side defaults to 180090 Prepaid Expenses, held as an option so
--     it can be repointed without touching code.
--   * expinv_number on tblexpenses holds the document number, EXPINV00001...
--
-- Safe to re-run.
-- ============================================================

-- ── 1. The Credit payment mode, expenses only ────────────────
INSERT INTO `tblpayment_modes`
  (`name`, `description`, `show_on_pdf`, `invoices_only`, `expenses_only`, `selected_by_default`, `active`)
SELECT 'Credit', 'Expense purchased on credit - the vendor is owed the amount', 0, 0, 1, 0, 1
FROM (SELECT 1) t
WHERE NOT EXISTS (
  SELECT 1 FROM `tblpayment_modes` WHERE `name` = 'Credit'
);

-- Remember which mode id means "credit" so the code never matches on the name
INSERT INTO `tbloptions` (`name`, `value`, `autoload`)
SELECT 'expense_credit_payment_mode',
       (SELECT `id` FROM `tblpayment_modes` WHERE `name` = 'Credit' LIMIT 1),
       1
FROM (SELECT 1) t
WHERE NOT EXISTS (
  SELECT 1 FROM `tbloptions` WHERE `name` = 'expense_credit_payment_mode'
);

-- ── 2. Default credit-side GL account: 180090 Prepaid Expenses ─
INSERT INTO `tbloptions` (`name`, `value`, `autoload`)
SELECT 'expense_credit_gl_account',
       COALESCE((SELECT `id` FROM `tblacc_accounts` WHERE `number` = '180090' LIMIT 1), 0),
       1
FROM (SELECT 1) t
WHERE NOT EXISTS (
  SELECT 1 FROM `tbloptions` WHERE `name` = 'expense_credit_gl_account'
);

-- ── 3. The expense invoice number ────────────────────────────
SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `tblexpenses` ADD COLUMN `expinv_number` VARCHAR(50) NULL DEFAULT NULL, ADD KEY `idx_expinv_number` (`expinv_number`)',
    'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblexpenses'
    AND COLUMN_NAME = 'expinv_number'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
