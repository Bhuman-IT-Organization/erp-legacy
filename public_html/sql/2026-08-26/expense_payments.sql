-- ============================================================================
-- Expense payments: settle an expense recorded on credit
-- ============================================================================
--
-- An expense recorded with the "Credit" payment mode is not paid when it is
-- entered - the vendor is owed the amount and it sits on their accounts payable
-- account. There was no way to settle it afterwards, so the liability stayed on
-- the books forever.
--
-- This adds a payment record against an expense, mirroring
-- tblpur_invoice_payment for purchase invoices, so the same pattern applies:
--   * payments are soft deleted (is_deleted) so a reversal keeps its history
--   * the expense carries a payment_status of unpaid / partially_paid / paid
--   * each payment posts its own double entry, Dr accounts payable / Cr bank
--
-- No information_schema and no stored procedures - the live MySQL user is denied
-- access to information_schema. The ALTER statements will report
-- "#1060 Duplicate column name" on a second run; that error is expected and
-- harmless, nothing is lost. Everything else is guarded and re-runnable.
-- ============================================================================

-- ---------------------------------------------------------------------------
-- 1. Payments recorded against an expense
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `tblexpense_payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expense_id` int(11) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  -- Payment mode used to settle (bank, cash, cheque). Never "Credit".
  `paymentmode` int(11) DEFAULT NULL,
  -- Chart of accounts row that is credited, i.e. the bank or cash paid from
  `gl_account` int(11) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `transactionid` varchar(100) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `cheque_number` varchar(100) DEFAULT NULL,
  `cheque_bank_name` varchar(191) DEFAULT NULL,
  `cheque_date` date DEFAULT NULL,
  `daterecorded` datetime DEFAULT NULL,
  `addedfrom` int(11) DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `expense_id` (`expense_id`),
  KEY `is_deleted` (`is_deleted`),
  KEY `gl_account` (`gl_account`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- 2. Payment status on the expense itself
-- ---------------------------------------------------------------------------
-- Same three values tblpur_invoices.payment_status uses, so reports can treat
-- expenses and purchase invoices the same way.
-- Expect #1060 Duplicate column name on re-run - harmless.
ALTER TABLE `tblexpenses`
  ADD COLUMN `payment_status` VARCHAR(30) NULL DEFAULT NULL;

ALTER TABLE `tblexpenses`
  ADD INDEX `payment_status` (`payment_status`);

-- ---------------------------------------------------------------------------
-- 3. Backfill the status for existing expenses
-- ---------------------------------------------------------------------------
-- A credit expense with no payments is unpaid. Anything else was settled at the
-- moment it was recorded (the entry already credited bank or cash), so it counts
-- as paid and must not offer a payment button.
UPDATE `tblexpenses` e
SET e.`payment_status` = CASE
        WHEN e.`expinv_number` IS NOT NULL AND e.`expinv_number` <> '' THEN 'unpaid'
        ELSE 'paid'
    END
WHERE e.`payment_status` IS NULL OR e.`payment_status` = '';

-- ---------------------------------------------------------------------------
-- Verification (run manually, not part of the migration)
-- ---------------------------------------------------------------------------
-- SHOW COLUMNS FROM tblexpenses LIKE 'payment_status';
-- SHOW COLUMNS FROM tblexpense_payments;
--
-- SELECT e.id, e.expinv_number, e.amount, e.payment_status,
--        COALESCE(SUM(CASE WHEN p.is_deleted = 0 THEN p.amount END), 0) AS paid
-- FROM tblexpenses e
-- LEFT JOIN tblexpense_payments p ON p.expense_id = e.id
-- GROUP BY e.id
-- ORDER BY e.id;
--
-- Payment entries must balance:
-- SELECT rel_id, ROUND(SUM(debit),2) dr, ROUND(SUM(credit),2) cr
-- FROM tblacc_account_history WHERE rel_type = 'expense_payment'
-- GROUP BY rel_id HAVING dr <> cr;
