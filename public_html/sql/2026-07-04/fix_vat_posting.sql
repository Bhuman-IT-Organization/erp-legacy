-- Fix VAT/Tax posting for Sales Invoices
-- Sales tax deposit_to should be AR (1) not VAT Input (356)
-- When sales invoice has VAT: Dr AR (customer owes VAT), Cr VAT Payable (we owe government)

UPDATE tblacc_tax_mappings SET deposit_to = 1 WHERE id = 1;
-- deposit_to = 1 = Accounts Receivable (DEBIT side for sales)
-- payment_account = 29 = VAT Payable (CREDIT side for sales)

-- Verify all tax mapping columns:
-- Sales:    deposit_to=1 (Dr AR), payment_account=29 (Cr VAT Payable)
-- Purchase: purchase_payment_account=356 (Dr VAT Input), purchase_deposit_to=87 (Cr AP)
-- Expense:  expense_payment_account=356 (Dr VAT Input), expense_deposit_to=87 (Cr AP)
