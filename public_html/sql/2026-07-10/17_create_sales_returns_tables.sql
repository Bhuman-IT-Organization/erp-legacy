-- Sales Returns tables

CREATE TABLE IF NOT EXISTS tblsales_returns (
  id INT(11) NOT NULL AUTO_INCREMENT,
  estimate_id INT(11) DEFAULT NULL,
  clientid INT(11) DEFAULT NULL,
  gdv_ids TEXT DEFAULT NULL,
  return_number VARCHAR(50) DEFAULT NULL,
  return_date DATE DEFAULT NULL,
  subtotal DECIMAL(15,2) DEFAULT 0.00,
  total_tax DECIMAL(15,2) DEFAULT 0.00,
  total DECIMAL(15,2) DEFAULT 0.00,
  note TEXT DEFAULT NULL,
  status VARCHAR(20) DEFAULT 'draft',
  is_deleted TINYINT(1) NOT NULL DEFAULT 0,
  created_by INT(11) DEFAULT NULL,
  created_at DATETIME DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS tblsales_return_items (
  id INT(11) NOT NULL AUTO_INCREMENT,
  sales_return_id INT(11) NOT NULL,
  item_code INT(11) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  qty DECIMAL(15,2) DEFAULT 0.00,
  unit_price DECIMAL(15,2) DEFAULT 0.00,
  unit_id INT(11) DEFAULT NULL,
  tax_id VARCHAR(50) DEFAULT NULL,
  tax_rate VARCHAR(50) DEFAULT NULL,
  amount DECIMAL(15,2) DEFAULT 0.00,
  PRIMARY KEY (id),
  KEY sales_return_id (sales_return_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
