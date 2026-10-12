-- ---------------------------------------------------------------------------
-- Shipping charges: add Admin Note, Vendor Note and Terms & Conditions.
--
-- Same three fields the purchase invoice carries (tblpur_invoices.adminnote,
-- vendor_note, terms) and the same TEXT type, so the two records stay
-- comparable and the print layouts can share wording.
--
--   adminnote    internal note, not printed for the vendor
--   vendor_note  note addressed to the freight vendor
--   terms        Terms & Conditions. Auto-filled from the vendor's own
--                payment_terms when it has any, otherwise chosen from the
--                tblpur_terms template library - the same rule the purchase
--                order form applies.
--
-- Written as plain ALTERs rather than guarded by information_schema lookups,
-- because the live database user cannot read information_schema.
--
-- RE-RUNNING THIS FILE IS SAFE. On a second run MySQL reports:
--     ERROR 1060 (42S21): Duplicate column name 'adminnote'
--     ERROR 1060 (42S21): Duplicate column name 'vendor_note'
--     ERROR 1060 (42S21): Duplicate column name 'terms'
-- Those three errors mean the change is already applied. Nothing else should
-- error; anything else is a real problem worth investigating.
--
--   mysql -u root nextgen_live -e "source sql/2026-09-01/shipping_charge_notes_and_terms.sql"
-- ---------------------------------------------------------------------------

ALTER TABLE `tblinv_shipping_charges`
  ADD COLUMN `adminnote` TEXT NULL DEFAULT NULL AFTER `date_created`;

ALTER TABLE `tblinv_shipping_charges`
  ADD COLUMN `vendor_note` TEXT NULL DEFAULT NULL AFTER `adminnote`;

ALTER TABLE `tblinv_shipping_charges`
  ADD COLUMN `terms` TEXT NULL DEFAULT NULL AFTER `vendor_note`;

-- ── Verification ───────────────────────────────────────────────────────────
SHOW COLUMNS FROM `tblinv_shipping_charges`;

SELECT COUNT(*) AS existing_charges,
       SUM(CASE WHEN `adminnote`   IS NULL OR `adminnote`   = '' THEN 1 ELSE 0 END) AS without_adminnote,
       SUM(CASE WHEN `vendor_note` IS NULL OR `vendor_note` = '' THEN 1 ELSE 0 END) AS without_vendor_note,
       SUM(CASE WHEN `terms`       IS NULL OR `terms`       = '' THEN 1 ELSE 0 END) AS without_terms
FROM `tblinv_shipping_charges`;

-- The auto-populate source. Every vendor carrying payment_terms means the terms
-- box fills itself and the template dropdown stays disabled for that vendor.
SELECT COUNT(*) AS vendors_total,
       SUM(CASE WHEN `payment_terms` IS NOT NULL AND `payment_terms` <> '' THEN 1 ELSE 0 END) AS vendors_with_terms
FROM `tblpur_vendor`;

-- The fallback source when a vendor has no terms of its own
SELECT `id`, `name` FROM `tblpur_terms` ORDER BY `id`;
