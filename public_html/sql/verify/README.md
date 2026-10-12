# sql/verify — do NOT run these on the live server

Everything in here is a test or diagnostic script. None of it is needed to bring a
database up to date. The files that change the live schema or data stay in
`sql/<date>/`; only those need running on the server.

Kept rather than deleted because each one documents what the matching feature was
expected to do, and they are useful again after any change to that area.

## Read-only — safe to run anywhere

These contain only `SELECT` / `SHOW`. Run them against live if you want to inspect
its state.

| File | What it answers |
|---|---|
| `2026-08-26/verify_commodity_landed_cost.sql` | Landed cost per commodity |
| `2026-08-26/verify_stock_row_landed_cost.sql` | Landed cost per stock row |
| `2026-08-26/verify_vat_report.sql` | VAT report figures and account bucketing |
| `2026-09-01/verify_shipping_charge_gl_and_date.sql` | Shipping charge GL account and posting date |
| `2026-09-01/audit_gl_reversal_coverage.sql` | Whether any GL entry is stranded after its document was deleted. Useful on live — start here when auditing the ledger |

## They WRITE — never run these on live

Each creates a dummy record, exercises it through its workflow, then deletes it. On a
development copy that is harmless. On live it inserts real rows into real tables, and
if the script fails partway the cleanup at the bottom never executes, leaving test
data behind.

| File | Writes to |
|---|---|
| `2026-08-26/verify_expense_double_entry.sql` | `tblexpense_payments`, `tblacc_account_history` |
| `2026-08-29/verify_pur_bid_waiver.sql` | `tblpur_bid_waivers` and its child tables |
| `2026-08-29/verify_pur_bid_waiver_doc_control.sql` | `tblpur_bid_waivers`, `tblpur_bid_waiver_approvals` |
| `2026-09-01/verify_pur_petty_cash_advance.sql` | `tblpur_petty_cash_advances` and its child tables |

## What to run on live instead

See `sql/LIVE_DEPLOY.md` for the ordered list of schema and data scripts.
