-- ---------------------------------------------------------------------------
-- Line items for expenses
-- ---------------------------------------------------------------------------
--
-- One row per line on an expense, the same shape the shipping charge form uses in
-- tblinv_shipping_charge_items.
--
-- The header stays exactly as it is. tblexpenses.amount keeps holding the NET total,
-- and tax still comes from the tax / tax2 columns on the expense, so every existing
-- report, payment and ledger entry carries on working untouched. These rows are the
-- breakdown behind that one figure.
--
-- Safe to re-run: CREATE TABLE IF NOT EXISTS produces a note rather than an error, so
-- it will not stop in phpMyAdmin.
--
-- Collation is stated explicitly rather than left to the server default, which is
-- utf8mb4_0900_ai_ci on MySQL 8 and would not compare against the rest of the schema.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `tblexpense_items` (
    `id`          INT(11)        NOT NULL AUTO_INCREMENT,
    `expense_id`  INT(11)        NOT NULL,
    `description` VARCHAR(500)   DEFAULT NULL,
    `qty`         DECIMAL(15,2)  NOT NULL DEFAULT 1.00,
    `rate`        DECIMAL(15,2)  NOT NULL DEFAULT 0.00,
    `amount`      DECIMAL(15,2)  NOT NULL DEFAULT 0.00
        COMMENT 'qty x rate, excluding tax',
    `tax_percent` DECIMAL(15,2)  NOT NULL DEFAULT 0.00,
    `tax_amount`  DECIMAL(15,2)  NOT NULL DEFAULT 0.00
        COMMENT 'amount x tax_percent',
    `sort`        INT(11)        NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    KEY `expense_id` (`expense_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- Expect the table to exist and be empty on a first run.
SELECT COUNT(*) AS expense_item_rows FROM `tblexpense_items`;
