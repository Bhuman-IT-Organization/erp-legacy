# AP opening balances as of 30/04/2026

Turns the raw spreadsheet import
`ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026` into named columns,
resolves each line to a vendor, and attaches it to that vendor so it shows on
the vendor statement.

## Sign convention

Straight from the sheet, no reinterpretation:

| Amount in the sheet | Meaning | `balance_type` |
| --- | --- | --- |
| negative, e.g. `-30,689.03` | the vendor is still owed this, we have to pay it | `payable` |
| positive, e.g. `53,924.05` | we already paid it, it sits as an advance with the vendor | `advance` |

## Run order on live

Run these in phpMyAdmin or the MySQL client, in this order, one file at a time.

| # | File | Touches application data? | Re-runnable |
| --- | --- | --- | --- |
| 00 | `00_create_and_load_ap_balance_data.sql` | no, rebuilds the import copy | yes, cleanly |
| 01 | `01_ap_balance_rename_columns.sql` | no | no, stops with #1054 |
| 02 | `02_ap_balance_clean_and_map.sql` | no | yes |
| 03 | `03_vendor_opening_balance_table.sql` | its own new table | yes, guarded |
| 04 | `04_ap_balance_verify.sql` | no, read only | yes |
| 05 | `05_fix_vendor_code_spaces.sql` | `tblpur_vendor`, optional | yes |
| 06 | `06_ap_balance_to_vendor_invoices.sql` | inserts into `tblpur_invoices` | yes, guarded |
| 07 | `07_reverse_ap_balance_invoices.sql` | **removes what 06 and 09 created** | only to undo them |
| 08 | `08_fix_ap_balance_table_collation.sql` | no, superseded by 00 | yes |
| 09 | `09_ap_balance_advances_as_payments.sql` | flags 49 rows in `tblpur_invoices`, inserts 18 more, inserts 49 payments | yes, guarded |
| 10 | `10_hide_ob_carriers_from_ap_ageing.sql` | flags the 18 carriers `paid` | yes |

File 09 is self contained: it needs no change to 06 or 07, and 07 still reverses
everything because `OBADV` starts with `OB`, which its existing patterns already match.

**No file in the run path deletes an application record.** File 00 drops and rebuilds
the imported copy of the spreadsheet, which holds no application data and nothing
references. Files 02, 03 and 06 never delete anything. File 07 is the only file that
removes application records, and undoing file 06 is its entire purpose.

How each file behaves on a second run:

- **00** drops and recreates the staging table, then reloads it with explicit ids 1 to
  180. Always ends with exactly those 180 rows, never duplicates, never a partial
  load, and always in the right collation.
- **03** and **06** each carry a `NOT EXISTS` guard on their own output, so a second
  run inserts nothing rather than duplicating.
- **02** only fills in parsed and resolved columns, so running it again just writes
  the same values.

Because 03 and 06 refuse to load twice, re-running 00 reloads the source data but
does not push it through. To genuinely rework the figures: run 07, clear
`tblpur_vendor_opening_balance` by hand, then 02, 03 and 06 again.

**Run 00 instead of 01 on any server where the spreadsheet was never imported.**
File 01 only renames the columns of a table the import created, so on a server
without that table it fails with

```
#1146 - Table 'ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026' doesn't exist
```

File 00 creates that table already in its final shape and loads all 180 data rows
with the amount and date parsed, so nothing needs importing. Use one or the other,
never both:

```
fresh server   00  ->  02  ->  03  ->  06  ->  09  ->  04
after import   01  ->  02  ->  03  ->  06  ->  09  ->  04
```

**06 and 09 are the files that make the lines appear on the vendor statement.** Files
00 to 03 only prepare and store the data. No PHP is changed anywhere.

File 06 writes one invoice per line, payables with a positive total and advances with a
negative one. File 09 then moves the advances into the Payments column as positives.
After both have run:

| Sheet amount | Meaning | Ends up as | Shows in |
| --- | --- | --- | --- |
| negative | due for payment | an invoice with a positive total | **Amount** column |
| positive | advance already paid | a payment with a positive amount | **Payments** column |

No minus figure reaches the statement.

### About file 01

It is a single `ALTER TABLE`. Run it once. On a second run it fails as a whole
with

```
#1054 - Unknown column 'COL 1' in 'ngpp_ap_balances_30_04_2026_xlsx___ap_balance_30_04_2026'
```

