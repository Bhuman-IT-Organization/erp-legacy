-- Create goods receipt history table to track each receipt transaction
CREATE TABLE IF NOT EXISTS `tblgoods_receipt_history` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `goods_receipt_id` INT NOT NULL,
  `goods_receipt_code` VARCHAR(100) DEFAULT NULL,
  `pr_order_id` INT DEFAULT NULL,
  `warehouse_id` INT DEFAULT NULL,
  `supplier_code` VARCHAR(100) DEFAULT NULL,
  `total_goods_money` DECIMAL(15,2) DEFAULT NULL,
  `total_tax_money` DECIMAL(15,2) DEFAULT NULL,
  `total_money` DECIMAL(15,2) DEFAULT NULL,
  `action` VARCHAR(50) DEFAULT 'created' COMMENT 'created, updated',
  `staffid` INT DEFAULT NULL,
  `date` DATETIME DEFAULT NULL,
  `note` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `goods_receipt_id` (`goods_receipt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create goods receipt history detail table
CREATE TABLE IF NOT EXISTS `tblgoods_receipt_history_detail` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `history_id` INT UNSIGNED NOT NULL,
  `goods_receipt_id` INT NOT NULL,
  `commodity_code` VARCHAR(100) DEFAULT NULL,
  `commodity_name` TEXT DEFAULT NULL,
  `warehouse_id` INT DEFAULT NULL,
  `quantities` DECIMAL(15,2) DEFAULT NULL,
  `unit_price` DECIMAL(15,2) DEFAULT NULL,
  `received_qty` DECIMAL(15,2) DEFAULT NULL,
  `pending_qty` DECIMAL(15,2) DEFAULT NULL,
  `tax_money` DECIMAL(15,2) DEFAULT NULL,
  `goods_money` DECIMAL(15,2) DEFAULT NULL,
  `lot_number` VARCHAR(100) DEFAULT NULL,
  `unit_id` INT DEFAULT NULL,
  `staffid` INT DEFAULT NULL,
  `date` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `history_id` (`history_id`),
  KEY `goods_receipt_id` (`goods_receipt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
