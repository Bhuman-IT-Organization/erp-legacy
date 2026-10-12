-- ============================================================
-- Invoice & Packing List print format (Freezone / customs layout)
--
-- The two-page Invoice + Packing List document needs shipping and packing
-- details that are not held anywhere in the database. Verified with an
-- information_schema sweep: only tblitems.hs_code existed; there were no
-- port / destination / notify party / consignee columns, and no per-line
-- pallets or net/gross weight (tblwh_packing_lists only carries a single
-- document level `weight`).
--
-- All columns are nullable so existing invoices are unaffected; the PDF simply
-- leaves the corresponding cell blank until the value is entered.
-- ============================================================

-- ── Header: shipping / customs details ──────────────────────
ALTER TABLE `tblpur_invoices`
  ADD COLUMN `consignee_name`     VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN `consignee_address`  TEXT         NULL DEFAULT NULL,
  ADD COLUMN `port_of_loading`    VARCHAR(200) NULL DEFAULT NULL,
  ADD COLUMN `port_of_discharge`  VARCHAR(200) NULL DEFAULT NULL,
  ADD COLUMN `port_of_exit`       VARCHAR(200) NULL DEFAULT NULL,
  ADD COLUMN `final_destination`  VARCHAR(200) NULL DEFAULT NULL,
  ADD COLUMN `notify_party`       VARCHAR(255) NULL DEFAULT NULL,
  ADD COLUMN `packing_list_number` VARCHAR(100) NULL DEFAULT NULL;

-- ── Lines: packing figures ──────────────────────────────────
ALTER TABLE `tblpur_invoice_details`
  ADD COLUMN `pallets`      DECIMAL(15,2) NULL DEFAULT NULL,
  ADD COLUMN `net_weight`   DECIMAL(15,2) NULL DEFAULT NULL,
  ADD COLUMN `gross_weight` DECIMAL(15,2) NULL DEFAULT NULL;
