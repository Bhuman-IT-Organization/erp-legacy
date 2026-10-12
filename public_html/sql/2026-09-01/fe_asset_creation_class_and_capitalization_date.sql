-- ---------------------------------------------------------------------------
-- Asset Creation: add the Asset Class and Capitalization Date columns.
--
-- The form (asset_creation/manage.php) renders both fields, the list table
-- reads $ac['asset_class'] and $ac['capitalization_date'], and the detail view
-- reads $asset_item->asset_class / ->capitalization_date - but neither column
-- ever existed on tblfe_asset_creation.
--
-- The controller filters the POST down to real columns before writing, so both
-- values were silently discarded on every save while the page still reported
-- success. The list and the detail view always showed "-".
--
--   asset_class          free text, matching render_input() on the form
--   capitalization_date  DATE, matching render_date_input() on the form
--
-- Written as plain ALTERs rather than guarded by information_schema lookups,
-- because the live database user cannot read information_schema.
--
-- RE-RUNNING THIS FILE IS SAFE. On a second run MySQL reports:
--     ERROR 1060 (42S21): Duplicate column name 'asset_class'
--     ERROR 1060 (42S21): Duplicate column name 'capitalization_date'
-- Those two errors mean the change is already applied.
--
--   mysql -u root nextgen_live -e "source sql/2026-09-01/fe_asset_creation_class_and_capitalization_date.sql"
-- ---------------------------------------------------------------------------

ALTER TABLE `tblfe_asset_creation`
  ADD COLUMN `asset_class` VARCHAR(200) NULL DEFAULT NULL AFTER `asset_title`;

ALTER TABLE `tblfe_asset_creation`
  ADD COLUMN `capitalization_date` DATE NULL DEFAULT NULL AFTER `asset_class`;

-- ── Verification ───────────────────────────────────────────────────────────

SHOW COLUMNS FROM `tblfe_asset_creation`;

SELECT COUNT(*) AS existing_assets,
       SUM(CASE WHEN `asset_class` IS NULL OR `asset_class` = '' THEN 1 ELSE 0 END) AS without_class,
       SUM(CASE WHEN `capitalization_date` IS NULL THEN 1 ELSE 0 END)               AS without_cap_date
FROM `tblfe_asset_creation`;
