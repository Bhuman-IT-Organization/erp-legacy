-- Delete 3 dummy cheque management records
-- These are test records for "Banana Republic" vendor
DELETE FROM `tblcheque_management` WHERE `id` IN (1, 2, 3);
DELETE FROM `tblcheque_transactions` WHERE `cheque_id` IN (1, 2, 3);
