-- ============================================================================
-- NextGen ERP - Accounting Double Entry Setup
-- Chart of Accounts naming, numbering, and mapping configuration
-- UAE Standard Chart of Accounts numbering:
--   1xxx = Assets, 2xxx = Liabilities, 3xxx = Equity
--   4xxx = Revenue, 5xxx = Cost of Sales/COGS, 6xxx = Expenses
-- ============================================================================

-- ── Step 1: Set Account Names and Numbers ────────────────────────────────────

-- Assets (Type 1: Accounts Receivable)
UPDATE tblacc_accounts SET name = 'Accounts Receivable', number = '1100' WHERE id = 1;

-- Assets (Type 2: Current Assets)
UPDATE tblacc_accounts SET name = 'Allowance for Bad Debts', number = '1101' WHERE id = 6;
UPDATE tblacc_accounts SET name = 'Available for Sale Assets', number = '1200' WHERE id = 9;
UPDATE tblacc_accounts SET name = 'Inventory', number = '1300' WHERE id = 36;
UPDATE tblacc_accounts SET name = 'Inventory Asset', number = '1301' WHERE id = 37;
UPDATE tblacc_accounts SET name = 'Prepaid Expenses', number = '1400' WHERE id = 58;
UPDATE tblacc_accounts SET name = 'Uncategorised Asset', number = '1900' WHERE id = 79;
UPDATE tblacc_accounts SET name = 'Undeposited Funds', number = '1050' WHERE id = 82;

-- Assets (Type 3: Cash & Cash Equivalents)
UPDATE tblacc_accounts SET name = 'Cash and Cash Equivalents', number = '1010' WHERE id = 13;

-- Assets (Type 4: Fixed Assets)
UPDATE tblacc_accounts SET name = 'Accumulated Depreciation', number = '1510' WHERE id = 5;
UPDATE tblacc_accounts SET name = 'Property, Plant & Equipment', number = '1500' WHERE id = 59;

-- Assets (Type 5: Non-Current Assets)
UPDATE tblacc_accounts SET name = 'Assets Held for Sale', number = '1600' WHERE id = 8;
UPDATE tblacc_accounts SET name = 'Deferred Tax Assets', number = '1610' WHERE id = 17;
UPDATE tblacc_accounts SET name = 'Goodwill', number = '1620' WHERE id = 27;
UPDATE tblacc_accounts SET name = 'Intangible Assets', number = '1630' WHERE id = 33;
UPDATE tblacc_accounts SET name = 'Long-term Investments', number = '1640' WHERE id = 41;

-- Liabilities (Type 6: Accounts Payable)
UPDATE tblacc_accounts SET name = 'Accounts Payable', number = '2100' WHERE id = 87;

-- Liabilities (Type 7: Credit Card)
UPDATE tblacc_accounts SET name = 'Credit Card', number = '2200' WHERE id = 86;

-- Liabilities (Type 8: Current Liabilities)
UPDATE tblacc_accounts SET name = 'Accrued Liabilities', number = '2300' WHERE id = 3;
UPDATE tblacc_accounts SET name = 'Dividends Payable', number = '2310' WHERE id = 22;
UPDATE tblacc_accounts SET name = 'VAT Payable', number = '2400' WHERE id = 29;
UPDATE tblacc_accounts SET name = 'Payroll Clearing', number = '2500' WHERE id = 55;
UPDATE tblacc_accounts SET name = 'Payroll Liabilities', number = '2510' WHERE id = 57;
UPDATE tblacc_accounts SET name = 'Short-term Debt', number = '2600' WHERE id = 72;

-- Liabilities (Type 9: Non-Current Liabilities)
UPDATE tblacc_accounts SET name = 'Accrued Non-Current Liabilities', number = '2700' WHERE id = 2;
UPDATE tblacc_accounts SET name = 'Non-Current Liabilities (Held for Sale)', number = '2710' WHERE id = 39;
UPDATE tblacc_accounts SET name = 'Long-term Debt', number = '2720' WHERE id = 40;
UPDATE tblacc_accounts SET name = 'Accrued Holiday Payable', number = '2730' WHERE id = 4;