That is expected and harmless - it means the rename already happened. Because it
is one statement it either applies completely or changes nothing, so there is no
half-migrated state. Files 02, 03 and 04 contain no DDL, so nothing that must run
is ever blocked by that error.

### Column mapping

The header names were taken from row 1 of the sheet. The sheet uses "Supplier"
twice, for the SAP code and for the name, so those two are named for what they
actually hold.

| Was | Now | Row 1 of the sheet |
| --- | --- | --- |
| `COL 1` | `sap_supplier_code` | Supplier |
| `COL 2` | `vendor_code` | New System Vendor Code |
| `COL 3` | `supplier_name` | Supplier |
| `COL 4` | `doc_date_raw` | Date |
| `COL 5` | `invoice_no` | INV No |
| `COL 6` | `amount_aed_raw` | Amount AED |
| `COL 7` | `sap_description` | SAP Description |

Added: `id`, `vendor_id`, `doc_date`, `amount`, `balance_type`.

## What to expect from file 04

Measured against the local copy of `nextgen_live`:

```
staging rows                                180
mapped to a vendor                          178
NOT mapped to a vendor                        2
amount failed to parse                        0
date failed to parse                          2
loaded into tblpur_vendor_opening_balance   178

staging total    -28,330,450.48
loaded total     -28,330,282.48

advance    49 lines     3,403,728.46
payable   129 lines   -31,734,010.94
```

The staging total is exactly the grand total row that was on the sheet
(`-28,330,450.48`), so nothing was lost in the parse. The 168.00 gap between
staging and loaded is precisely the two unmapped VEN0077 rows.

## The AP Ageing Detail report

`/admin/accounting/rp_accounts_payable_ageing_detail`

Measured after files 06, 09 and 10: the report shows **31,872,477.27** with every figure
positive. Of that, 31,734,010.94 comes from the opening balances, but the true opening
balance net AP is **28,330,282.48**. The report therefore **overstates payables by
3,403,728.46**, which is exactly the advance total.

### Why

The report selects purchase invoices with

```sql
payment_status != 'paid' AND approval_status = 2 AND is_deleted = 0
```

The 49 advance invoices fail that on both counts - file 06 marks them `paid`, and file
09 flags them `is_deleted = 1` - so they never reach the report. The report reads only
invoices, expenses and bills; it never looks at payments, so the advance payments file
09 created are invisible to it. `pur_invoice_amount_left()` cannot help either: it
returns 0 whenever payments exceed the total, so it can never produce a negative.

### This cannot be fixed from the data alone

The two screens want opposite shapes from the same record:

| Screen | Needs an advance to be | Because |
| --- | --- | --- |
| Vendor statement | a **payment**, positive amount | it derives direction from the sign of the invoice total, so a negative invoice puts a minus in the Amount column |
| AP ageing detail | a live, not-paid **invoice with a negative total** | it simply lists invoice totals |

One record cannot be both, and holding both would double count: the statement would
show `invoiced - paid` as `Y - X - X`.

Three routes were examined and rejected:

- **A negative carrier invoice.** Fixes the ageing report, but puts the minus straight
  back on the statement, which is what file 09 exists to remove.
- **Hiding a negative invoice from the statement via the `currency` filter.** The
  statement filters `currency IN (0, base)` while the ageing report has no currency
  condition, so a non-base currency would hide a record from one and not the other. It
  breaks for the 3 opening balance vendors whose own currency is USD, and it means
  storing a currency that is not true. Rejected.
- **Negative expenses.** The report's expense branch (`paymentmode = ''`) matches 0 rows
  today, and the statement ignores expenses with no `expinv_number`, so it would work.
  But it means recording a vendor advance as a negative expense, which would surface in
  the Expenses screen and every expense based report. The blast radius is worse than the
  problem. Rejected.

### The two ways forward

1. **Accept it.** The vendor statement is correct and is the document you send to a
   vendor. The ageing report overstates by the advance total, and that figure is known
   and constant: 3,403,728.46.
2. **One small PHP change** to
   `Accounting_model::get_data_accounts_payable_ageing_detail()` so it also reads the
   advance payments and emits them as negative rows. Contained to one function,
   additive, and it would make the report tie to the actual AP balance. Not done, because
   the instruction has been to keep to SQL only.

### What file 10 does fix

