-- ---------------------------------------------------------------------------
-- Stop two purchase orders ever sharing a number.
--
-- THE PROBLEM
--   The number shown on the new Purchase Order form is read from the shared
--   next_po_number option when the page loads:
--
--       var po_next_number = <?php echo get_purchase_option('next_po_number'); ?>;
--
--   and get_next_po_number_by_type() just re-reads the same option. Neither
--   reserves anything. Open the form in two tabs and both display the same
--   number - which is exactly what was reported: two browsers, same user, both
--   showing PO00000093.
--
--   add_pur_order() then checked for a clash with a read-then-write loop:
--
--       SELECT ... WHERE pur_order_number = ?   -- both saves see it free
--       ... INSERT                              -- both insert it
--
--   With no unique constraint on the table, MySQL accepted both and nothing
--   afterwards noticed. tblpur_orders carried only a PRIMARY KEY on id and an
--   index on cost_center.
--
-- THE FIX HAS TWO HALVES AND BOTH ARE NEEDED
--   1. This file: a UNIQUE index, so a duplicate is refused by the database.
--   2. Purchase_model::add_pur_order(): catches the resulting error 1062,
--      re-derives the number from MAX(number) and retries the insert. The
--      counter is also moved forward with GREATEST() instead of being assigned,
--      so two concurrent saves cannot drag it backwards.
--
--   Without the index the retry has nothing to catch. Without the retry the
--   index turns a silent duplicate into a failed save. Deploy them together.
--
-- SAFE TO ADD: checked on this database first - 88 orders, 88 distinct numbers,
-- no NULL and no blank, so nothing blocks the index.
--
-- RE-RUNNING THIS FILE IS SAFE. On a second run MySQL reports:
--     ERROR 1061 (42000): Duplicate key name 'unq_pur_order_number'
-- That means it is already applied.
--
--   mysql -u root inova -e "source sql/2026-09-01/pur_order_number_unique.sql"
-- ---------------------------------------------------------------------------

-- ── Preflight: this must return no rows, or the index will be rejected ─────

SELECT '--- Duplicates or blanks that would block the index ---' AS step;

SELECT `pur_order_number`, COUNT(*) AS copies, GROUP_CONCAT(`id` ORDER BY `id`) AS ids
FROM   `tblpur_orders`
GROUP BY `pur_order_number`
HAVING COUNT(*) > 1
    OR `pur_order_number` IS NULL
    OR TRIM(`pur_order_number`) = '';

-- ── The index ─────────────────────────────────────────────────────────────
-- Only pur_order_number is made unique. The `number` column is deliberately
-- left alone: it is reused as a per-type sequence in places and adding a
-- constraint there could reject legitimate saves.

ALTER TABLE `tblpur_orders`
  ADD UNIQUE INDEX `unq_pur_order_number` (`pur_order_number`);

-- ── Realign the counter with reality ──────────────────────────────────────
-- If the counter has drifted below the highest stored number, the next save
-- would be handed a number already taken and would burn a retry for nothing.

UPDATE `tblpurchase_option`
SET    `option_val` = (SELECT COALESCE(MAX(`number`), 0) + 1 FROM `tblpur_orders`)
WHERE  `option_name` = 'next_po_number'
  AND  CAST(`option_val` AS UNSIGNED) <= (SELECT COALESCE(MAX(`number`), 0) FROM `tblpur_orders`);

-- ── Verify ────────────────────────────────────────────────────────────────

SELECT '--- Index present ---' AS step;
SHOW INDEX FROM `tblpur_orders` WHERE `Key_name` = 'unq_pur_order_number';

SELECT '--- Counter vs stored data ---' AS step;
SELECT (SELECT MAX(`number`) FROM `tblpur_orders`)                                   AS max_number_stored,
       (SELECT `option_val` FROM `tblpurchase_option` WHERE `option_name`='next_po_number') AS next_po_number,
       CASE WHEN (SELECT CAST(`option_val` AS UNSIGNED) FROM `tblpurchase_option`
                   WHERE `option_name`='next_po_number')
                 > (SELECT COALESCE(MAX(`number`),0) FROM `tblpur_orders`)
            THEN 'ok - counter is ahead'
            ELSE 'PROBLEM - counter is behind the data' END                          AS counter_check;

SELECT '--- Still no duplicates ---' AS step;
SELECT COUNT(*) AS duplicate_numbers_should_be_0
FROM (SELECT `pur_order_number` FROM `tblpur_orders`
      GROUP BY `pur_order_number` HAVING COUNT(*) > 1) d;

-- ---------------------------------------------------------------------------
-- ROLLBACK:
--   ALTER TABLE `tblpur_orders` DROP INDEX `unq_pur_order_number`;
-- ---------------------------------------------------------------------------
