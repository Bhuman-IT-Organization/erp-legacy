-- Sales Invoice: Dr AR(117), Cr Revenue(199)
UPDATE tbloptions SET value = '117' WHERE name = 'acc_invoice_deposit_to';
UPDATE tbloptions SET value = '199' WHERE name = 'acc_invoice_payment_account';

-- Sales Discount: Dr Discount(201), Cr AR(117)
UPDATE tbloptions SET value = '201' WHERE name = 'acc_invoice_discount_payment_account';
UPDATE tbloptions SET value = '117' WHERE name = 'acc_invoice_discount_deposit_to';
