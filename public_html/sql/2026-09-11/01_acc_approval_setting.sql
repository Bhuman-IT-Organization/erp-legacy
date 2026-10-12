-- ---------------------------------------------------------------------------
-- Accounting approval settings
--
-- Approval chains for accounting documents, currently cheques, configurable per
-- department. Mirrors tblfe_approval_setting / tblpur_approval_setting so the
-- resolution logic and the settings UI behave the same way across modules.
--
-- Column notes:
--   related    the document type the chain governs, e.g. 'cheque'
--   setting    JSON array of nodes, one per approval step, in order:
--              [{"approver":"specific_personnel","staff":"27"}, ...]
--   departments  comma separated department ids. 0 or empty means all
--                departments, and is used as the fallback when a department has
--                no chain of its own.
--
-- Idempotent: safe to run more than once.
-- Collation matches the tables it is joined against, to avoid
-- "Illegal mix of collations" on the live server where the schema is mixed.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tblacc_approval_setting` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `related` varchar(255) NOT NULL,
  `setting` longtext NOT NULL,
  `choose_when_approving` int(11) NOT NULL DEFAULT 0,
  `notification_recipient` longtext DEFAULT NULL,
  `number_day_approval` int(11) DEFAULT NULL,
  `departments` text DEFAULT NULL,
  `job_positions` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `related` (`related`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Approval progress per document. One row per approval step, frozen at the
-- moment the document is sent for approval - the same shape as
-- tblpur_approval_details, so the panels render identically.
CREATE TABLE IF NOT EXISTS `tblacc_approval_details` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `rel_id` int(11) NOT NULL,
  `rel_type` varchar(45) NOT NULL,
  `staffid` varchar(45) DEFAULT NULL,
  `approve` varchar(45) DEFAULT NULL,
  `note` text DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `staff_approve` int(11) DEFAULT NULL,
  `action` varchar(45) DEFAULT NULL,
  `sender` int(11) DEFAULT NULL,
  `date_send` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rel` (`rel_type`,`rel_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
