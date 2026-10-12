-- ============================================================
-- Shipping charges as line items
--
-- tblinv_shipping_charges held a single amount + one tax_percent, so a customs
-- clearance bill (Bill of Entry, Custom Deposit, Transportation, Gate Pass,
-- Delivery Order, Service Charges ...) could not be entered as it is issued -
-- each line has its own rate and its own VAT%, some 5% and some zero rated.
--
-- Lines now live in tblinv_shipping_charge_items:
--     amount     = qty * rate
--     tax_amount = amount * vat_percent / 100
--
-- The header keeps amount (total excluding VAT), tax_amount (sum of line VAT)
-- and total (net), so the existing grid and the inv_shipping_charge journal
-- carry on working unchanged.
--
-- Safe to re-run.
-- ============================================================

CREATE TABLE IF NOT EXISTS `tblinv_shipping_charge_items` (
  `id`                  INT(11) NOT NULL AUTO_INCREMENT,
  `shipping_charge_id`  INT(11) NOT NULL,
  `description`         VARCHAR(500) NULL DEFAULT NULL,
  `qty`                 DECIMAL(15,2) NOT NULL DEFAULT 1.00,
  `rate`                DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `amount`              DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `vat_percent`         DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  `tax_amount`          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
  `sort`                INT(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_shipping_charge` (`shipping_charge_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Header total VAT. Mixed rates across lines cannot be expressed as one
-- percentage, so the actual figure is stored rather than derived.
SET @sql = (
  SELECT IF(COUNT(*) = 0,
    'ALTER TABLE `tblinv_shipping_charges` ADD COLUMN `tax_amount` DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER `tax_percent`',
    'DO 0')
  FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'tblinv_shipping_charges'
    AND COLUMN_NAME = 'tax_amount'
);
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- Backfill from the old single-rate figures so existing rows stay correct
UPDATE `tblinv_shipping_charges`
SET `tax_amount` = ROUND(`amount` * `tax_percent` / 100, 2)
WHERE `tax_amount` = 0
  AND `tax_percent` > 0;

-- Carry each existing single-amount charge over as one line, so nothing that is
-- already saved appears empty on the new form.
INSERT INTO `tblinv_shipping_charge_items`
  (`shipping_charge_id`, `description`, `qty`, `rate`, `amount`, `vat_percent`, `tax_amount`, `sort`)
SELECT c.`id`, 'Shipping charge', 1.00, c.`amount`, c.`amount`, c.`tax_percent`,
       ROUND(c.`amount` * c.`tax_percent` / 100, 2), 0
FROM `tblinv_shipping_charges` c
WHERE c.`amount` > 0
  AND NOT EXISTS (
    SELECT 1 FROM `tblinv_shipping_charge_items` i WHERE i.`shipping_charge_id` = c.`id`
  );
