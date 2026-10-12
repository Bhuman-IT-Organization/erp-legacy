-- Asset Code Request Form tables
-- Run on live server

CREATE TABLE IF NOT EXISTS tblasset_code_requests (
  id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  request_number VARCHAR(50) DEFAULT NULL,
  department INT(11) DEFAULT NULL,
  requested_by INT(11) DEFAULT NULL,
  reason VARCHAR(50) DEFAULT NULL,
  location VARCHAR(255) DEFAULT NULL,
  asset_code VARCHAR(100) DEFAULT NULL,
  asset_class VARCHAR(100) DEFAULT NULL,
  capitalization_date DATE DEFAULT NULL,
  status TINYINT(1) NOT NULL DEFAULT 0,
  approval_status TINYINT(1) NOT NULL DEFAULT 0,
  date_created DATETIME DEFAULT NULL,
  created_by INT(11) DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS tblasset_code_request_items (
  id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  request_id INT(11) NOT NULL,
  description TEXT DEFAULT NULL,
  qty DECIMAL(15,2) DEFAULT 0,
  uom VARCHAR(50) DEFAULT NULL,
  unit_cost DECIMAL(15,2) DEFAULT 0,
  total DECIMAL(15,2) DEFAULT 0,
  vendor INT(11) DEFAULT NULL,
  PRIMARY KEY (id),
  KEY request_id (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- Approval setting for Asset Request (uses same approval flow as PR/PO)
-- Staff ID 1 = admin (change to your finance manager ID)
INSERT INTO tblpur_approval_setting (name, related, department, setting, approval_type) 
VALUES ('Asset Request Approval', 'asset_request', 0, '[{"approver":"staff","staff":"1","action":"approve","amount":""}]', 0);
