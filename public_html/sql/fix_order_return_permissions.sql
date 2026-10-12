-- Grant wh_receipt_return_order permissions to all active staff
-- =====================================================

INSERT IGNORE INTO `tblstaff_permissions` (staff_id, feature, capability)
SELECT staffid, 'wh_receipt_return_order', 'view' FROM tblstaff WHERE active=1;

INSERT IGNORE INTO `tblstaff_permissions` (staff_id, feature, capability)
SELECT staffid, 'wh_receipt_return_order', 'view_own' FROM tblstaff WHERE active=1;

INSERT IGNORE INTO `tblstaff_permissions` (staff_id, feature, capability)
SELECT staffid, 'wh_receipt_return_order', 'create' FROM tblstaff WHERE active=1;

INSERT IGNORE INTO `tblstaff_permissions` (staff_id, feature, capability)
SELECT staffid, 'wh_receipt_return_order', 'edit' FROM tblstaff WHERE active=1;

INSERT IGNORE INTO `tblstaff_permissions` (staff_id, feature, capability)
SELECT staffid, 'wh_receipt_return_order', 'delete' FROM tblstaff WHERE active=1;
