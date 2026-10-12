# Live deployment — SQL to run, 26 Aug to 1 Sep 2026

Only these files change the database. Run them in this order; each is re-runnable, so
a second run is harmless. Test and diagnostic scripts have been moved to
`sql/verify/` and must **not** be run on live — see `sql/verify/README.md`.

Every script prints its own verification at the end. Check that output before moving to
the next one.

    mysql -u <user> -p <database> -e "source sql/<path>"

## Order

| # | File | What it does | Type |
|---|---|---|---|
| 1 | `2026-08-26/expense_payments.sql` | Creates `tblexpense_payments`; adds columns to `tblexpenses`; backfills them | schema + data |
| 2 | `2026-08-26/expense_credit_payable_account.sql` | Adds/updates the credit-expense payable account option; corrects `tblexpenses` and existing GL rows | data |
| 3 | `2026-08-29/pur_bid_waiver.sql` | Creates the 3 bid waiver tables; inserts 4 purchase options | schema + data |
| 4 | `2026-09-01/pur_petty_cash_advance.sql` | Creates the 3 petty cash advance tables; inserts 3 purchase options | schema + data |
| 5 | `2026-09-01/shipping_charge_cost_center_invoice_no.sql` | Adds `cost_center`, `shipping_invoice_number` + index to `tblinv_shipping_charges` | schema |
| 6 | `2026-09-01/shipping_charge_invoice_date.sql` | Adds `shipping_invoice_date` + index | schema |
| 7 | `2026-09-01/shipping_charge_notes_and_terms.sql` | Adds `adminnote`, `vendor_note`, `terms` | schema |
| 8 | `2026-09-01/fe_asset_creation_class_and_capitalization_date.sql` | Adds `asset_class`, `capitalization_date` to `tblfe_asset_creation` | schema |
| 9 | `2026-09-01/cleanup_orphan_shipping_charge_items.sql` | Deletes shipping charge line items orphaned by past reversals | data |
| 10 | `2026-09-01/repair_shipping_charge_gl_accounts.sql` | Repoints freight debits from the old hardcoded account 112 to the purchase order's own GL account | **data — accounting** |
| 11 | `2026-09-01/repair_stuck_acc_mapping_flags.sql` | Clears `acc_mapping` on documents flagged as posted that have no GL entries, so they can be posted again | **data — accounting** |
| 12 | `2026-09-01/clear_placeholder_email_signature.sql` | Clears the `email_signature` option, which held the literal text "this is test mail" and was signing every outgoing notification | data |
| 13 | `2026-09-01/restructure_ap_aging_report.sql` | Rebuilds the AP aging import table: adds an `id` primary key, names the columns, types the amount and date, drops the spreadsheet header and grand total rows | schema + data |
| 14 | `2026-09-01/pur_order_number_unique.sql` | UNIQUE index on `tblpur_orders.pur_order_number`, stopping two purchase orders sharing a number. **Must ship together with the retry logic in `Purchase_model::add_pur_order()`** - the index without the retry turns a silent duplicate into a failed save | schema |

Item 13 is independent of the rest and can be run at any point. It is **not**
re-runnable: the table swap fails on a second run because the backup already exists,
which is deliberate so a repeat run cannot overwrite the only copy of the original.

Items 5, 6 and 7 all alter the same table and must run before 9 and 10.

## Expected harmless errors

The schema scripts use plain `ALTER TABLE` rather than `information_schema` lookups,
because the live database user cannot read `information_schema`. On a second run MySQL
reports the change is already present:

    ERROR 1060 (42S21): Duplicate column name '...'
    ERROR 1061 (42000): Duplicate key name '...'

Those two are expected. **Any other error is real** — stop and investigate.

## Items 10 and 11 touch posted accounting

Read these before running:

- Both take a backup first. Item 10 copies every row it will modify into
  `tblacc_account_history_bak_20260901`; item 11 logs each cleared flag to
  `tblacc_mapping_reset_log_20260901`. Rollback statements are in each file header.
- Item 10 changes **only** the debit account and its contra `split`. Amounts, dates,
  VAT lines and AP credits are untouched, so entries stay balanced and stay in their
  original period. The script prints a before/after table and a balance check.
- Item 11 changes **no ledger entry at all**. It only clears a flag so a stranded
  document becomes eligible for posting again.
- Take a database backup before running either, as usual for accounting changes.

## After deploying

Two things need doing by hand, and neither is a SQL script, because both post real
money to the ledger:

1. **INV00044** was flagged as posted but had no GL entries. Item 11 makes it eligible
   again — post it from the accounting module.
2. **ISC00001** (shipping charge, 20,000.00) has never had GL entries. Open it and save
   it to post them. It will date them 2026-08-07, its original date.

Then run `sql/verify/2026-09-01/audit_gl_reversal_coverage.sql` on live. It is
read-only and confirms nothing is stranded in the ledger.
