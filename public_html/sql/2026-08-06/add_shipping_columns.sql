-- Inventory Shipping Charges - Separate table
-- Run on live server

CREATE TABLE IF NOT EXISTS tblinv_shipping_charges (
  id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  invoice_id INT(11) NOT NULL,
  vendor INT(11) NOT NULL,
  amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  tax_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
  total DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  date_created DATETIME DEFAULT NULL,
  PRIMARY KEY (id),
  KEY invoice_id (invoice_id),
  KEY vendor (vendor)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
