-- ---------------------------------------------------------------------------
-- Optional: normalise the collation of the staging table
-- ---------------------------------------------------------------------------
--
-- YOU ALMOST CERTAINLY DO NOT NEED THIS FILE ANY MORE.
--
-- File 00 now drops and rebuilds the staging table with the collation stated
-- explicitly, so simply running file 00 again fixes this. That is the better route,
-- because it also guarantees the row count and the total.
--
-- This file is kept only for the case where you want to correct the collation without
-- reloading the rows at all.
--
--
-- On MySQL 8 the old version of file 00 left the table on the server default,
-- utf8mb4_0900_ai_ci, while tblpur_vendor is utf8mb4_general_ci. That is what
-- produced
--
--     ERROR 1267 (HY000): Illegal mix of collations
--     (utf8mb4_general_ci,IMPLICIT) and (utf8mb4_0900_ai_ci,IMPLICIT)
--     for operation '='
--
-- at the two vendor matching UPDATEs in file 02.
--
--
-- YOU DO NOT HAVE TO RUN THIS FILE. File 02 now forces the comparison collation, so
-- it works regardless of what this table carries. This file only makes the stored
-- data consistent with the rest of the database, which is tidier and avoids the same
-- surprise if anyone writes their own query against it later.
--
-- Nothing is deleted. CONVERT TO CHARACTER SET rewrites the column definitions in
-- place and leaves every row exactly as it is. The values are plain ASCII, so no
-- character can change.
--
-- One statement, so it either applies completely or does nothing. Safe to re-run:
-- running it when the collation is already correct is simply a no-op rebuild.
-- ---------------------------------------------------------------------------

ALTER TABLE `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`
    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;


-- Confirm. Expect utf8mb4_general_ci, and 180 rows still totalling -28330450.48.
SELECT `TABLE_COLLATION`
FROM `information_schema`.`TABLES`
WHERE `TABLE_SCHEMA` = DATABASE()
  AND `TABLE_NAME` = 'ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026';

SELECT COUNT(*) AS rows_present,
       ROUND(SUM(`amount`), 2) AS total_aed
FROM `ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026`;
