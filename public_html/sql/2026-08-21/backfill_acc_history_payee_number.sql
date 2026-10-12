-- ============================================================
-- Maintenance: backfill missing payee / reference on account history
--
-- acc_account_history rows are written from many places across the purchase,
-- warehouse and accounting modules. Some posting routines forget to stamp
-- vendor / customer / number, which leaves the account register's Payee and
-- Number columns blank (purchase_payment was one such case).
--
-- The register now resolves missing values at read time
-- (Accounting_model::acc_history_fill_payee_and_number), so the grid is correct
-- regardless. This script repairs the stored columns as well, for any report
-- that reads them directly.
--
-- Safe to re-run: only touches rows that are actually missing a value.
-- ============================================================

-- ── Purchase payments ───────────────────────────────────────
UPDATE `tblacc_account_history` h
JOIN `tblpur_invoice_payment` p ON p.`id` = h.`rel_id`
LEFT JOIN `tblpur_invoices` i   ON i.`id` = p.`pur_invoice`
SET h.`vendor` = COALESCE(NULLIF(h.`vendor`, 0), i.`vendor`),
    h.`number` = COALESCE(NULLIF(h.`number`, ''),
                          NULLIF(p.`cheque_number`, ''),
                          CONCAT('PP-', LPAD(p.`id`, 5, '0')))
WHERE h.`rel_type` = 'purchase_payment'
  AND (h.`vendor` IS NULL OR h.`vendor` = 0 OR h.`number` IS NULL OR h.`number` = '');

-- ── Purchase invoices ───────────────────────────────────────
UPDATE `tblacc_account_history` h
JOIN `tblpur_invoices` i ON i.`id` = h.`rel_id`
SET h.`vendor` = COALESCE(NULLIF(h.`vendor`, 0), i.`vendor`),
    h.`number` = COALESCE(NULLIF(h.`number`, ''),
                          NULLIF(i.`invoice_number`, ''),
                          CONCAT('PI-', LPAD(i.`id`, 5, '0')))
WHERE h.`rel_type` = 'purchase_invoice'
  AND (h.`vendor` IS NULL OR h.`vendor` = 0 OR h.`number` IS NULL OR h.`number` = '');

-- ── Purchase orders ─────────────────────────────────────────
UPDATE `tblacc_account_history` h
JOIN `tblpur_orders` o ON o.`id` = h.`rel_id`
SET h.`vendor` = COALESCE(NULLIF(h.`vendor`, 0), o.`vendor`),
    h.`number` = COALESCE(NULLIF(h.`number`, ''),
                          NULLIF(o.`pur_order_number`, ''),
                          CONCAT('PO-', LPAD(o.`id`, 5, '0')))
WHERE h.`rel_type` = 'purchase_order'
  AND (h.`vendor` IS NULL OR h.`vendor` = 0 OR h.`number` IS NULL OR h.`number` = '');

-- ── Goods receipts (stock import) ───────────────────────────
UPDATE `tblacc_account_history` h
JOIN `tblgoods_receipt` g ON g.`id` = h.`rel_id`
SET h.`vendor` = COALESCE(NULLIF(h.`vendor`, 0), NULLIF(g.`supplier_code`, 0)),
    h.`number` = COALESCE(NULLIF(h.`number`, ''),
                          NULLIF(g.`goods_receipt_code`, ''),
                          CONCAT('GRN-', LPAD(g.`id`, 5, '0')))
WHERE h.`rel_type` = 'stock_import'
  AND (h.`vendor` IS NULL OR h.`vendor` = 0 OR h.`number` IS NULL OR h.`number` = '');

-- ── Expenses ────────────────────────────────────────────────
UPDATE `tblacc_account_history` h
JOIN `tblexpenses` e ON e.`id` = h.`rel_id`
SET h.`vendor`   = COALESCE(NULLIF(h.`vendor`, 0), NULLIF(e.`vendor`, 0)),
    h.`customer` = COALESCE(NULLIF(h.`customer`, 0), NULLIF(e.`clientid`, 0)),
    h.`number`   = COALESCE(NULLIF(h.`number`, ''),
                            NULLIF(e.`reference_no`, ''),
                            CONCAT('EXP-', LPAD(e.`id`, 5, '0')))
WHERE h.`rel_type` = 'expense'
  AND (
        (
          (h.`vendor` IS NULL OR h.`vendor` = 0)
          AND (h.`customer` IS NULL OR h.`customer` = 0)
        )
        OR h.`number` IS NULL
        OR h.`number` = ''
      );

-- ── Sales invoices ──────────────────────────────────────────
UPDATE `tblacc_account_history` h
JOIN `tblinvoices` i ON i.`id` = h.`rel_id`
SET h.`customer` = COALESCE(NULLIF(h.`customer`, 0), i.`clientid`),
    h.`number`   = COALESCE(NULLIF(h.`number`, ''),
                            CONCAT(i.`prefix`, LPAD(i.`number`, 6, '0')))
WHERE h.`rel_type` = 'invoice'
  AND (h.`customer` IS NULL OR h.`customer` = 0 OR h.`number` IS NULL OR h.`number` = '');

-- ── Sales payments ──────────────────────────────────────────
UPDATE `tblacc_account_history` h
JOIN `tblinvoicepaymentrecords` r ON r.`id` = h.`rel_id`
LEFT JOIN `tblinvoices` i         ON i.`id` = r.`invoiceid`
SET h.`customer` = COALESCE(NULLIF(h.`customer`, 0), i.`clientid`),
    h.`number`   = COALESCE(NULLIF(h.`number`, ''),
                            CONCAT('SR-', LPAD(r.`id`, 5, '0')))
WHERE h.`rel_type` = 'payment'
  AND (h.`customer` IS NULL OR h.`customer` = 0 OR h.`number` IS NULL OR h.`number` = '');

-- ── Journal entries (reference only, no payee) ───────────────
UPDATE `tblacc_account_history` h
JOIN `tblacc_journal_entries` j ON j.`id` = h.`rel_id`
SET h.`number` = COALESCE(NULLIF(h.`number`, ''),
                          NULLIF(j.`number`, ''),
                          CONCAT('JE-', LPAD(j.`id`, 5, '0')))
WHERE h.`rel_type` = 'journal_entry'
  AND (h.`number` IS NULL OR h.`number` = '');
