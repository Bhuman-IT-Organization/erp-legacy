-- ============================================================================
-- VERIFICATION ONLY - runs inside a transaction and rolls back. Changes nothing.
-- ============================================================================
--
-- Walks a credit expense through its whole life and checks the double entry at
-- each step:
--
--   1. Expense recorded on credit   Dr expense account  / Cr vendor A/P
--   2. Part payment                 Dr vendor A/P       / Cr bank
--   3. Balance payment              Dr vendor A/P       / Cr bank
--   4. Reverse one payment          payment entry removed, A/P goes back up
--
-- The invariant being proved: once fully paid the vendor's A/P nets to zero for
-- that expense, and every voucher balances on its own.
--
-- Uses expense 6 (EXPINV00006, 5,000.00, vendor 42, A/P 230005 id 164) and
-- pays from 190010 NBF Main Bank AED (id 134).
-- ============================================================================

START TRANSACTION;

-- ---------------------------------------------------------------------------
-- Step 0: opening position
-- ---------------------------------------------------------------------------
SELECT '0. EXPENSE AS RECORDED' AS step;

SELECT a.number, a.name, h.debit, h.credit, h.number AS ref
FROM tblacc_account_history h
JOIN tblacc_accounts a ON a.id = h.account
WHERE h.rel_type = 'expense' AND h.rel_id = 6;

SELECT ROUND(SUM(debit), 2) AS dr, ROUND(SUM(credit), 2) AS cr,
       ROUND(SUM(debit) - SUM(credit), 2) AS must_be_zero
FROM tblacc_account_history
WHERE rel_type = 'expense' AND rel_id = 6;

-- ---------------------------------------------------------------------------
-- Step 1: part payment of 2,000
-- ---------------------------------------------------------------------------
INSERT INTO tblexpense_payments
  (expense_id, amount, paymentmode, gl_account, date, daterecorded, addedfrom, is_deleted)
VALUES (6, 2000.00, 1, 134, '2026-08-26', NOW(), 1, 0);
SET @pay1 = LAST_INSERT_ID();

INSERT INTO tblacc_account_history
  (account, debit, credit, description, rel_id, rel_type, datecreated, addedfrom, split, date, vendor, number)
VALUES
  (164, 2000.00, 0, 'Payment for expense EXPINV00006', @pay1, 'expense_payment', NOW(), 1, 134, '2026-08-26', 42, CONCAT('EXPPAY', LPAD(@pay1, 5, '0'))),
  (134, 0, 2000.00, 'Payment for expense EXPINV00006', @pay1, 'expense_payment', NOW(), 1, 164, '2026-08-26', 42, CONCAT('EXPPAY', LPAD(@pay1, 5, '0')));

-- ---------------------------------------------------------------------------
-- Step 2: balance payment of 3,000
-- ---------------------------------------------------------------------------
INSERT INTO tblexpense_payments
  (expense_id, amount, paymentmode, gl_account, date, daterecorded, addedfrom, is_deleted)
VALUES (6, 3000.00, 1, 134, '2026-08-27', NOW(), 1, 0);
SET @pay2 = LAST_INSERT_ID();

INSERT INTO tblacc_account_history
  (account, debit, credit, description, rel_id, rel_type, datecreated, addedfrom, split, date, vendor, number)
VALUES
  (164, 3000.00, 0, 'Payment for expense EXPINV00006', @pay2, 'expense_payment', NOW(), 1, 134, '2026-08-27', 42, CONCAT('EXPPAY', LPAD(@pay2, 5, '0'))),
  (134, 0, 3000.00, 'Payment for expense EXPINV00006', @pay2, 'expense_payment', NOW(), 1, 164, '2026-08-27', 42, CONCAT('EXPPAY', LPAD(@pay2, 5, '0')));

SELECT '2. FULLY PAID' AS step;

-- Every voucher must balance on its own
SELECT rel_type, rel_id, ROUND(SUM(debit), 2) AS dr, ROUND(SUM(credit), 2) AS cr
FROM tblacc_account_history
WHERE (rel_type = 'expense' AND rel_id = 6)
   OR (rel_type = 'expense_payment' AND rel_id IN (@pay1, @pay2))
GROUP BY rel_type, rel_id;

-- Net movement per account for this expense and its payments
SELECT a.number, a.name,
       ROUND(SUM(h.debit), 2) AS dr,
       ROUND(SUM(h.credit), 2) AS cr,
       ROUND(SUM(h.debit) - SUM(h.credit), 2) AS net_dr
