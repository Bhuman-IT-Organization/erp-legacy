-- Create purchase terms table
CREATE TABLE IF NOT EXISTS `tblpur_terms` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL,
  `content` TEXT NULL,
  `created_by` INT DEFAULT NULL,
  `datecreated` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
