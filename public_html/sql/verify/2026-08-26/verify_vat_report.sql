-- ============================================================================
-- VERIFICATION ONLY - read only, changes nothing.
-- ============================================================================
--
-- Reproduces the VAT report in SQL so its totals can be checked independently and,
-- more importantly, tied back to the Trial Balance.
--
-- The report reads the ledger rows on the VAT accounts rather than re-adding tax
-- from each document, which is what guarantees the two agree. Output tax sits on
-- VAT accounts of a liability type and is credited; input tax sits on asset type
-- accounts and is debited.
--
-- Adjust the period below to match whatever the screen is showing.
-- ============================================================================

SET @from_date = '2026-01-01';
SET @to_date   = '2026-12-31';

-- ---------------------------------------------------------------------------
-- 1. Which accounts the report treats as VAT, and on which side
-- ---------------------------------------------------------------------------
SELECT
    a.id,
    a.number,
    a.name,
    a.account_type_id,
    CASE WHEN a.account_type_id IN (6,7,8,9) THEN 'OUTPUT (sales)'
         WHEN a.account_type_id IN (1,2,3,4,5) THEN 'INPUT (purchases)'
    END AS treated_as
FROM tblacc_accounts a
WHERE (a.name LIKE '%VAT%' OR a.name LIKE '%Tax Payable%')
  AND a.account_type_id IN (1,2,3,4,5,6,7,8,9)
ORDER BY a.number;

-- ---------------------------------------------------------------------------
-- 2. Report totals
-- ---------------------------------------------------------------------------
SELECT
    ROUND(SUM(CASE WHEN a.account_type_id IN (6,7,8,9) THEN h.credit - h.debit ELSE 0 END), 2) AS output_vat,
    ROUND(SUM(CASE WHEN a.account_type_id IN (1,2,3,4,5) THEN h.debit - h.credit ELSE 0 END), 2) AS input_vat,
    ROUND(
        SUM(CASE WHEN a.account_type_id IN (6,7,8,9) THEN h.credit - h.debit ELSE 0 END)
      - SUM(CASE WHEN a.account_type_id IN (1,2,3,4,5) THEN h.debit - h.credit ELSE 0 END)
    , 2) AS net_vat_payable
FROM tblacc_account_history h
JOIN tblacc_accounts a ON a.id = h.account
WHERE (a.name LIKE '%VAT%' OR a.name LIKE '%Tax Payable%')
  AND a.account_type_id IN (1,2,3,4,5,6,7,8,9)
  AND h.date BETWEEN @from_date AND @to_date;

-- ---------------------------------------------------------------------------
-- 3. The rows behind those totals, grouped per document as the report does
-- ---------------------------------------------------------------------------
SELECT
    CASE WHEN a.account_type_id IN (6,7,8,9) THEN 'SALES' ELSE 'PURCHASES' END AS section,
    h.rel_type,
    h.rel_id,
    MIN(h.date) AS date,
    a.number    AS vat_account,
    ROUND(SUM(CASE WHEN a.account_type_id IN (6,7,8,9)
                   THEN h.credit - h.debit
                   ELSE h.debit - h.credit END), 2) AS vat_amount
FROM tblacc_account_history h
JOIN tblacc_accounts a ON a.id = h.account
WHERE (a.name LIKE '%VAT%' OR a.name LIKE '%Tax Payable%')
  AND a.account_type_id IN (1,2,3,4,5,6,7,8,9)
  AND h.date BETWEEN @from_date AND @to_date
GROUP BY section, h.rel_type, h.rel_id, a.number
HAVING vat_amount <> 0
ORDER BY section, date, h.rel_id;

-- ---------------------------------------------------------------------------
-- 4. TIE BACK TO THE TRIAL BALANCE
-- ---------------------------------------------------------------------------
-- The report total for each VAT account must equal that account's movement in the
-- same period. Any non zero difference means the report has dropped or double
-- counted something.
SELECT
    a.number,
    a.name,
    ROUND(SUM(h.debit), 2)  AS total_debit,
    ROUND(SUM(h.credit), 2) AS total_credit,
    ROUND(SUM(h.debit) - SUM(h.credit), 2) AS net_movement,
    COUNT(*) AS ledger_rows,
    COUNT(DISTINCT CONCAT(h.rel_type, ':', h.rel_id)) AS documents
FROM tblacc_account_history h
JOIN tblacc_accounts a ON a.id = h.account
WHERE (a.name LIKE '%VAT%' OR a.name LIKE '%Tax Payable%')
  AND a.account_type_id IN (1,2,3,4,5,6,7,8,9)
  AND h.date BETWEEN @from_date AND @to_date
GROUP BY a.number, a.name
ORDER BY a.number;

-- ---------------------------------------------------------------------------
-- 5. Documents the report cannot label
-- ---------------------------------------------------------------------------
-- These still appear on the report (shown as "rel_type #id") so nothing is lost,
-- but a new rel_type here means get_vat_report_document() wants a case adding.
SELECT DISTINCT h.rel_type
FROM tblacc_account_history h
JOIN tblacc_accounts a ON a.id = h.account
WHERE (a.name LIKE '%VAT%' OR a.name LIKE '%Tax Payable%')
  AND a.account_type_id IN (1,2,3,4,5,6,7,8,9)
  AND h.rel_type NOT IN ('invoice','credit_note','purchase_invoice',
                         'inv_shipping_charge','expense','pur_debit_note','debit_note');
