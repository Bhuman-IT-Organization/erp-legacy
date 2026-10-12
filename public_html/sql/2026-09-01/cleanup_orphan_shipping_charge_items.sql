-- ---------------------------------------------------------------------------
-- Remove orphaned shipping charge service lines.
--
-- reverse_shipping_charge() deleted the charge row and its ledger entries but
-- never the rows in tblinv_shipping_charge_items. There is no foreign key on
-- that table, so every reversal since the feature was built left its service
-- lines behind, pointing at a shipping_charge_id that no longer exists.
--
-- That is not just clutter: tblinv_shipping_charges.id is AUTO_INCREMENT, and if
-- the counter is ever reset (a restore, or a table rebuild) a new charge could
-- inherit a reversed charge's lines.
--
-- The controller now deletes them, so this only clears the existing backlog.
--
-- REVERSIBLE. Step 1 copies the rows it is about to delete.
-- RE-RUNNABLE. Once clean, the DELETE matches nothing.
--
--   mysql -u root nextgen_live -e "source sql/2026-09-01/cleanup_orphan_shipping_charge_items.sql"
-- ---------------------------------------------------------------------------

-- ── 1. What is orphaned, before touching anything ─────────────────────────

SELECT '--- Orphaned service lines found ---' AS step;

SELECT  it.`shipping_charge_id`,
        COUNT(*)                  AS orphan_lines,
        ROUND(SUM(it.`amount`),2) AS total_amount,
        GROUP_CONCAT(LEFT(it.`description`, 28) SEPARATOR ' | ') AS descriptions
FROM    `tblinv_shipping_charge_items` it
LEFT JOIN `tblinv_shipping_charges` sc ON sc.`id` = it.`shipping_charge_id`
WHERE   sc.`id` IS NULL
GROUP BY it.`shipping_charge_id`
ORDER BY it.`shipping_charge_id`;

-- ── 2. Backup ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `tblinv_shipping_charge_items_orphans_bak_20260901` (
  `id`                 INT(11) NOT NULL,
  `shipping_charge_id` INT(11) NULL,
  `description`        TEXT NULL,
  `qty`                DECIMAL(15,2) NULL,
  `rate`               DECIMAL(15,2) NULL,
  `amount`             DECIMAL(15,2) NULL,
  `vat_percent`        DECIMAL(5,2) NULL,
  `tax_amount`         DECIMAL(15,2) NULL,
  `backed_up`          DATETIME NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tblinv_shipping_charge_items_orphans_bak_20260901`
  (`id`, `shipping_charge_id`, `description`, `qty`, `rate`, `amount`, `vat_percent`, `tax_amount`, `backed_up`)
SELECT  it.`id`, it.`shipping_charge_id`, it.`description`, it.`qty`, it.`rate`,
        it.`amount`, it.`vat_percent`, it.`tax_amount`, NOW()
FROM    `tblinv_shipping_charge_items` it
LEFT JOIN `tblinv_shipping_charges` sc ON sc.`id` = it.`shipping_charge_id`
WHERE   sc.`id` IS NULL;

-- ── 3. Delete ─────────────────────────────────────────────────────────────
-- Written as a JOIN delete rather than a NOT IN subquery, which would delete
-- everything if the charges table were ever empty.

DELETE it
FROM   `tblinv_shipping_charge_items` it
LEFT JOIN `tblinv_shipping_charges` sc ON sc.`id` = it.`shipping_charge_id`
WHERE  sc.`id` IS NULL;

-- ── 4. Verify ─────────────────────────────────────────────────────────────

SELECT '--- After cleanup ---' AS step;

SELECT  (SELECT COUNT(*) FROM `tblinv_shipping_charge_items` it
          LEFT JOIN `tblinv_shipping_charges` sc ON sc.`id` = it.`shipping_charge_id`
         WHERE sc.`id` IS NULL)                                AS orphans_remaining_should_be_0,
        (SELECT COUNT(*) FROM `tblinv_shipping_charge_items`)   AS lines_remaining,
        (SELECT COUNT(*) FROM `tblinv_shipping_charges`)        AS charges;

SELECT '--- Every remaining charge still has its own lines ---' AS step;

-- Aliased line_count, not lines: MariaDB treats LINES as a reserved word and the
-- statement fails with a 1064 syntax error.
SELECT  sc.`id`, sc.`shipping_number`,
        COUNT(it.`id`)            AS line_count,
        ROUND(sc.`amount`, 2)     AS header_amount,
        ROUND(COALESCE(SUM(CASE WHEN it.`description` NOT REGEXP '^[Vv][Aa][Tt]([^A-Za-z]|$)'
                                THEN it.`amount` ELSE 0 END), 0), 2) AS sum_service_lines
FROM    `tblinv_shipping_charges` sc
LEFT JOIN `tblinv_shipping_charge_items` it ON it.`shipping_charge_id` = sc.`id`
GROUP BY sc.`id`, sc.`shipping_number`, sc.`amount`
ORDER BY sc.`id`;

-- ---------------------------------------------------------------------------
-- ROLLBACK, if ever needed:
--
--   INSERT IGNORE INTO tblinv_shipping_charge_items
--     (id, shipping_charge_id, description, qty, rate, amount, vat_percent, tax_amount)
--   SELECT id, shipping_charge_id, description, qty, rate, amount, vat_percent, tax_amount
--   FROM   tblinv_shipping_charge_items_orphans_bak_20260901;
--
-- Once satisfied:
--   DROP TABLE tblinv_shipping_charge_items_orphans_bak_20260901;
-- ---------------------------------------------------------------------------