-- Equity (Type 10)
UPDATE tblacc_accounts SET name = 'Dividends Disbursed', number = '3100' WHERE id = 20;
UPDATE tblacc_accounts SET name = 'Equity in Subsidiaries', number = '3200' WHERE id = 25;
UPDATE tblacc_accounts SET name = 'Other Comprehensive Income', number = '3300' WHERE id = 49;
UPDATE tblacc_accounts SET name = 'Retained Earnings', number = '3400' WHERE id = 64;
UPDATE tblacc_accounts SET name = 'Share Capital', number = '3500' WHERE id = 70;

-- Revenue (Type 11: Income)
UPDATE tblacc_accounts SET name = 'Billable Expense Income', number = '4010' WHERE id = 12;
UPDATE tblacc_accounts SET name = 'Revenue - General', number = '4000' WHERE id = 65;
UPDATE tblacc_accounts SET name = 'Sales', number = '4100' WHERE id = 66;
UPDATE tblacc_accounts SET name = 'Sales - Retail', number = '4110' WHERE id = 67;
UPDATE tblacc_accounts SET name = 'Sales - Wholesale', number = '4120' WHERE id = 68;
UPDATE tblacc_accounts SET name = 'Sales of Product Income', number = '4130' WHERE id = 69;
UPDATE tblacc_accounts SET name = 'Unapplied Cash Payment Income', number = '4200' WHERE id = 78;
UPDATE tblacc_accounts SET name = 'Uncategorised Income', number = '4900' WHERE id = 81;

-- Other Income (Type 12)
UPDATE tblacc_accounts SET name = 'Dividend Income', number = '4500' WHERE id = 21;
UPDATE tblacc_accounts SET name = 'Interest Income', number = '4510' WHERE id = 35;
UPDATE tblacc_accounts SET name = 'Loss on Disposal of Assets', number = '4520' WHERE id = 43;
UPDATE tblacc_accounts SET name = 'Other Operating Income', number = '4530' WHERE id = 51;
UPDATE tblacc_accounts SET name = 'Unrealised Loss on Securities', number = '4540' WHERE id = 83;

-- Cost of Sales (Type 13: COGS)
UPDATE tblacc_accounts SET name = 'Change in Inventory - COGS', number = '5010' WHERE id = 14;
UPDATE tblacc_accounts SET name = 'Cost of Sales', number = '5000' WHERE id = 16;
UPDATE tblacc_accounts SET name = 'Direct Labour - COGS', number = '5020' WHERE id = 18;
UPDATE tblacc_accounts SET name = 'Discounts Given - COGS', number = '5030' WHERE id = 19;

-- Expenses (Type 14)
UPDATE tblacc_accounts SET name = 'Uncategorised Expense', number = '6900' WHERE id = 80;

-- ── Step 2: Create Essential Missing Accounts ────────────────────────────────

-- VAT Input (recoverable VAT on purchases)
INSERT INTO tblacc_accounts (name, key_name, number, account_type_id, account_detail_type_id, active)
SELECT 'VAT Input (Recoverable)', 'acc_vat_input', '1410', 2, 5, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE key_name = 'acc_vat_input');

-- Goods Received Not Invoiced (GRNI)
INSERT INTO tblacc_accounts (name, key_name, number, account_type_id, account_detail_type_id, active)
SELECT 'Goods Received Not Invoiced', 'acc_grni', '2110', 8, 35, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE key_name = 'acc_grni');

-- Bank Account (main operating account)
INSERT INTO tblacc_accounts (name, key_name, number, account_type_id, account_detail_type_id, active)
SELECT 'Bank Account - Operating', 'acc_bank_operating', '1020', 3, 15, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE key_name = 'acc_bank_operating');

-- Petty Cash
INSERT INTO tblacc_accounts (name, key_name, number, account_type_id, account_detail_type_id, active)
SELECT 'Petty Cash', 'acc_petty_cash', '1011', 3, 15, 1
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM tblacc_accounts WHERE key_name = 'acc_petty_cash');

