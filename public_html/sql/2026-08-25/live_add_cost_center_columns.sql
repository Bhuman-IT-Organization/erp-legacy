-- ============================================================
-- FIX: "Unknown column 'cost_center' in 'field list'" on the live server
--
-- The cost centre feature was added across several screens (expenses, purchase
-- requests, purchase orders, purchase estimates and asset creation) but the
-- schema changes never reached the live database, so any save that includes the
-- field fails. CodeIgniter rejects the whole statement on an unknown column, and
-- with db_debug off in production the failure can be silent - the record simply
-- does not save.
--
-- Columns below mirror the local (working) database exactly, including the
-- British spelling `cost_centre` on tblfe_asset_creation - that difference is
-- real and the code depends on it.
--
--   tblexpenses.cost_center            INT(11)      NULL
--   tblpur_request.cost_center         INT(11)      NULL
--   tblpur_orders.cost_center          INT(11)      NULL
--   tblpur_estimates.cost_center       INT(11)      NULL
--   tblfe_asset_creation.cost_centre   VARCHAR(200) NULL
--
-- ------------------------------------------------------------
-- HOW TO RUN
-- ------------------------------------------------------------
-- The live database user has no access to information_schema, so the usual
-- "add only if missing" guard cannot be used. Instead the statements below are
-- plain and independent.
--
-- Run them one at a time, or paste the lot and let it run.
--
--   * If a statement reports  #1060 Duplicate column name '...'
--     that column already exists. Nothing is wrong - skip it and carry on.
--   * If a statement reports  #1061 Duplicate key name '...'
--     the index already exists. Same thing, skip it.
--
-- No statement destroys or rewrites data: every one only adds a nullable
-- column, so existing rows are untouched and read as NULL.
-- ============================================================


-- ── 1. Cost centre master table ─────────────────────────────
-- Referenced by every cost_center column below and by admin/cost_centers.
-- Safe as-is: does nothing when the table is already there.
CREATE TABLE IF NOT EXISTS `tblcost_centers` (
  `id`          INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name`        VARCHAR(255) NOT NULL,
  `created_by`  INT(11) DEFAULT NULL,
  `datecreated` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── 2. tblexpenses ──────────────────────────────────────────
-- This is the table the reported error came from.
ALTER TABLE `tblexpenses` ADD COLUMN `cost_center` INT(11) DEFAULT NULL;
ALTER TABLE `tblexpenses` ADD KEY `idx_expenses_cost_center` (`cost_center`);


-- ── 3. tblpur_request ───────────────────────────────────────
ALTER TABLE `tblpur_request` ADD COLUMN `cost_center` INT(11) DEFAULT NULL;
ALTER TABLE `tblpur_request` ADD KEY `idx_pur_request_cost_center` (`cost_center`);


-- ── 4. tblpur_orders ────────────────────────────────────────
ALTER TABLE `tblpur_orders` ADD COLUMN `cost_center` INT(11) DEFAULT NULL;
ALTER TABLE `tblpur_orders` ADD KEY `idx_pur_orders_cost_center` (`cost_center`);


-- ── 5. tblpur_estimates ─────────────────────────────────────
ALTER TABLE `tblpur_estimates` ADD COLUMN `cost_center` INT(11) DEFAULT NULL;
ALTER TABLE `tblpur_estimates` ADD KEY `idx_pur_estimates_cost_center` (`cost_center`);


-- ── 6. tblfe_asset_creation ─────────────────────────────────
-- Note the spelling: this one is `cost_centre` and stores the NAME, not an id.
ALTER TABLE `tblfe_asset_creation` ADD COLUMN `cost_centre` VARCHAR(200) DEFAULT NULL;


-- ── 7. Check the result ─────────────────────────────────────
-- SHOW COLUMNS needs no special privileges. Each should return one row.
SHOW COLUMNS FROM `tblexpenses`          LIKE 'cost_center';
SHOW COLUMNS FROM `tblpur_request`       LIKE 'cost_center';
SHOW COLUMNS FROM `tblpur_orders`        LIKE 'cost_center';
SHOW COLUMNS FROM `tblpur_estimates`     LIKE 'cost_center';
SHOW COLUMNS FROM `tblfe_asset_creation` LIKE 'cost_centre';
