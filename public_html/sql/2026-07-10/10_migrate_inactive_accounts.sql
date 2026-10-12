-- Migrate existing accounting entries from inactive accounts to active 6-digit ones
-- Only run if you have existing entries using old account IDs

-- 87 (2100 inactive) -> 159 (230000 active AP)
UPDATE tblacc_account_history SET account = 159 WHERE account = 87;
UPDATE tblacc_account_history SET split = 159 WHERE split = 87;

-- 1 (1100 inactive) -> 117 (170000 active AR)
UPDATE tblacc_account_history SET account = 117 WHERE account = 1;
UPDATE tblacc_account_history SET split = 117 WHERE split = 1;

-- 37 (1301 inactive) -> 114 (150002 active Inventory FG)
UPDATE tblacc_account_history SET account = 114 WHERE account = 37;
UPDATE tblacc_account_history SET split = 114 WHERE split = 37;

-- 66 (4100 inactive) -> 199 (300000 active Revenue)
UPDATE tblacc_account_history SET account = 199 WHERE account = 66;
UPDATE tblacc_account_history SET split = 199 WHERE split = 66;

-- 356 (1410 inactive) -> 124 (179020 active VAT Paid)
UPDATE tblacc_account_history SET account = 124 WHERE account = 356;
UPDATE tblacc_account_history SET split = 124 WHERE split = 356;

-- 29 (2400 inactive) -> 123 (179010 active VAT Received)
UPDATE tblacc_account_history SET account = 123 WHERE account = 29;
UPDATE tblacc_account_history SET split = 123 WHERE split = 29;
