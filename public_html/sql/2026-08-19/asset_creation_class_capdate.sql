-- ============================================================
-- Carry Asset Class + Capitalization Date onto created assets
--
-- These are captured by the final approver on the Asset Request
-- Form (tblasset_code_requests) but had nowhere to land on the
-- asset records created from that request.
-- ============================================================

ALTER TABLE `tblfe_asset_creation`
  ADD COLUMN `asset_class` VARCHAR(200) DEFAULT NULL AFTER `asset_title`,
  ADD COLUMN `capitalization_date` DATE DEFAULT NULL AFTER `asset_class`;

-- Backfill assets already created from approved requests.
-- tblasset_code_requests.asset_code holds a comma separated list of
-- the asset_uid values that were generated for that request.
UPDATE `tblfe_asset_creation` ac
JOIN `tblasset_code_requests` r
  ON FIND_IN_SET(ac.asset_uid, REPLACE(r.asset_code, ', ', ',')) > 0
SET ac.asset_class         = r.asset_class,
    ac.capitalization_date = r.capitalization_date
WHERE (r.asset_class IS NOT NULL AND r.asset_class != '')
   OR (r.capitalization_date IS NOT NULL AND r.capitalization_date != '0000-00-00');
