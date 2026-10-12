-- ---------------------------------------------------------------------------
-- 04  Make the 30 missing AP opening balance lines appear on the vendor
--     statement for the three USD vendors
-- ---------------------------------------------------------------------------
--
-- THE PROBLEM
--
-- Purchase_model::get_statement() renders a vendor's statement in that vendor's
-- own currency, and filters records with a strict equality when the vendor is
-- not on the base currency:
--
--     if ($currency->id == $base_currency->id) {
--         ... AND tblpur_invoices.currency IN (0, <base>)
--     } else {
--         ... AND tblpur_invoices.currency = <vendor currency>
--     }
--
-- There is no fallback to 0 or to base in the else branch. Every opening
-- balance record written by sql/2026-09-06/06 and /09 carries currency = 1
-- (AED). Three vendors are set to default_currency = 3 (USD), so all of their
-- opening balance records - invoices, carriers and advance payments alike - are
-- filtered out and their statement shows nothing but their one real invoice.
--
-- Affected, measured on nextgen_live:
--
--   VEN0015  GUANGZHOU YIDE PRINTING MACHINERY CO.,LTD   vendor 19   3 lines
--   VEN0037  DONGGUAN YUNCHEN PLATE MAKING CO., LTD      vendor 41  17 lines
--   VEN0039  WUXI KAICHUANG MOULD CO., LTD               vendor 43  10 lines
--                                                                  --------
--                                                                  30 lines
--
-- plus their 3 OBADV carrier invoices. The other 46 vendors and 150 lines are
-- unaffected and are not touched by this file.
--
--
-- WHAT THIS FILE DOES
--
-- Restates those records into the vendor's own currency, which is the only
-- shape get_statement() will show, following the convention the application
-- already uses for these very vendors. Their real invoices INV00063 and
-- INV00064 are stored as:
--
--     currency = 3   currency_rate = 0.272300   from_currency = 1   to_currency = 3
--
-- so the total is held in USD and the rate records the AED -> USD conversion.
-- This file writes the opening balance records the same way.
--
-- Only these columns change, and only on records whose invoice_number starts
-- with OB, for those three vendors:
--
--     tblpur_invoices        subtotal, total, currency, currency_rate,
--                            from_currency, to_currency
--     tblpur_invoice_payment amount
--
-- Nothing is inserted and nothing is deleted. The OB invoices carry no line
-- items (verified: 0 rows in tblitemable), so subtotal and total are the whole
-- of the money on the record. acc_mapping stays 0, so nothing posts to the
-- general ledger. Document numbering is untouched.
--
--
-- THE RATE, AND HOW TO NOT CONVERT
--
-- @rate below is the AED -> USD rate. 0.272300 is not invented here: it is the
-- rate already stored on INV00063 and INV00064 for two of these three vendors.
--
-- Set @rate = 1 instead if you want the figures left exactly as the spreadsheet
-- gave them. The lines will still appear, but AED amounts will then be printed
-- under the USD symbol on the statement and in the PDF sent to the vendor.
--
-- Consequence of converting, stated plainly: the AP Ageing Detail report sums
-- tblpur_invoices.total with no currency condition at all, so converting these
-- 14 visible payable invoices lowers that report by
-- 373,420.87 - 101,682.50 = 271,738.37. The report was already known to
-- overstate by the advance total (see sql/2026-09-06/README.md); this changes
-- the size of that gap. With @rate = 1 the ageing report does not move.
--
-- Each line is rounded to the cent on its own, so a vendor's converted total
-- can differ from the conversion of its total by a cent. VEN0039 lands on
-- -25,100.02 where converting the AED net in one step gives -25,100.01. That is
-- the sum of rounded parts against the rounded sum, and rounding each line is
-- the right choice: it is the individual figures that appear on the statement.
--
-- tblpur_vendor_opening_balance is deliberately left in AED. It is the audit
-- copy of what the spreadsheet said and should keep saying it.
--
--
-- RE-RUNNABLE
--
-- Both UPDATEs are guarded on currency = base, which is only true before the
-- first run. Payments are converted before the invoices, so on a second run the
-- guard has already flipped and both statements match zero rows. Running this
-- file twice cannot double convert.
--
-- REVERSIBLE
--
-- Every original value is copied into two backup tables before anything is
-- changed. The rollback is at the bottom of this file.
-- ---------------------------------------------------------------------------


-- ---------------------------------------------------------------------------
-- 0. Settings
-- ---------------------------------------------------------------------------

SET @base_cur = (SELECT id FROM tblcurrencies WHERE isdefault = 1);   -- 1, AED
SET @vend_cur = 3;                                                    -- USD
SET @rate     = 0.272300;                                             -- AED -> USD. Set to 1 to keep AED figures.

