-- Create Asset Creation table under Asset Management
-- =====================================================

CREATE TABLE IF NOT EXISTS `tblfe_asset_creation` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `asset_uid` VARCHAR(50) NULL,
  `company` VARCHAR(200) NULL DEFAULT 'Nextgen Printing & Packaging',
  `code` VARCHAR(50) NULL,
  `department` VARCHAR(200) NULL,
  `asset_title` VARCHAR(255) NULL,
  `descriptions` TEXT NULL,
  `qty` INT(11) NULL DEFAULT 1,
  `cost_centre` VARCHAR(200) NULL,
  `vendor_name` VARCHAR(200) NULL,
  `manufacturer_name` VARCHAR(200) NULL,
  `useful_life` INT(11) NULL,
  `reason` TEXT NULL,
  `location` VARCHAR(255) NULL,
  `created_by` INT(11) NULL,
  `created_at` DATETIME NULL,
  `updated_by` INT(11) NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
