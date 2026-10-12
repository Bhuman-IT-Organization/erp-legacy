-- Add sales_order_id and delivery_voucher_ids columns to tblinvoices
-- These store the linked estimate and delivery voucher references

SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE tblinvoices ADD COLUMN sales_order_id INT(11) DEFAULT NULL', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblinvoices' AND COLUMN_NAME = 'sales_order_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(COUNT(*) = 0, 'ALTER TABLE tblinvoices ADD COLUMN delivery_voucher_ids TEXT DEFAULT NULL', 'SELECT 1') FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'tblinvoices' AND COLUMN_NAME = 'delivery_voucher_ids');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
