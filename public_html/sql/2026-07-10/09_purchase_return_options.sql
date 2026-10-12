-- Purchase Return: Dr AP(159), Cr Inventory RM(112)
UPDATE tbloptions SET value = '159' WHERE name = 'acc_pur_order_return_deposit_to';
UPDATE tbloptions SET value = '112' WHERE name = 'acc_pur_order_return_payment_account';
UPDATE tbloptions SET value = '159' WHERE name = 'acc_pur_order_return_discount_deposit_to';
UPDATE tbloptions SET value = '209' WHERE name = 'acc_pur_order_return_discount_payment_account';
UPDATE tbloptions SET value = '290' WHERE name = 'acc_pur_order_return_fee_deposit_to';
UPDATE tbloptions SET value = '159' WHERE name = 'acc_pur_order_return_fee_payment_account';
