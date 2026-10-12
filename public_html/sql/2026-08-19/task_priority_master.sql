-- ============================================================
-- Task Priority master (Low / Medium / High / Urgent)
-- Makes get_tasks_priorities() configurable via CRUD.
--
-- IMPORTANT: tbltasks.priority stores these ids as integers.
-- The seed rows use EXPLICIT ids 1-4 to match the previous
-- hard-coded list. Never renumber existing rows.
-- ============================================================

CREATE TABLE IF NOT EXISTS `tbltask_priority_master` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `color` VARCHAR(20) NOT NULL DEFAULT '#333333',
  `sort_order` INT(11) NOT NULL DEFAULT 0,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `tbltask_priority_master` (`id`, `name`, `color`, `sort_order`, `active`) VALUES
  (1, 'Low',    '#777777', 1, 1),
  (2, 'Medium', '#03a9f4', 2, 1),
  (3, 'High',   '#ff6f00', 3, 1),
  (4, 'Urgent', '#fc2d42', 4, 1)
ON DUPLICATE KEY UPDATE `color` = VALUES(`color`), `sort_order` = VALUES(`sort_order`);
