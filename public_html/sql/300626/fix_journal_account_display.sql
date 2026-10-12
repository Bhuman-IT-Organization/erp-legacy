-- Fix Journal Report: Account names and Dr/Cr labels not showing on 61 server
-- Date: 2026-06-30

-- 1. Add 'split' column to acc_account_history if missing (used for journal Dr/Cr display)
ALTER TABLE `tblacc_account_history` ADD COLUMN IF NOT EXISTS `split` INT(11) NOT NULL DEFAULT 0;

-- 2. Add 'date' column to acc_account_history if missing
ALTER TABLE `tblacc_account_history` ADD COLUMN IF NOT EXISTS `date` DATE NULL;

-- 3. Add 'number' column to acc_accounts if missing (stores account numbers like 150000, 240000 etc)
ALTER TABLE `tblacc_accounts` ADD COLUMN IF NOT EXISTS `number` VARCHAR(45) NULL;

-- 4. Enable account numbers display in options
INSERT INTO `tbloptions` (`name`, `value`) VALUES ('acc_enable_account_numbers', '1')
ON DUPLICATE KEY UPDATE `value` = '1';

INSERT INTO `tbloptions` (`name`, `value`) VALUES ('acc_show_account_numbers', '1')
ON DUPLICATE KEY UPDATE `value` = '1';

-- 5. Verify: Run this SELECT to check if accounts have numbers populated
-- If this returns empty/null numbers, you need to copy account numbers from localhost
-- SELECT id, name, number FROM tblacc_accounts ORDER BY id;
