-- Purchase Invoice: Cr AP(159), Dr Inventory(112 for without-PO)
UPDATE tbloptions SET value = '159' WHERE name = 'acc_pur_invoice_payment_account';
UPDATE tbloptions SET value = '112' WHERE name = 'acc_pur_invoice_deposit_to';

-- Purchase Shipping: Dr Freight(290), Cr AP(159)
UPDATE tbloptions SET value = '290' WHERE name = 'acc_pur_invoice_shipping_deposit_to';
UPDATE tbloptions SET value = '159' WHERE name = 'acc_pur_invoice_shipping_payment_account';

-- Purchase Discount: Dr AP(159), Cr Discount Received(209)
UPDATE tbloptions SET value = '159' WHERE name = 'acc_pur_invoice_discount_deposit_to';
UPDATE tbloptions SET value = '209' WHERE name = 'acc_pur_invoice_discount_payment_account';

-- Purchase VAT: Dr VAT Paid(124), Cr AP(159)
UPDATE tbloptions SET value = '124' WHERE name = 'acc_pur_tax_payment_account';
UPDATE tbloptions SET value = '159' WHERE name = 'acc_pur_tax_deposit_to';