FROM tblacc_account_history h
JOIN tblacc_accounts a ON a.id = h.account
WHERE (h.rel_type = 'expense' AND h.rel_id = 6)
   OR (h.rel_type = 'expense_payment' AND h.rel_id IN (@pay1, @pay2))
GROUP BY a.number, a.name
ORDER BY a.number;

-- Expected once fully paid:
--   410005 Telephone Expenses   net Dr  5000.00   (the cost, hits P&L)
--   230005 A/P Service Vendors  net      0.00     (owed, then settled)
--   190010 NBF Main Bank AED    net Cr  5000.00   (cash out)
SELECT 'A/P MUST BE ZERO ONCE FULLY PAID' AS check_name,
       ROUND(COALESCE(SUM(h.debit), 0) - COALESCE(SUM(h.credit), 0), 2) AS ap_net
FROM tblacc_account_history h
WHERE h.account = 164
  AND ((h.rel_type = 'expense' AND h.rel_id = 6)
    OR (h.rel_type = 'expense_payment' AND h.rel_id IN (@pay1, @pay2)));

-- Balance left per the application's own arithmetic
SELECT 'BALANCE LEFT MUST BE ZERO' AS check_name,
       ROUND(5000.00 - COALESCE(SUM(amount), 0), 2) AS balance_left
FROM tblexpense_payments
WHERE expense_id = 6 AND is_deleted = 0;

-- ---------------------------------------------------------------------------
-- Step 3: reverse the second payment
-- ---------------------------------------------------------------------------
UPDATE tblexpense_payments SET is_deleted = 1 WHERE id = @pay2;
DELETE FROM tblacc_account_history WHERE rel_type = 'expense_payment' AND rel_id = @pay2;

SELECT '3. AFTER REVERSING THE 3,000 PAYMENT' AS step;

SELECT 'A/P MUST BE BACK TO 3,000 CREDIT' AS check_name,
       ROUND(COALESCE(SUM(h.debit), 0) - COALESCE(SUM(h.credit), 0), 2) AS ap_net
FROM tblacc_account_history h
WHERE h.account = 164
  AND ((h.rel_type = 'expense' AND h.rel_id = 6)
    OR (h.rel_type = 'expense_payment' AND h.rel_id IN (@pay1, @pay2)));

SELECT 'BALANCE LEFT MUST BE 3,000' AS check_name,
       ROUND(5000.00 - COALESCE(SUM(amount), 0), 2) AS balance_left
FROM tblexpense_payments
WHERE expense_id = 6 AND is_deleted = 0;

SELECT 'REVERSED PAYMENT MUST LEAVE NO LEDGER ROWS' AS check_name,
       COUNT(*) AS must_be_zero
FROM tblacc_account_history
WHERE rel_type = 'expense_payment' AND rel_id = @pay2;

-- ---------------------------------------------------------------------------
-- Step 4: no unbalanced voucher anywhere in the ledger
-- ---------------------------------------------------------------------------
SELECT '4. LEDGER WIDE BALANCE CHECK' AS step;

SELECT rel_type, rel_id, ROUND(SUM(debit), 2) AS dr, ROUND(SUM(credit), 2) AS cr
FROM tblacc_account_history
WHERE rel_type IN ('expense', 'expense_payment')
GROUP BY rel_type, rel_id
HAVING ROUND(SUM(debit), 2) <> ROUND(SUM(credit), 2);

-- Nothing above means every expense and expense payment voucher balances.

SELECT 'ORPHAN LEDGER ROWS (payment gone but rows remain)' AS check_name,
       COUNT(*) AS must_be_zero
FROM tblacc_account_history h
LEFT JOIN tblexpense_payments p ON p.id = h.rel_id AND p.is_deleted = 0
WHERE h.rel_type = 'expense_payment' AND p.id IS NULL;

SELECT 'LEDGER ROWS WITHOUT A NUMBER' AS check_name,
       COUNT(*) AS must_be_zero
FROM tblacc_account_history
WHERE rel_type IN ('expense', 'expense_payment')
  AND (number IS NULL OR number = '');

ROLLBACK;

SELECT 'ROLLED BACK - NOTHING CHANGED' AS step;
SELECT COUNT(*) AS expense_payments_still_zero FROM tblexpense_payments;
