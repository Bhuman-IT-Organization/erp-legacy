-- Fix inova PR#4 approval issue
-- Problem: staffid=29 doesn't exist in inova, should be staff 45 (Quality Dept)

-- Fix approval setting ID 7 (Purchase - HR, dept 8) to use valid staff 42 (Hassan)
UPDATE tblpur_approval_setting SET setting = '[{"approver":"staff","staff":"42","action":"approve","amount":"9999999999999"}]' WHERE id = 7;

-- Delete invalid approval record for PR#4 (wrong staff 29)
DELETE FROM tblpur_approval_details WHERE rel_id = 4 AND rel_type = 'pur_request' AND staffid = '29';

-- Re-create correct approval record for PR#4 using Quality Dept approver (staff 45)
INSERT INTO tblpur_approval_details (rel_id, rel_type, staffid, action, sender, date_send) 
VALUES (4, 'pur_request', '45', 'approve', 1, NOW());

-- Also fix any other PRs with wrong staffid=29
UPDATE tblpur_approval_details SET staffid = '45' WHERE staffid = '29' AND rel_type = 'pur_request';
