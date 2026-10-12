-- Create vendor recommendations table for PR > 5000 AED
CREATE TABLE IF NOT EXISTS tblpur_vendor_recommendations (
    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    pur_request_id INT(11) NOT NULL,
    vendor_name VARCHAR(255) DEFAULT NULL,
    vendor_id INT(11) DEFAULT NULL,
    quotation_amount DECIMAL(15,2) DEFAULT 0,
    remarks TEXT DEFAULT NULL,
    recommended TINYINT(1) DEFAULT 0,
    file_name VARCHAR(255) DEFAULT NULL,
    filetype VARCHAR(100) DEFAULT NULL,
    staffid INT(11) DEFAULT NULL,
    dateadded DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_pur_request (pur_request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
