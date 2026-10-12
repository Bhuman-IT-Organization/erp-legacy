-- ---------------------------------------------------------------------------
-- Shipping charges: add the freight vendor's own invoice date.
--
--   shipping_invoice_date  The date printed on the clearing/freight bill. This
--                          is the date the accounting entries are posted on,
--                          NOT the date the charge was keyed in, so a bill
--                          entered late still lands in the correct VAT period.
--
-- Nullable because the three charges raised before this column existed have no
-- such date; the accounting code falls back to date_created for those.
--
-- Written as plain ALTERs rather than guarded by information_schema lookups,
-- because the live database user cannot read information_schema.
--
-- RE-RUNNING THIS FILE IS SAFE. On a second run MySQL reports:
--     ERROR 1060 (42S21): Duplicate column name 'shipping_invoice_date'
--     ERROR 1061 (42000): Duplicate key name 'idx_isc_shipping_invoice_date'
-- Those two errors mean the change is already applied. Nothing else should
-- error; anything else is a real problem worth investigating.
--
--   mysql -u root nextgen_live -e "source sql/2026-09-01/shipping_charge_invoice_date.sql"
-- ---------------------------------------------------------------------------

ALTER TABLE `tblinv_shipping_charges`
  ADD COLUMN `shipping_invoice_date` DATE NULL DEFAULT NULL AFTER `shipping_invoice_number`;

-- Indexed because the list is sorted by it and reports filter date ranges on it
ALTER TABLE `tblinv_shipping_charges`
  ADD INDEX `idx_isc_shipping_invoice_date` (`shipping_invoice_date`);

-- ── Verification ───────────────────────────────────────────────────────────
SHOW COLUMNS FROM `tblinv_shipping_charges` LIKE 'shipping_invoice_date';

SELECT COUNT(*) AS existing_charges,
       SUM(CASE WHEN `shipping_invoice_date` IS NULL THEN 1 ELSE 0 END) AS without_invoice_date
FROM `tblinv_shipping_charges`;
