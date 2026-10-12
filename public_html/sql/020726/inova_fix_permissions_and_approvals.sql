-- ============================================================
-- Fix Inova DB for Purchase and Inventory modules
-- Run this on the INOVA database
-- ============================================================

-- 1. Add full permissions for staff who need purchase/warehouse access
-- Staff 40 (Gregory) - Procurement Manager
INSERT IGNORE INTO tblstaff_permissions (staff_id, feature, capability) VALUES
(40, 'purchase_request', 'create'),
(40, 'purchase_request', 'edit'),
(40, 'purchase_request', 'delete'),
(40, 'purchase_orders', 'create'),
(40, 'purchase_orders', 'edit'),
(40, 'purchase_orders', 'delete'),
(40, 'purchase_invoices', 'create'),
(40, 'purchase_invoices', 'edit'),
(40, 'purchase_invoices', 'delete'),
(40, 'purchase_items', 'create'),
(40, 'purchase_items', 'edit'),
(40, 'purchase_items', 'delete'),
(40, 'purchase_vendors', 'create'),
(40, 'purchase_vendors', 'edit'),
(40, 'purchase_vendors', 'delete'),
(40, 'purchase_vendor_items', 'create'),
(40, 'purchase_vendor_items', 'edit'),
(40, 'warehouse_item', 'create'),
(40, 'warehouse_item', 'edit'),
(40, 'warehouse_item', 'delete'),
(40, 'wh_warehouse', 'create'),
(40, 'wh_warehouse', 'edit');

-- Staff 42 (Hassan) - Operations
INSERT IGNORE INTO tblstaff_permissions (staff_id, feature, capability) VALUES
(42, 'purchase_request', 'create'),
(42, 'purchase_request', 'edit'),
(42, 'purchase_orders', 'create'),
(42, 'purchase_orders', 'edit'),
(42, 'purchase_invoices', 'create'),
(42, 'purchase_invoices', 'edit'),
(42, 'purchase_items', 'create'),
(42, 'purchase_items', 'edit'),
(42, 'warehouse_item', 'create'),
(42, 'warehouse_item', 'edit'),
(42, 'wh_warehouse', 'create'),
(42, 'wh_warehouse', 'edit');

-- Staff 45 (Thazin) - Quality
INSERT IGNORE INTO tblstaff_permissions (staff_id, feature, capability) VALUES
(45, 'purchase_request', 'create'),
(45, 'purchase_request', 'edit'),
(45, 'warehouse_item', 'view'),
(45, 'wh_warehouse', 'view');

-- Staff 39 (Chamath) - PR creator
INSERT IGNORE INTO tblstaff_permissions (staff_id, feature, capability) VALUES
(39, 'purchase_request', 'view_own'),
(39, 'purchase_request', 'delete'),
(39, 'purchase_orders', 'view'),
(39, 'purchase_invoices', 'view');

-- 2. Fix approval details with wrong staff IDs (old staff 29 doesn't exist in inova)
-- Delete old invalid approvals
DELETE FROM tblpur_approval_details WHERE staffid = '29';

-- 3. Ensure purchase options are correct
-- Update approval settings to match inova staff
-- (Already correct: staff 40, 41, 42, 45 referenced in tblpur_approval_setting)

-- 4. Ensure the Purchase Request prefix settings work
-- They already exist (PR prefix = 'PR', PO prefix = 'PO')

-- 5. Add required items for testing (if items table is empty)
-- Check: SELECT COUNT(*) FROM tblitems;
-- If less than 5, import items from the nextgen DB or add manually

-- 6. Ensure departments match what PR approvals expect
-- Current: department 8 = IT, department 7 = Quality Assurance
-- Approval settings reference department 8 (IT) and 0 (all departments)
-- These are correct.

-- 7. Reset PR status to allow re-approval
-- PR #3 has status=1 (pending), which means it hasn't been sent for approval yet
-- Send it for approval by updating status:
-- UPDATE tblpur_request SET status = 1 WHERE id = 3;

-- 8. Ensure tblpur_request_types has correct data
SELECT id, name, type_code, ledger_account, credit_ledger FROM tblpur_request_types;
-- If ledger_account is 0 or NULL for some types, update them:
-- (Run only if accounting module is set up with correct accounts in inova)
