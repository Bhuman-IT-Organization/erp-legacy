-- ---------------------------------------------------------------------------
-- Attachments on asset request forms
--
-- One row per uploaded file. The file itself lives on disk under
-- modules/fixed_equipment/uploads/asset_requests/<request_id>/, and this table
-- records what it is called, who attached it and when, so the view page can list
-- and link the files.
--
-- filetype is stored because it is what decides whether the view renders a
-- thumbnail or a download link, and reading it back off disk on every page load
-- would be wasteful.
--
-- Idempotent: safe to run more than once.
-- Collation matches the tables it is joined against, to avoid
-- "Illegal mix of collations" on the live server where the schema is mixed.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tblasset_code_request_files` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `filetype` varchar(100) DEFAULT NULL,
  `filesize` int(11) DEFAULT NULL,
  `external` varchar(40) DEFAULT NULL,
  `external_link` text DEFAULT NULL,
  `staffid` int(11) DEFAULT NULL,
  `dateadded` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `request_id` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