The 18 carrier invoices were left `unpaid`, so they matched the report filter and showed
as 18 rows of AED 0.00 - noise, contributing nothing. File 10 marks them `paid`, which
takes them out and is also honest: a 0.00 invoice with payments against it has nothing
left to pay.

Verified, 14 checks passed: 18 carrier rows before, 0 after, report total unchanged at
31,872,477.27, and the statement completely unaffected - vendor 67 still shows
511,043.07 positive under Payments with a balance of 18,366,428.73, vendor 77 still
reads -147,000.00.

## If you need to change the figures later

Because 03 and 06 will not re-load once loaded, correcting something is a deliberate
three step move rather than something that can happen by accident:

1. Run **07** to remove the invoice records.
2. Fix whatever was wrong.
3. Run **03** and **06** again.

To reload the source data itself you would also need to clear
`tblpur_vendor_opening_balance` and the staging table by hand. That is left as a
manual step on purpose - no file in this set will do it for you.

## Two rows that need a decision

### VEN0077 EXPRESS PRINTING LLC, 2 lines, -168.00

There is no such vendor. Codes in `tblpur_vendor` stop at VEN0076. Create the
vendor through the UI at **Purchase > Vendors > New Vendor** with vendor code
`VEN0077` so it gets a currency, an AP account and a contact like every other
vendor, then re-run files 02 and 03. They are both re-runnable and will pick the
two rows up.

Deliberately not created by SQL here: a vendor inserted with a bare `INSERT`
would be missing the fields the purchase module expects and would misbehave
later in a way that is hard to trace back to this migration.

### VEN0052 is held by two vendors

| userid | vendor_code | company |
| --- | --- | --- |
| 56 | `VEN0052` | ABM INKS INDUSTRY L.L.C |
| 57 | `' VEN0052'` | HANGZHOU KUANGHU IMP |

File 02 resolves this correctly - it matches on the company name before it
matches on the code, and the sheet says HANGZHOU KUANGHU, so the line lands on
vendor 57. But the duplicate itself is a data problem: decide which of the two
keeps `VEN0052` and give the other one its own code.

## Two rows have no date

`VEN0005` invoice `20260302` and `VEN0059` invoice `PO-37826` have an empty date
cell. They keep `doc_date = NULL` in the staging table so the gap stays visible,
and are loaded with the snapshot date `2026-04-30` so they still appear on the
statement instead of being dropped by a date filter.

## Where the balances show up

Two places, and they hold the same 178 lines:

1. `tblpur_vendor_opening_balance` - one record per line, keyed on the vendor.
   This is the clean record of what was imported.
2. `tblpur_invoices` - one invoice record per line, created by file 06. This is
   what the vendor statement reads, so each line appears as its own dated row.

The statement already reads `tblpur_invoices` and `tblpur_invoice_payment`, which is
why no PHP was needed:

- a **payable** line becomes an invoice with a positive `total`, so it lands in the
  **Amount** column and increases what is owed
- an **advance** line becomes a payment with a positive `amount`, so it lands in the
  **Payments** column and reduces what is owed

### Why advances end up as payments rather than negative invoices

File 06 writes advances as invoices with a negative `total`. The balance comes out
right, but the statement then shows a minus in the Amount column - vendor 67 BIZGROUP
read `-511,043.07`. That is the wrong place for it: an advance is money that has
already gone out, so it belongs in Payments as a positive.

File 09 corrects that in three steps, without touching file 06:

1. flags the 49 negative invoices with `is_deleted = 1`, which is what the statement
   filters on, so they drop out of the Amount column and out of the beginning balance.
   The rows are kept, not removed, and the undo snippet inside file 09 puts them back.
2. creates 18 carrier invoices at 0.00, one per vendor with advances
3. creates 49 payments against those carriers, each with its own date and reference

Moving the figure is presentation only, the balance does not shift. The statement treats
the two forms identically:

```
as a negative invoice   running balance does  tmp - (-511,043.07) = tmp + 511,043.07
as a payment            running balance does  tmp + 511,043.07
```

and in the summary, `balance_due = invoiced_amount - amount_paid + beginning_balance
+ refunds`, so a fall in `invoiced_amount` and a rise in `amount_paid` of the same
figure give the same answer. Vendor 67 still ends on `18,366,428.73` either way.

### The carrier invoices

