-- Drop trigger if exists
DROP TRIGGER IF EXISTS add_permissions_after_contact_insert;

-- Create trigger to automatically add all permissions to new contacts
DELIMITER $$
CREATE TRIGGER add_permissions_after_contact_insert
AFTER INSERT ON tblcontacts
FOR EACH ROW
BEGIN
    INSERT IGNORE INTO tblcontactpermissions (permission_id, userid) VALUES (1, NEW.id);
    INSERT IGNORE INTO tblcontactpermissions (permission_id, userid) VALUES (2, NEW.id);
    INSERT IGNORE INTO tblcontactpermissions (permission_id, userid) VALUES (3, NEW.id);
    INSERT IGNORE INTO tblcontactpermissions (permission_id, userid) VALUES (4, NEW.id);
    INSERT IGNORE INTO tblcontactpermissions (permission_id, userid) VALUES (5, NEW.id);
    INSERT IGNORE INTO tblcontactpermissions (permission_id, userid) VALUES (6, NEW.id);
END$$
DELIMITER ;

-- Add permissions to all existing contacts
INSERT IGNORE INTO tblcontactpermissions (permission_id, userid)
SELECT 1, id FROM tblcontacts;

INSERT IGNORE INTO tblcontactpermissions (permission_id, userid)
SELECT 2, id FROM tblcontacts;

INSERT IGNORE INTO tblcontactpermissions (permission_id, userid)
SELECT 3, id FROM tblcontacts;

INSERT IGNORE INTO tblcontactpermissions (permission_id, userid)
SELECT 4, id FROM tblcontacts;

INSERT IGNORE INTO tblcontactpermissions (permission_id, userid)
SELECT 5, id FROM tblcontacts;

INSERT IGNORE INTO tblcontactpermissions (permission_id, userid)
SELECT 6, id FROM tblcontacts;
