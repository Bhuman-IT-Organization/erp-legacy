-- =====================================================
-- Fix: Revert number column back to INT if it was changed
-- The type-based numbering will use the type column + number column together
-- =====================================================

-- If you already changed number to VARCHAR, revert it:
-- ALTER TABLE `tblpur_estimates` MODIFY COLUMN `number` INT NOT NULL;

-- No changes needed - the type column already exists
-- The display will be: EST + TYPE-NUMBER (e.g. EST-FO-00001)
-- Stored as: prefix=EST, type=FO, number=1
