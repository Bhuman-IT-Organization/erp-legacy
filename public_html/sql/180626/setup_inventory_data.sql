-- ============================================================================
-- Setup inventory data for all items and create missing tables
-- Run on both local and live server
-- ============================================================================

-- Create goods_delivery_batch table (required for batch tracking)
CREATE TABLE IF NOT EXISTS tblgoods_delivery_batch (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    delivery_detail_id INT(11) NOT NULL,
    goods_delivery_id INT(11) NOT NULL,
    batch_number VARCHAR(100) DEFAULT NULL,
    batch_qty DECIMAL(15,2) DEFAULT 0,
    PRIMARY KEY (id),
    KEY idx_delivery_detail (delivery_detail_id),
    KEY idx_goods_delivery (goods_delivery_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Set can_be_inventory flag on all items that should track inventory
UPDATE tblitems SET can_be_inventory = 'can_be_inventory' 
WHERE (can_be_inventory IS NULL OR can_be_inventory = '') 
AND without_checking_warehouse = 0 AND active = 1;

-- Add inventory min/max records for items that don't have them
INSERT INTO tblinventory_commodity_min (commodity_id, commodity_code, commodity_name, inventory_number_min, inventory_number_max)
SELECT id, commodity_code, description, 10, 1000 
FROM tblitems 
WHERE id NOT IN (SELECT commodity_id FROM tblinventory_commodity_min) 
AND active = 1 AND without_checking_warehouse = 0;

-- Add opening stock for items in NGPP Warehouse (warehouse_id=1) that have no inventory
-- Only run this if items don't already have stock records
INSERT INTO tblinventory_manage (warehouse_id, commodity_id, inventory_number, purchase_price, lot_number)
SELECT 1, id, 50, IFNULL(purchase_price, 0), CONCAT('NG', LPAD(id, 5, '0'))
FROM tblitems 
WHERE id NOT IN (SELECT commodity_id FROM tblinventory_manage) 
AND active = 1 AND without_checking_warehouse = 0;

-- Update existing inventory records to have batch numbers if empty
UPDATE tblinventory_manage 
SET lot_number = CONCAT('NG', LPAD(id, 5, '0')) 
WHERE (lot_number IS NULL OR lot_number = '' OR lot_number = '0');
