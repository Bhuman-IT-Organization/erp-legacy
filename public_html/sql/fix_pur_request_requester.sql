-- Fix purchase requests with missing requester
-- Sets requester to the first admin staff (staffid=1) as a fallback for old records
-- Run this once to patch existing data

SET FOREIGN_KEY_CHECKS = 0;

-- Update PRs where requester is NULL or 0
UPDATE `tblpur_request`
SET `requester` = (
    SELECT `staffid` FROM `tblstaff` WHERE `admin` = 1 ORDER BY `staffid` ASC LIMIT 1
)
WHERE `requester` IS NULL OR `requester` = 0;

SET FOREIGN_KEY_CHECKS = 1;
