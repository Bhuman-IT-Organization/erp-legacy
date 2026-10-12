-- ---------------------------------------------------------------------------
-- Read-only verification for the shipping charge GL account + posting date change.
--
-- Proves the resolution chain the controller now walks:
--     inv_shipping_charges.invoice_id
--       -> pur_invoices.pur_order
--       -> pur_orders.ledger_account
--       -> acc_accounts.name
-- with a fallback to Inventory Raw Materials (112) when any link is missing.
--
-- Nothing here writes. Safe to re-run at any time.
--
--   mysql -u root nextgen_live -e "source sql/2026-09-01/verify_shipping_charge_gl_and_date.sql"
-- ---------------------------------------------------------------------------

SELECT '--- 1. Existing charges: account the OLD code used vs the NEW code ---' AS step;

SELECT  sc.id,
        sc.shipping_number,
        po.pur_order_number,
        112                                   AS old_hardcoded_account,
        COALESCE(NULLIF(po.ledger_account, 0), 112) AS new_resolved_account,
        COALESCE(acc.name, 'Inventory Raw Materials (fallback)') AS new_account_name,
        CASE WHEN COALESCE(NULLIF(po.ledger_account, 0), 112) = 112
             THEN 'unchanged'
             ELSE 'CORRECTED' END             AS effect
FROM    tblinv_shipping_charges sc
LEFT JOIN tblpur_invoices  inv ON inv.id = sc.invoice_id
LEFT JOIN tblpur_orders    po  ON po.id  = inv.pur_order
LEFT JOIN tblacc_accounts  acc ON acc.id = NULLIF(po.ledger_account, 0)
ORDER BY sc.id;

SELECT '--- 2. Ledger entries already posted for shipping charges ---' AS step;

SELECT  ah.rel_id       AS shipping_charge_id,
        ah.account,
        acc.name        AS account_name,
        ah.debit,
        ah.credit,
        ah.date         AS posted_on
FROM    tblacc_account_history ah
LEFT JOIN tblacc_accounts acc ON acc.id = ah.account
WHERE   ah.rel_type = 'inv_shipping_charge'
ORDER BY ah.rel_id, ah.id;

SELECT '--- 3. Fallback coverage: how often the PO has no GL account ---' AS step;

SELECT  COUNT(*) AS invoices_total,
        SUM(CASE WHEN inv.pur_order IS NULL OR inv.pur_order = 0
                 THEN 1 ELSE 0 END) AS invoices_with_no_po,
        SUM(CASE WHEN po.id IS NOT NULL
                  AND (po.ledger_account IS NULL OR po.ledger_account = 0)
                 THEN 1 ELSE 0 END) AS invoices_whose_po_has_no_gl,
        SUM(CASE WHEN po.ledger_account IS NOT NULL AND po.ledger_account <> 0
                 THEN 1 ELSE 0 END) AS invoices_that_resolve_from_po
FROM    tblpur_invoices inv
LEFT JOIN tblpur_orders po ON po.id = inv.pur_order
WHERE   inv.is_deleted = 0;

SELECT '--- 4. Which accounts shipping would now be debited to, by volume ---' AS step;

SELECT  COALESCE(NULLIF(po.ledger_account, 0), 112) AS resolved_account,
        COALESCE(acc.name, 'Inventory Raw Materials (fallback)') AS account_name,
        COUNT(*) AS invoice_count
FROM    tblpur_invoices inv
LEFT JOIN tblpur_orders   po  ON po.id  = inv.pur_order
LEFT JOIN tblacc_accounts acc ON acc.id = NULLIF(po.ledger_account, 0)
WHERE   inv.is_deleted = 0
GROUP BY resolved_account, account_name
ORDER BY invoice_count DESC
LIMIT 15;

SELECT '--- 5. Every referenced PO ledger_account must exist in acc_accounts ---' AS step;

-- Aliased acc_status rather than status: the latter is swallowed by MySQL in a
-- HAVING clause and the filter silently does nothing.
SELECT  COUNT(*) AS dangling_po_accounts_should_be_0
FROM    tblpur_orders po
LEFT JOIN tblacc_accounts acc ON acc.id = po.ledger_account
WHERE   po.ledger_account IS NOT NULL
  AND   po.ledger_account <> 0
  AND   acc.id IS NULL;

SELECT '--- 6. New columns all present ---' AS step;

SELECT  COUNT(*) AS should_be_3
FROM    information_schema.columns
WHERE   table_schema = DATABASE()
  AND   table_name   = 'tblinv_shipping_charges'
  AND   column_name IN ('cost_center', 'shipping_invoice_number', 'shipping_invoice_date');