SELECT @base_cur AS base_currency, @vend_cur AS vendor_currency, @rate AS rate_applied;


-- ---------------------------------------------------------------------------
-- 1. Before: what is about to change
-- ---------------------------------------------------------------------------

SELECT '=== 1. records to be restated ===' AS report;

SELECT
    v.userid                      AS vendor_id,
    TRIM(v.vendor_code)           AS vendor_code,
    v.company,
    COUNT(*)                      AS ob_invoices,
    SUM(i.is_deleted = 0)         AS visible_now_after_fix,
    ROUND(SUM(i.total), 2)        AS total_aed_now,
    ROUND(SUM(ROUND(i.total * @rate, 2)), 2) AS total_after
FROM tblpur_invoices i
JOIN tblpur_vendor v ON v.userid = i.vendor
WHERE v.default_currency = @vend_cur
  AND i.invoice_number LIKE 'OB%'
  AND i.currency = @base_cur
GROUP BY v.userid, v.vendor_code, v.company
ORDER BY v.vendor_code;

SELECT
    COUNT(*)                                 AS advance_payments_to_restate,
    ROUND(SUM(p.amount), 2)                  AS amount_aed_now,
    ROUND(SUM(ROUND(p.amount * @rate, 2)), 2) AS amount_after
FROM tblpur_invoice_payment p
JOIN tblpur_invoices i ON i.id = p.pur_invoice
JOIN tblpur_vendor v   ON v.userid = i.vendor
WHERE v.default_currency = @vend_cur
  AND i.invoice_number LIKE 'OBADV%'
  AND i.currency = @base_cur;


-- ---------------------------------------------------------------------------
-- 2. Backup, before any change
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tblpur_ob_curfix_inv_bak` (
    `id`            int(11) NOT NULL,
    `subtotal`      decimal(15,2) DEFAULT NULL,
    `total`         decimal(15,2) DEFAULT NULL,
    `currency`      int(11) DEFAULT NULL,
    `currency_rate` decimal(15,6) DEFAULT NULL,
    `from_currency` varchar(20) DEFAULT NULL,
    `to_currency`   varchar(20) DEFAULT NULL,
    `taken_at`      datetime DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `tblpur_ob_curfix_pay_bak` (
    `id`       int(11) NOT NULL,
    `amount`   decimal(15,2) DEFAULT NULL,
    `taken_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `tblpur_ob_curfix_inv_bak`
    (`id`, `subtotal`, `total`, `currency`, `currency_rate`, `from_currency`, `to_currency`, `taken_at`)
SELECT i.id, i.subtotal, i.total, i.currency, i.currency_rate, i.from_currency, i.to_currency, NOW()
FROM tblpur_invoices i
JOIN tblpur_vendor v ON v.userid = i.vendor
WHERE v.default_currency = @vend_cur
  AND i.invoice_number LIKE 'OB%'
  AND i.currency = @base_cur
  AND NOT EXISTS (SELECT 1 FROM `tblpur_ob_curfix_inv_bak` b WHERE b.id = i.id);

INSERT INTO `tblpur_ob_curfix_pay_bak` (`id`, `amount`, `taken_at`)
SELECT p.id, p.amount, NOW()
FROM tblpur_invoice_payment p
JOIN tblpur_invoices i ON i.id = p.pur_invoice
JOIN tblpur_vendor v   ON v.userid = i.vendor
WHERE v.default_currency = @vend_cur
  AND i.invoice_number LIKE 'OBADV%'
  AND i.currency = @base_cur
  AND NOT EXISTS (SELECT 1 FROM `tblpur_ob_curfix_pay_bak` b WHERE b.id = p.id);

SELECT '=== 2. backup taken ===' AS report;
SELECT (SELECT COUNT(*) FROM `tblpur_ob_curfix_inv_bak`) AS invoices_backed_up,
       (SELECT COUNT(*) FROM `tblpur_ob_curfix_pay_bak`) AS payments_backed_up;


-- ---------------------------------------------------------------------------
-- 3. Advance payments first
--
-- Must run before the invoices, because its guard reads the parent invoice's
-- currency. Doing it in this order is what makes the whole file safe to re-run.
-- ---------------------------------------------------------------------------

UPDATE tblpur_invoice_payment p
JOIN tblpur_invoices i ON i.id = p.pur_invoice
JOIN tblpur_vendor v   ON v.userid = i.vendor
SET p.amount = ROUND(p.amount * @rate, 2)
WHERE v.default_currency = @vend_cur
  AND i.invoice_number LIKE 'OBADV%'
  AND i.currency = @base_cur;

