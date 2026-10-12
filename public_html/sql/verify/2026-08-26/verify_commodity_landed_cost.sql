-- ============================================================================
-- VERIFICATION ONLY - read only, changes nothing.
-- ============================================================================
--
-- Reproduces Purchase_model::get_commodity_landed_costs() in SQL so the figures
-- shown on the Landed Cost tab can be checked independently.
--
-- Landed cost = net line value + this line's pro rata share of the shipping
-- charge raised against the same invoice, allocated by net line value.
-- Shipping VAT is excluded - it is recoverable input tax on 179020 VAT Paid.
--
-- Note tblpur_invoice_details.item_code holds the commodity ID, not the code.
-- Change the id below to check a different item. 21 = NPRM3500003.
-- ============================================================================

SET @commodity_id = 21;

SELECT
    d.id                                        AS line_id,
    i.id                                        AS invoice_id,
    i.invoice_number,
    i.vendor_invoice_number,
    i.invoice_date,
    d.quantity,
    d.unit_price,
    ROUND(d.into_money - COALESCE(d.discount_money, 0), 2) AS line_net,
    inv.invoice_net,
    ROUND((d.into_money - COALESCE(d.discount_money, 0)) / NULLIF(inv.invoice_net, 0) * 100, 2) AS share_pct,
    COALESCE(sc.shipping_excl_vat, 0)           AS shipping_excl_vat,
    COALESCE(sc.shipping_vat, 0)                AS shipping_vat,
    sc.shipping_numbers,
    -- Allocated freight
    ROUND(
        COALESCE(sc.shipping_excl_vat, 0)
        * ((d.into_money - COALESCE(d.discount_money, 0)) / NULLIF(inv.invoice_net, 0))
    , 2)                                        AS allocated_shipping,
    -- Landed cost for the line
    ROUND(
        (d.into_money - COALESCE(d.discount_money, 0))
        + COALESCE(
            ROUND(COALESCE(sc.shipping_excl_vat, 0)
                * ((d.into_money - COALESCE(d.discount_money, 0)) / NULLIF(inv.invoice_net, 0)), 2)
        , 0)
    , 2)                                        AS landed_total,
    -- Landed cost per unit
    ROUND(
        (
            (d.into_money - COALESCE(d.discount_money, 0))
            + COALESCE(
                ROUND(COALESCE(sc.shipping_excl_vat, 0)
                    * ((d.into_money - COALESCE(d.discount_money, 0)) / NULLIF(inv.invoice_net, 0)), 2)
            , 0)
        ) / NULLIF(d.quantity, 0)
    , 4)                                        AS landed_unit_cost
FROM tblpur_invoice_details d
JOIN tblpur_invoices i
  ON i.id = d.pur_invoice
-- Net value of every line on the invoice: the allocation base
JOIN (
    SELECT pur_invoice, ROUND(SUM(into_money - COALESCE(discount_money, 0)), 2) AS invoice_net
    FROM tblpur_invoice_details
    GROUP BY pur_invoice
) inv ON inv.pur_invoice = d.pur_invoice
-- An invoice can carry more than one shipping charge, so they are summed
LEFT JOIN (
    SELECT invoice_id,
           SUM(total)      AS shipping_excl_vat,
           SUM(tax_amount) AS shipping_vat,
           SUM(total)      AS shipping_total,
           GROUP_CONCAT(shipping_number ORDER BY id) AS shipping_numbers
    FROM tblinv_shipping_charges
    GROUP BY invoice_id
) sc ON sc.invoice_id = d.pur_invoice
WHERE d.item_code = @commodity_id
  AND (i.is_deleted = 0 OR i.is_deleted IS NULL)
ORDER BY i.invoice_date DESC, d.id DESC;

-- ---------------------------------------------------------------------------
-- Sanity check: an invoice's allocated freight must add back to the charge
-- ---------------------------------------------------------------------------
-- Runs across every invoice that has a shipping charge, not just this item's,
-- so a rounding drift anywhere shows up. A few cents of difference on invoices
-- with many lines is expected from per line rounding.
SELECT
    sc.invoice_id,
    i.invoice_number,
    sc.shipping_excl_vat,
    ROUND(SUM(
        ROUND(sc.shipping_excl_vat
            * ((d.into_money - COALESCE(d.discount_money, 0)) / NULLIF(inv.invoice_net, 0)), 2)
    ), 2) AS sum_of_allocations,
    ROUND(sc.shipping_excl_vat - SUM(
        ROUND(sc.shipping_excl_vat
            * ((d.into_money - COALESCE(d.discount_money, 0)) / NULLIF(inv.invoice_net, 0)), 2)
    ), 2) AS rounding_difference
FROM (
    SELECT invoice_id, SUM(total) AS shipping_excl_vat
    FROM tblinv_shipping_charges
    GROUP BY invoice_id
) sc
JOIN tblpur_invoices i ON i.id = sc.invoice_id
JOIN tblpur_invoice_details d ON d.pur_invoice = sc.invoice_id
JOIN (
    SELECT pur_invoice, ROUND(SUM(into_money - COALESCE(discount_money, 0)), 2) AS invoice_net
    FROM tblpur_invoice_details
    GROUP BY pur_invoice
) inv ON inv.pur_invoice = sc.invoice_id
GROUP BY sc.invoice_id, i.invoice_number, sc.shipping_excl_vat;
