-- ============================================================
-- Fix: tblinv_shipping_charges was missing the shipping_number
-- column that the code already reads and writes.
--
-- Symptoms this resolves:
--   * admin/purchase/shipping_charges - DataTable AJAX returned a
--     500 ("Unknown column 'shipping_number'"), so the list page
--     never populated.
--   * Saving a shipping charge failed, since save_shipping_charge()
--     inserts and then updates shipping_number.
-- ============================================================

ALTER TABLE `tblinv_shipping_charges`
  ADD COLUMN `shipping_number` VARCHAR(50) DEFAULT NULL AFTER `id`,
  ADD KEY `idx_shipping_number` (`shipping_number`);

-- Backfill existing rows with the ISC00001 format used by the code
UPDATE `tblinv_shipping_charges`
SET `shipping_number` = CONCAT('ISC', LPAD(`id`, 5, '0'))
WHERE `shipping_number` IS NULL OR `shipping_number` = '';
