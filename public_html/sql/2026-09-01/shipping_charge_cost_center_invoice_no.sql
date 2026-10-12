-- ---------------------------------------------------------------------------
-- Shipping charges: add Cost Center and Shipping Invoice Number.
--
--   cost_center             FK to tblcost_centers.id, the same list the Purchase
--                           Request and Purchase Order forms already use
--   shipping_invoice_number the freight vendor's own invoice number, free text,
--                           alongside our generated shipping_number (ISC00001)
--
-- Written as plain ALTERs rather than guarded by information_schema lookups,
-- because the live database user cannot read information_schema.
--
-- RE-RUNNING THIS FILE IS SAFE. On a second run MySQL reports:
--     ERROR 1060 (42S21): Duplicate column name 'cost_center'
--     ERROR 1060 (42S21): Duplicate column name 'shipping_invoice_number'
--     ERROR 1061 (42000): Duplicate key name 'idx_isc_cost_center'
-- Those three errors mean the change is already applied. Nothing else should
-- error; anything else is a real problem worth investigating.
--
--   mysql -u root nextgen_live -e "source sql/2026-09-01/shipping_charge_cost_center_invoice_no.sql"
-- ---------------------------------------------------------------------------

ALTER TABLE `tblinv_shipping_charges`
  ADD COLUMN `cost_center` INT(11) NULL DEFAULT NULL AFTER `vendor`;

ALTER TABLE `tblinv_shipping_charges`
  ADD COLUMN `shipping_invoice_number` VARCHAR(100) NULL DEFAULT NULL AFTER `shipping_number`;

-- Indexed because the list can be filtered and sorted by cost centre
ALTER TABLE `tblinv_shipping_charges`
  ADD INDEX `idx_isc_cost_center` (`cost_center`);

-- ── Verification ───────────────────────────────────────────────────────────
SHOW COLUMNS FROM `tblinv_shipping_charges` LIKE 'cost_center';
SHOW COLUMNS FROM `tblinv_shipping_charges` LIKE 'shipping_invoice_number';

SELECT COUNT(*) AS existing_charges,
       SUM(CASE WHEN `cost_center` IS NULL THEN 1 ELSE 0 END) AS without_cost_center,
       SUM(CASE WHEN `shipping_invoice_number` IS NULL OR `shipping_invoice_number` = '' THEN 1 ELSE 0 END) AS without_invoice_no
FROM `tblinv_shipping_charges`;
