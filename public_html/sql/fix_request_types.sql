-- Create Request Types table
CREATE TABLE IF NOT EXISTS `tblpur_request_types` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `type_code` VARCHAR(50) NOT NULL,
  `ledger_account` INT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `datecreated` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
