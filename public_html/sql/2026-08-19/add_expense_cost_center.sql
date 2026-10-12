-- ============================================================
-- Expenses: Cost Center
-- Adds a cost center reference to expenses, matching the pattern
-- already used by tblpur_request / tblpur_orders.
-- References tblcost_centers.id (managed at admin/cost_centers).
-- ============================================================

ALTER TABLE `tblexpenses`
  ADD COLUMN `cost_center` INT(11) DEFAULT NULL AFTER `credit_gl_account`;

ALTER TABLE `tblexpenses`
  ADD INDEX `idx_expenses_cost_center` (`cost_center`);
