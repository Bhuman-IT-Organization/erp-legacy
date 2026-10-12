-- Truncate purchase request tables
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE tblpur_request_detail;
TRUNCATE TABLE tblpur_request;
SET FOREIGN_KEY_CHECKS = 1;

-- Reset next PR number back to 1
UPDATE tblpurchase_option SET option_val = '1' WHERE option_name = 'next_pr_number';