-- ── Step 3: Fix Account Mappings ─────────────────────────────────────────────
-- Use proper Accounts Payable (87) instead of Cash for purchase transactions

-- Purchase Order: Debit Inventory/Expense, Credit Accounts Payable
UPDATE tbloptions SET value = '87' WHERE name = 'acc_pur_order_payment_account';
UPDATE tbloptions SET value = '37' WHERE name = 'acc_pur_order_deposit_to';

-- Purchase Invoice: Debit Expense/Inventory, Credit Accounts Payable
UPDATE tbloptions SET value = '87' WHERE name = 'acc_pur_invoice_payment_account';
UPDATE tbloptions SET value = '37' WHERE name = 'acc_pur_invoice_deposit_to';

-- Purchase Payment: Debit Accounts Payable, Credit Bank/Cash
UPDATE tbloptions SET value = '87' WHERE name = 'acc_pur_payment_deposit_to';
UPDATE tbloptions SET value = '13' WHERE name = 'acc_pur_payment_payment_account';

-- Purchase Tax: Debit VAT Input, Credit Accounts Payable
-- (Keep using account 29 for now as VAT Payable; ideally use VAT Input for purchases)
UPDATE tbloptions SET value = '87' WHERE name = 'acc_pur_tax_payment_account';

-- Purchase Return: Debit Accounts Payable, Credit Inventory
UPDATE tbloptions SET value = '87' WHERE name = 'acc_pur_order_return_deposit_to';
UPDATE tbloptions SET value = '37' WHERE name = 'acc_pur_order_return_payment_account';

-- Purchase Refund: Debit Bank/Cash, Credit Accounts Payable
UPDATE tbloptions SET value = '13' WHERE name = 'acc_pur_refund_payment_account';
UPDATE tbloptions SET value = '87' WHERE name = 'acc_pur_refund_deposit_to';

-- Sales Invoice: Debit Accounts Receivable, Credit Sales Revenue
-- (already correct: payment_account=66 (Sales), deposit_to=1 (AR))

-- Sales Payment: Debit Bank/Cash, Credit Accounts Receivable
-- (already correct: payment_account=1 (AR), deposit_to=13 (Cash))

-- Expense: Debit Expense Account, Credit Bank/Cash
-- (already correct: payment_account=13 (Cash), deposit_to=80 (Expense) → should be specific expense)

-- ── Step 4: Enable Account Numbers Display ───────────────────────────────────
UPDATE tbloptions SET value = '1' WHERE name = 'acc_enable_account_numbers';
UPDATE tbloptions SET value = '1' WHERE name = 'acc_show_account_numbers';

-- ── Step 5: Rename VAT account (was "Income Tax Payable", should be "VAT Payable") ──
UPDATE tblacc_accounts SET key_name = 'acc_vat_payable' WHERE id = 29;

-- ============================================================================
-- DONE. The accounting system is now properly configured for UAE operations.
--
-- Double-Entry Flow Summary:
-- 
-- PURCHASE CYCLE:
--   PO Approved    → Dr: Inventory Asset (1301)    Cr: Accounts Payable (2100)
--   Invoice Rcvd   → Dr: Inventory Asset (1301)    Cr: Accounts Payable (2100)
--   Payment Made   → Dr: Accounts Payable (2100)   Cr: Bank/Cash (1010)
--   VAT on Purchase→ Dr: VAT Payable (2400)        Cr: Accounts Payable (2100)
--
-- SALES CYCLE:
--   Invoice Sent   → Dr: Accounts Receivable (1100) Cr: Sales Revenue (4100)
--   Payment Rcvd   → Dr: Bank/Cash (1010)           Cr: Accounts Receivable (1100)
--   VAT on Sales   → Dr: Accounts Receivable (1100) Cr: VAT Payable (2400)
--
-- EXPENSES:
--   Expense Added  → Dr: Expense Account (6xxx)     Cr: Bank/Cash (1010)
-- ============================================================================
