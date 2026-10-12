-- ============================================================
-- Superseded: the per-row "VAT Line" flag was removed from the form.
--
-- Amount Excl. VAT is simply the sum of the line amounts, VAT is the sum of the
-- per-line tax amounts, and the net total is the two added together. There is no
-- longer a row type that behaves differently, so the column is dropped.
--
-- Safe to re-run, and safe to run on an install that never had the column.
-- ============================================================

SET @sql = (
  SELECT IF(COUNT(*) = 1,
    'ALTER TABLE `tblinv_shipping_charge_items` DROP COLUMN `is_vat_line`',
    'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblinv_shipping_charge_items'
    AND COLUMN_NAME = 'is_vat_line'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
