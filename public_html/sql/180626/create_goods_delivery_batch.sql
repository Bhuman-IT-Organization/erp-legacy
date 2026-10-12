-- Create goods_delivery_batch table (required for batch tracking in goods deliveries)
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
