-- ============================================================
-- Task Masters: Repeat Every + Related To
-- Makes the hard-coded task dropdowns configurable via CRUD
-- ============================================================

-- ── Repeat Every master ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS `tbltask_repeat_master` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `repeat_every` INT(11) NOT NULL DEFAULT 1,
  `recurring_type` VARCHAR(20) NOT NULL DEFAULT 'month',
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_repeat` (`repeat_every`, `recurring_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tbltask_repeat_master` (`name`, `repeat_every`, `recurring_type`, `sort_order`, `active`) VALUES
  ('Week',     1, 'week',  1, 1),
  ('2 Weeks',  2, 'week',  2, 1),
  ('1 Month',  1, 'month', 3, 1),
  ('2 Months', 2, 'month', 4, 1),
  ('3 Months', 3, 'month', 5, 1),
  ('6 Months', 6, 'month', 6, 1),
  ('1 Year',   1, 'year',  7, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- ── Related To master ───────────────────────────────────────
-- key_name must match a rel_type supported by the task helpers
CREATE TABLE IF NOT EXISTS `tbltask_related_master` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `key_name` VARCHAR(50) NOT NULL,
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_key_name` (`key_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tbltask_related_master` (`name`, `key_name`, `sort_order`, `active`) VALUES
  ('Project',  'project',  1, 1),
  ('Invoice',  'invoice',  2, 1),
  ('Client',   'customer', 3, 1),
  ('Estimate', 'estimate', 4, 1),
  ('Contract', 'contract', 5, 1),
  ('Ticket',   'ticket',   6, 1),
  ('Expense',  'expense',  7, 1),
  ('Lead',     'lead',     8, 1),
  ('Proposal', 'proposal', 9, 1)
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