SELECT '=== 3. advance payments restated ===' AS report, ROW_COUNT() AS rows_changed;


-- ---------------------------------------------------------------------------
-- 4. The opening balance invoices and their carriers
-- ---------------------------------------------------------------------------

UPDATE tblpur_invoices i
JOIN tblpur_vendor v ON v.userid = i.vendor
SET i.subtotal      = ROUND(i.subtotal * @rate, 2),
    i.total         = ROUND(i.total * @rate, 2),
    i.currency      = @vend_cur,
    i.currency_rate = @rate,
    i.from_currency = @base_cur,
    i.to_currency   = @vend_cur
WHERE v.default_currency = @vend_cur
  AND i.invoice_number LIKE 'OB%'
  AND i.currency = @base_cur;

SELECT '=== 4. opening balance invoices restated ===' AS report, ROW_COUNT() AS rows_changed;


-- ---------------------------------------------------------------------------
-- 5. Verify, using the same filters get_statement() applies
-- ---------------------------------------------------------------------------

SELECT '=== 5a. per vendor: sheet lines vs lines now on the statement ===' AS report;

SELECT
    CASE WHEN sheet.sheet_lines
              - COALESCE(inv.visible_invoices, 0)
              - COALESCE(pay.visible_payments, 0) = 0
         THEN 'COMPLETE' ELSE 'STILL MISSING' END                          AS status,
    TRIM(v.vendor_code)                                                    AS vendor_code,
    v.company,
    v.userid                                                               AS vendor_id,
    sheet.sheet_lines,
    COALESCE(inv.visible_invoices, 0) + COALESCE(pay.visible_payments, 0)   AS shown,
    sheet.sheet_lines
        - COALESCE(inv.visible_invoices, 0)
        - COALESCE(pay.visible_payments, 0)                                AS missing,
    ROUND(sheet.sheet_net_aed, 2)                                          AS sheet_net_aed,
    ROUND(sheet.sheet_net_aed * @rate, 2)                                  AS expected_net_usd,
    ROUND(COALESCE(inv.visible_amount, 0) - COALESCE(pay.visible_amount, 0), 2) AS statement_net
FROM tblpur_vendor v
JOIN (
    SELECT s.vendor_id, COUNT(*) AS sheet_lines, SUM(-s.amount) AS sheet_net_aed
    FROM ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026 s
    GROUP BY s.vendor_id
) sheet ON sheet.vendor_id = v.userid
LEFT JOIN (
    SELECT i.vendor, COUNT(*) AS visible_invoices, SUM(i.total) AS visible_amount
    FROM tblpur_invoices i
    JOIN tblpur_vendor vv ON vv.userid = i.vendor
    WHERE i.invoice_number LIKE 'OB%' AND i.invoice_number NOT LIKE 'OBADV%'
      AND (i.is_deleted = 0 OR i.is_deleted IS NULL)
      AND ((COALESCE(NULLIF(vv.default_currency, 0), @base_cur) = @base_cur AND i.currency IN (0, @base_cur))
        OR (COALESCE(NULLIF(vv.default_currency, 0), @base_cur) <> @base_cur
            AND i.currency = COALESCE(NULLIF(vv.default_currency, 0), @base_cur)))
    GROUP BY i.vendor
) inv ON inv.vendor = v.userid
LEFT JOIN (
    SELECT i.vendor, COUNT(*) AS visible_payments, SUM(p.amount) AS visible_amount
    FROM tblpur_invoice_payment p
    JOIN tblpur_invoices i ON i.id = p.pur_invoice
    JOIN tblpur_vendor vv  ON vv.userid = i.vendor
    WHERE i.invoice_number LIKE 'OBADV%'
      AND p.approval_status = 2 AND p.is_deleted = 0
      AND (i.is_deleted = 0 OR i.is_deleted IS NULL)
      AND ((COALESCE(NULLIF(vv.default_currency, 0), @base_cur) = @base_cur AND i.currency IN (0, @base_cur))
        OR (COALESCE(NULLIF(vv.default_currency, 0), @base_cur) <> @base_cur
            AND i.currency = COALESCE(NULLIF(vv.default_currency, 0), @base_cur)))
    GROUP BY i.vendor
) pay ON pay.vendor = v.userid
WHERE v.default_currency = @vend_cur
ORDER BY v.vendor_code;

SELECT '=== 5b. every vendor, count of any still missing a line (want 0) ===' AS report;