The statement reads payments through a join to the invoice table, so a payment has to
hang off an invoice - there is no payment on account in this schema. Ten of the
eighteen vendors with advances have no payable line to attach one to.

File 09 therefore creates one carrier invoice per vendor with advances, numbered
`OBADVnnnnn`, with a total of **0.00**. Zero is deliberate: a carrier with a real
value would add to what is owed and move the balance, which is exactly what must not
happen. It shows on the statement as a single `0.00` row above that vendor's advance
payments. That row is the one visible cost of doing this without touching any PHP.

One carrier per vendor, rather than attaching advances to an existing payable, so no
payable invoice has its payment status distorted.

### Undoing just file 09

The snippet is in the header of file 09 itself. It removes the carriers and their
payments and clears the `is_deleted` flag, leaving things exactly as file 06 left them.
Verified: 178 invoices visible again with the 49 negatives back.

### If file 02 gave you "Illegal mix of collations"

```
ERROR 1267 (HY000) at line 81: Illegal mix of collations
(utf8mb4_general_ci,IMPLICIT) and (utf8mb4_0900_ai_ci,IMPLICIT) for operation '='
```

Fixed. **Run file 00 again, then file 02** and carry on. File 00 now drops and
rebuilds the table with the collation stated explicitly, so this clears itself up.

What happened: the first version of file 00 said `DEFAULT CHARSET=utf8mb4` without
naming a collation, so on MySQL 8 the table took the server default of
`utf8mb4_0900_ai_ci`, while `tblpur_vendor` is `utf8mb4_general_ci`. The two vendor
matching UPDATEs in file 02 compare `vendor_code` across those tables, and MySQL
refuses to compare two different collations.

It never showed up locally because this MariaDB server's default is already
`utf8mb4_general_ci`, so there was nothing to mix.

Two changes were made:

- Files **00** and **03** now state `COLLATE=utf8mb4_general_ci` explicitly on their
  `CREATE TABLE`, so a fresh server never gets into this state.
- Files **02** and **04** now force the comparison collation with
  `COLLATE utf8mb4_general_ci`, which is what makes them work on the table you have
  *already created*. This matters beyond MySQL 8 defaults, because this database is
  genuinely mixed - `tblpur_invoices` is `utf8mb4_unicode_ci` while `tblpur_vendor` is
  `utf8mb4_general_ci`. The vendor codes are plain ASCII like `VEN0072`, so forcing a
  collation changes nothing about what matches.

If you used `--force`, only those two UPDATEs were skipped; every other statement in
file 02 ran, which is why `vendor_id` was left empty. Running the file again fills it
in.

File **08** is now redundant - it only exists for the case where you want to correct
the collation without reloading the rows. Re-running file 00 is the better route
because it also guarantees the row count and the total.

## Set the date range wide enough

This is the one thing that catches people out. The statement folds anything dated
before the range start into a single **Beginning Balance** figure - that is normal
statement behaviour, not a fault.

The import spans **2024-08-15 to 2026-10-10**, so to see every line individually
set the range on the statement tab to at least

```
From  01-01-2024      To  31-12-2026
```

VEN0072 with a range starting 01-09-2026 showed one lump of `-147,000.00`. With the
range above, the same vendor shows a Beginning Balance of `0.00`, the `0.00` carrier
row, and four separate payment rows:

| Date | Details | Amount | Payments |
| --- | --- | --- | --- |
| 10-04-2025 | Invoice OBADV00077 | 0.00 | |
| 10-04-2025 | Payment for OBADV00077 | | 38,000.00 |
| 13-06-2025 | Payment for OBADV00077 | | 36,000.00 |
| 13-06-2025 | Payment for OBADV00077 | | 36,000.00 |
| 07-10-2025 | Payment for OBADV00077 | | 37,000.00 |

All positive, each on its own date, and Balance Due still reads `-147,000.00` - the
total did not move, only the detail behind it appeared.

### What files 06 and 09 deliberately do not do

- **It does not touch document numbering.** The rows carry `number = 0` and an
  `invoice_number` of `OBnnnnn`. The purchase module takes the next invoice number
  from the `next_inv_number` option in `tblpurchase_option`, not from
  `MAX(number)`, and that option is left alone. Verified: the next real invoice is
  still INV00065.
- **It does not post to the general ledger.** `acc_mapping = 0`. The opening trial
  balance already carries these figures, so posting them again would double the
  payable.
