-- Ensure goods_receipt_history table exists
CREATE TABLE IF NOT EXISTS `tblgoods_receipt_history` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `pr_order_id` INT DEFAULT NULL,
    `goods_receipt_id` INT DEFAULT NULL,
    `staff_id` INT DEFAULT NULL,
    `date_received` DATETIME DEFAULT NULL,
    `notes` TEXT DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_pr_order_id` (`pr_order_id`),
    KEY `idx_goods_receipt_id` (`goods_receipt_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ensure goods_receipt_history_detail table exists
CREATE TABLE IF NOT EXISTS `tblgoods_receipt_history_detail` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `history_id` INT UNSIGNED NOT NULL,
    `commodity_code` VARCHAR(100) DEFAULT NULL,
    `received_qty` DECIMAL(15,2) DEFAULT 0,
    `date` DATETIME DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_history_id` (`history_id`),
    KEY `idx_commodity_code` (`commodity_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;