SELECT COUNT(*) AS vendors_still_missing_lines
FROM (
    SELECT sheet.vendor_id,
           sheet.sheet_lines
             - COALESCE(inv.visible_invoices, 0)
             - COALESCE(pay.visible_payments, 0) AS missing
    FROM (
        SELECT s.vendor_id, COUNT(*) AS sheet_lines
        FROM ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026 s
        GROUP BY s.vendor_id
    ) sheet
    LEFT JOIN (
        SELECT i.vendor, COUNT(*) AS visible_invoices
        FROM tblpur_invoices i
        JOIN tblpur_vendor vv ON vv.userid = i.vendor
        WHERE i.invoice_number LIKE 'OB%' AND i.invoice_number NOT LIKE 'OBADV%'
          AND (i.is_deleted = 0 OR i.is_deleted IS NULL)
          AND ((COALESCE(NULLIF(vv.default_currency, 0), @base_cur) = @base_cur AND i.currency IN (0, @base_cur))
            OR (COALESCE(NULLIF(vv.default_currency, 0), @base_cur) <> @base_cur
                AND i.currency = COALESCE(NULLIF(vv.default_currency, 0), @base_cur)))
        GROUP BY i.vendor
    ) inv ON inv.vendor = sheet.vendor_id
    LEFT JOIN (
        SELECT i.vendor, COUNT(*) AS visible_payments
        FROM tblpur_invoice_payment p
        JOIN tblpur_invoices i ON i.id = p.pur_invoice
        JOIN tblpur_vendor vv  ON vv.userid = i.vendor
        WHERE i.invoice_number LIKE 'OBADV%'
          AND p.approval_status = 2 AND p.is_deleted = 0
          AND (i.is_deleted = 0 OR i.is_deleted IS NULL)
          AND ((COALESCE(NULLIF(vv.default_currency, 0), @base_cur) = @base_cur AND i.currency IN (0, @base_cur))
            OR (COALESCE(NULLIF(vv.default_currency, 0), @base_cur) <> @base_cur
                AND i.currency = COALESCE(NULLIF(vv.default_currency, 0), @base_cur)))
        GROUP BY i.vendor
    ) pay ON pay.vendor = sheet.vendor_id
) x
WHERE missing <> 0;

SELECT '=== 5c. nothing outside the three vendors moved ===' AS report;

SELECT
    (SELECT COUNT(*) FROM tblpur_invoices
       WHERE invoice_number LIKE 'OB%' AND currency = @base_cur)   AS ob_records_still_aed,
    (SELECT COUNT(*) FROM tblpur_invoices
       WHERE invoice_number LIKE 'OB%' AND currency = @vend_cur)   AS ob_records_now_usd,
    (SELECT COUNT(*) FROM tblpur_invoices
       WHERE invoice_number NOT LIKE 'OB%' AND is_deleted = 0)     AS real_invoices_untouched,
    (SELECT ROUND(SUM(total), 2) FROM tblpur_invoices
       WHERE invoice_number LIKE 'OB%' AND currency = @base_cur)   AS aed_ob_total,
    (SELECT `option_val` FROM tblpurchase_option
       WHERE `option_name` = 'next_inv_number')                    AS next_inv_number;

SELECT '=== 5d. the two real USD invoices still show ===' AS report;

SELECT i.id, i.invoice_number, i.vendor, i.total, i.currency
FROM tblpur_invoices i
WHERE i.invoice_number IN ('INV00063', 'INV00064');

SELECT '=== 5e. audit copy left in AED on purpose ===' AS report;

SELECT COUNT(*) AS rows_in_audit_copy, ROUND(SUM(amount), 2) AS audit_total_aed
FROM tblpur_vendor_opening_balance;


-- ---------------------------------------------------------------------------
-- 6. ROLLBACK
--
-- Puts every value back exactly as it was and drops the backup tables.
-- Run the whole block, in this order.
-- ---------------------------------------------------------------------------
--
-- UPDATE tblpur_invoices i
-- JOIN `tblpur_ob_curfix_inv_bak` b ON b.id = i.id
-- SET i.subtotal      = b.subtotal,
--     i.total         = b.total,
--     i.currency      = b.currency,
--     i.currency_rate = b.currency_rate,
--     i.from_currency = b.from_currency,
--     i.to_currency   = b.to_currency;
--
-- UPDATE tblpur_invoice_payment p
-- JOIN `tblpur_ob_curfix_pay_bak` b ON b.id = p.id
-- SET p.amount = b.amount;
--
-- DROP TABLE `tblpur_ob_curfix_inv_bak`;
-- DROP TABLE `tblpur_ob_curfix_pay_bak`;
--
-- ---------------------------------------------------------------------------
-- Section 6 is also how you change your mind about the rate: roll back, edit
-- @rate at the top, run the file again.
-- ---------------------------------------------------------------------------