- **It does not write to `tblpur_vendor.balance`.** That column is only saved by
  the vendor form when the value is greater than zero, so a negative opening
  balance put there would be silently wiped the next time somebody edited the
  vendor.

### One thing to be aware of

These records also show in the purchase invoice list, because that list reads the
same table the statement does. They are recognisable by the `OB` prefix, by having
no purchase order or GRN behind them, and an advance line shows a negative total.
Run file 07 to remove them all cleanly if that is not wanted.

## Verified: advances in the Payments column

Driving the real statement screen and PDF over HTTP, with files 06 and 07 left exactly
as they were: **40 checks passed, 0 failed.**

Shape after `06` then `09`:

```
178  invoices written by 06, all still on disk
 49  of them flagged hidden by 09  (the advances)
129  payable invoices visible      31,734,010.94   all positive
 18  carrier invoices                       0.00
 49  advance payments                3,403,728.46   all positive
                                     -------------
     net                            28,330,282.48   matches the sheet
```

Vendor 67 BIZGROUP, the reported case:

- `-511,043.07` no longer appears anywhere in the statement
- `511,043.07` renders as a positive, and a cell by cell check confirmed it is in the
  **6th of 7 columns**, which is Payments, not Amount
- Balance Due still reads `18,366,428.73`, unchanged from the previous shape
- the `OBADV00067` carrier row is present, and the PDF renders at 156 KB

Vendor 77 HASSAN, an advance only vendor: `38,000.00`, `36,000.00` and `37,000.00` all
render positive with no minus anywhere, and Balance Due still reads `-147,000.00`.

Also confirmed: file 06 is untouched and still writes all 178 invoices with 49
negatives; file 07 has no `OBADV` specific logic and still reverses everything; no
visible invoice carries a negative total; no advance payment is negative; every carrier
is exactly 0.00; `next_inv_number` is still 65; the 44 real invoices are untouched;
nothing is posted to the ledger; re-running 06 then 09 duplicates nothing; and the undo
snippet restores 178 visible invoices with the 49 negatives back.

## Verified

Against the local copy of `nextgen_live`, driving the real statement screen and
PDF over HTTP with a logged in session: 43 checks passed, 0 failed. That covered
the four VEN0072 rows rendering with their own dates and cheque numbers, the
opposite direction on a payable vendor (22 of 22 rows), a valid 152 KB PDF,
document numbering left untouched, and file 07 removing all 178 records while
leaving the 44 real invoices intact.

The chain was then run against a scratch database built to match live's state exactly
as it stood after the collation failure: application tables present, the staging table
already created in the wrong collation (`utf8mb4_unicode_ci` standing in for
`utf8mb4_0900_ai_ci`, which MariaDB does not have), holding two leftover junk rows,
and `vendor_id` still empty from the half finished run under `--force`.

Starting from that, `00 -> 02 -> 03 -> 06` was run twice:

```
pass  staging  mapped  leftover  collation            ob   ob_inv   ob_total       real_inv
1       180      178       0     utf8mb4_general_ci  178    178     28,330,282.48     44
2       180      178       0     utf8mb4_general_ci  178    178     28,330,282.48     44
```

The rebuild in file 00 replaced the wrongly collated table and its junk rows, and the
second pass changed nothing: file 00 reported 180 rows at `-28,330,450.48` with no
error at all, and files 03 and 06 inserted nothing because their guards saw the data
was already loaded. The 44 real invoices and `next_inv_number` were untouched
throughout.

That guard on 06 replaced an earlier one that checked each row's own OB number.
Because reloading the data used to hand the rows fresh auto-increment ids, the
numbers changed, the check stopped matching, and a second pass produced **356
invoices instead of 178**. Fixed ids in 00 and a whole-set guard in 06 close that off.

The collation fix was verified the same way, by reproducing the failure rather than
assuming:

- the pre-fix version of the vendor match failed with
  `ERROR 1267 ... Illegal mix of collations (utf8mb4_general_ci,IMPLICIT) and
  (utf8mb4_unicode_ci,IMPLICIT) for operation '='`, the same error seen on live
- the fixed file 02 then ran clean against that same wrongly collated table and
  mapped 178 of 180 rows, leaving only the 2 known VEN0077 rows

So the `COLLATE` in file 02 is not merely belt and braces to the rebuild in file 00 -
it is what lets 02 work against a table that is already in the wrong collation.
