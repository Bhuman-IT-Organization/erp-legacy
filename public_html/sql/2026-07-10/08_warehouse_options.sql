-- Goods Delivery: Dr COGS-FG(222), Cr Inventory FG(114)
UPDATE tbloptions SET value = '222' WHERE name = 'acc_wh_stock_export_deposit_to';
UPDATE tbloptions SET value = '114' WHERE name = 'acc_wh_stock_export_payment_account';
UPDATE tbloptions SET value = '0' WHERE name = 'acc_wh_stock_export_profit_automatic_conversion';
