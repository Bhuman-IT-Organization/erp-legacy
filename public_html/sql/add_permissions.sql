-- Add all permissions to existing customer contacts

INSERT IGNORE INTO tblcontactpermissions (permission_id, userid)
SELECT 1, id FROM tblcontacts; -- invoices

INSERT IGNORE INTO tblcontactpermissions (permission_id, userid)
SELECT 2, id FROM tblcontacts; -- estimates

INSERT IGNORE INTO tblcontactpermissions (permission_id, userid)
SELECT 3, id FROM tblcontacts; -- contracts

INSERT IGNORE INTO tblcontactpermissions (permission_id, userid)
SELECT 4, id FROM tblcontacts; -- proposals

INSERT IGNORE INTO tblcontactpermissions (permission_id, userid)
SELECT 5, id FROM tblcontacts; -- support

INSERT IGNORE INTO tblcontactpermissions (permission_id, userid)
SELECT 6, id FROM tblcontacts; -- projects
