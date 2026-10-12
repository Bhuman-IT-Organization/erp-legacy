-- Payment mode mappings (active 6-digit accounts)
-- Purchase: Dr AP(159), Cr Bank/Cash/PDC
-- Sales: Dr Bank/Cash/PDC, Cr AR(117)

-- Bank (mode 1)
UPDATE tblacc_payment_mode_mappings SET 
  expense_payment_account = 159, expense_deposit_to = 134,
  deposit_to = 134, payment_account = 117
WHERE payment_mode_id = 1;

-- Cash (mode 2)
UPDATE tblacc_payment_mode_mappings SET 
  expense_payment_account = 159, expense_deposit_to = 133,
  deposit_to = 133, payment_account = 117
WHERE payment_mode_id = 2;

-- Cheque (mode 3)
UPDATE tblacc_payment_mode_mappings SET 
  expense_payment_account = 159, expense_deposit_to = 166,
  deposit_to = 166, payment_account = 117
WHERE payment_mode_id = 3;
