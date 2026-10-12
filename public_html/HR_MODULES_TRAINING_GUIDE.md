# HR & Project Modules — Setup and Training Guide

Covers the features added for requirements 10–13: **project scope/activities, loan
applications, increment requests, vacation requests, and the employee personal-documents
folder**, plus the shared approval engine they all run through.

Read Part 1 before anything else. **The system deliberately does nothing until it is
configured** — no loan or increment can be submitted until you tell it who approves what.
That is intentional: a guessed approver or a guessed salary split silently mispays people.

---

## Contents

| Part | Audience | Section |
|---|---|---|
| 1 | IT / Admin | [One-time installation](#part-1--one-time-installation-it) |
| 2 | HR / Admin | [Configuration, in order](#part-2--configuration-in-order-hr) |
| 3 | Every employee | [Training: all employees](#part-3--training-all-employees) |
| 4 | Managers & supervisors | [Training: managers](#part-4--training-managers-and-supervisors) |
| 5 | HR team | [Training: HR](#part-5--training-hr) |
| 6 | Project managers | [Training: project managers](#part-6--training-project-managers-and-site-in-charge) |
| 7 | Everyone | [What happens automatically](#part-7--what-happens-automatically) |
| 8 | Support desk | [Messages and troubleshooting](#part-8--messages-and-troubleshooting) |
| 9 | Trainer | [Suggested session plan](#part-9--suggested-training-session-plan) |

---

## Part 1 — One-time installation (IT)

Run once, on the server, from the project root:

```bash
php application/sql/apply_sql.php application/sql/hr_extensions_master_data.sql
```

It is safe to re-run — every statement is guarded, so nothing is duplicated or
overwritten. You should see `applied: 28, failed: 0`.

Confirm the scheduled job is running (it drives document-expiry reminders and the leave
ledger). In Perfex this is the standard cron; if it is already set up for the CRM,
nothing more is needed.

**Where everything appears:** all new screens are children of the existing **Timesheets**
group in the left sidebar. Nothing is in a new top-level menu, by design — attendance, HR
and payroll stay in one place.

---

## Part 2 — Configuration, in order (HR)

Go to **Timesheets → HR Master Data**. Work through the tabs **in this order** — later
steps depend on earlier ones.

### Step 1. Employee Categories

Already seeded, based on your requirement. Review and adjust:

| Category | Approval levels | Labour? | Vacation alert |
|---|---|---|---|
| Staff | 2 | No | 30 days |
| Site Supervisor | 2 | No | 30 days |
| Foreman | 2 | No | 30 days |
| Labour | 3 | Yes | — |

The category is the single most important field in the whole system. It decides:
- how many approval levels a loan or increment passes through,
- whether an increment is split 50/30/20 or goes entirely to allowances,
- whether the employee gets the high-balance vacation notice.

### Step 2. Employee Assignment

**Do not skip this.** Every rule keys off the category, and an employee with no category
falls back to the general flow and general policy.

Use the **checkbox column + "Assign to selected"** to classify people in bulk rather than
one at a time. While you are here, also set for each employee:

- **Joining date** — leave blank only if the staff record was created on their actual
  start date. For anyone migrated from another system, set it: seniority and loan
  eligibility are calculated from this.
- **Visa type** and **Salary on joining** — needed on the increment form.

### Step 3. Approval Flows

**Nothing can be submitted until this is done.** For each request type, create a flow and
add its levels in order.

Request types available: *Loan application, Increment request, Vacation request, Document
update, Activity completion*.

For each level you choose who approves:

| Approver type | Resolved from |
|---|---|
| Direct manager | The employee's "Direct manager" field on their HR record |
| Head of department | The manager set on the employee's department |
| Specific person | A named staff member |
| Role | Anyone holding that role |

Two settings matter:

- **"May set the approved amount"** — tick this on the level that decides the *approved*
  loan amount and instalment (your requirement puts this at L2 for staff, L3 for labour).
  Levels without it can only approve or reject what was requested.
- **"Days allowed"** — optional deadline shown on the request.

**Worked example for loans:**

- Flow "Staff Loans", request type *Loan application*, category **Staff**
  - Level 1 — Direct manager
  - Level 2 — Specific person (Finance Manager) — ✅ *May set the approved amount*
- Flow "Labour Loans", request type *Loan application*, category **Labour**
  - Level 1 — Direct manager
  - Level 2 — Head of department
  - Level 3 — Specific person (HR Manager) — ✅ *May set the approved amount*

Leave the category as **"Any category"** if one chain should cover everyone. A
category-specific flow always wins over the "any category" one.

> If an employee has no direct manager set and a level uses "Direct manager", submission
> is refused with a clear message rather than getting stuck. Fix the reporting line on
> their HR record.

### Step 4. Loan Policy

Ships with **no limits** and inactive. Enter your rules, then tick Active:

- Maximum amount, maximum instalments, minimum service (months)
- Maximum concurrent loans
- Maximum instalment as a % of salary

An active policy must set at least one limit — a policy that limits nothing looks like a
control while enforcing none, so it is refused.

Until a policy is active, loan applications are accepted with **no limit checked**.

### Step 5. Increment Split

The **50/30/20 percentages are already recorded**. What cannot be guessed is *which
allowance rows* the 30 and the 20 belong to, because those are your own allowance types.

For each non-labour category:
1. Slot 1 — Basic salary, 50% → tick **Active**
2. Slot 2 — Allowance, 30% → **choose the allowance type** (e.g. Housing) → **Active**
3. Slot 3 — Allowance, 20% → **choose the allowance type** (e.g. Other Allowances) → **Active**

For **Labour**: one row, Allowance 100% → choose Other Allowances → **Active**.

The tab shows a green badge when a category totals 100%, red when it does not. **An
increment will be approved but not applied to pay while the split is not exactly 100%** —
the requester is notified, so nothing is lost, but pay is never touched by a broken rule.

> If you have no allowance types yet, create them in HR Profile first.

### Step 6. Document Types

Already seeded and mapped to the existing staff record fields:

| Document | Reminder | Employee may upload | HR must approve | Updates staff record |
|---|---|---|---|---|
| Passport | 90 days | Yes | Yes | Number + expiry |
| Emirates ID | 60 days | Yes | Yes | Number + expiry |
| Residence Visa | 60 days | Yes | Yes | Expiry |
| Labour Card | 60 days | Yes | Yes | Number |
| Insurance Card | 30 days | No | No | — |
| Insurance List | 30 days | No | No | — |
| Certificate | 30 days | Yes | Yes | — |

Adjust the reminder lead times to suit you. "Updates staff record" is what makes the
master list refresh automatically once HR approves an employee's renewal.

### Step 7. Visa Types

Seeded with Employment / Mission / Visit / Family / Golden. These are just labels — rename
or deactivate freely.

### Configuration checklist

- [ ] Schema applied (`applied: 28, failed: 0`)
- [ ] Categories reviewed
- [ ] **Every employee has a category**
- [ ] Joining dates set for migrated staff
- [ ] Approval flow + levels for **each** request type you will use
- [ ] Loan policy entered and Active
- [ ] Increment split shows **100%** for every category you use
- [ ] Document reminder lead times agreed

---

## Part 3 — Training: all employees

**Audience:** everyone with a login. **Duration:** ~20 minutes.

### 3.1 My Documents

**Timesheets → My Documents**

This is your personal folder. You will see each document you hold, its number, expiry and
status.

**To upload a renewed document:**
1. Click **Upload Document**
2. Choose the type (Passport, Emirates ID, …)
3. Enter the new **number** and **expiry date**
4. Attach the scan (PDF or image)
5. Submit

What happens next: the row shows **Pending HR approval**. HR checks it, and once approved
the company records update automatically — you do not need to email anyone. You will get a
notification either way.

Statuses you will see:

| Status | Meaning |
|---|---|
| Current | This is the live record |
| Pending HR approval | Waiting for HR to confirm |
| Replaced | An older version, kept for history |
| Rejected | HR declined it — the reason is shown |

**Expiry reminders** arrive automatically as notifications before a document expires
(90 days ahead for a passport, 60 for a visa). Renew, then upload here.

### 3.2 Applying for a loan

**Timesheets → Loan Applications → New Loan Application**

The top of the form fills itself in — **years of service**, **outstanding balance** and
**current salary**. You do not type these.

You enter:
- **Reason for the loan**
- **Amount requested**
- **Proposed monthly instalment**
- **First instalment date**

As you type, the **repayment schedule appears below**, showing every instalment and the
date of the last one. Check it looks right before submitting.

Then either **Save draft** (come back later) or **Submit for approval**.

After submitting you can follow the **Approval progress** panel on the right — it shows
each level, who it is with, and any note they left. You will be notified when it is
decided.

> The approver may approve a **lower** amount than you asked for. The schedule is then
> rebuilt from the approved figures, not your original request.

### 3.3 Applying for leave

Leave still uses the **existing leave screen** you already know — nothing has moved. Three
things now happen behind it:

1. **Your balance is enforced.** If you request more annual leave days than you have, the
   request is not accepted and you get a notification saying how many days were actually
   available. Adjust the dates and try again.
2. **You are told about clashes.** If a colleague in your department already has leave
   over the same period, you get a notification naming them and their dates, so you can
   coordinate.
3. **High balance notice.** Staff whose balance goes over 30 days are reminded to plan
   leave, because encashment is not available.

> Sick leave, unpaid leave and the other types are not balance-limited — only annual leave.

---

## Part 4 — Training: managers and supervisors

**Audience:** direct managers, site supervisors, foremen. **Duration:** ~25 minutes.
Covers everything in Part 3 plus the following.

### 4.1 Approving requests

When something needs you, you get a notification with a link. Open it and you will see the
request plus a **Your decision** panel on the right.

- Add a **note** (optional, but it is shown to the employee and kept in the record)
- **Approve** — passes to the next level, or completes if you are the last
- **Reject** — ends the request immediately; later levels are never asked

If your level is flagged **"May set the approved amount"**, you will also see fields to set
the **approved amount**, **approved instalment** and **first instalment date**. Leave them
as they are to accept what was requested, or change them — the repayment schedule is built
from your figures.

You can only act when it is your turn. Levels are strictly in order.

### 4.2 Applying on behalf of someone

For workers who do not use the system themselves:

- **Loans** — on the application form, pick the employee from the **Employee** dropdown.
  The read-only context (service, balance, salary) refreshes for that person.
- **Documents** — open **My Documents**, and if you have HR rights you can pass a staff id
  to upload for them.
- **Leave** — use the existing leave screen as you do today.

The record keeps both names: who it is *about* and who *submitted* it.

### 4.3 Raising an increment

**Timesheets → Increment Requests → New Increment Request**

Pick the employee, and the form fills in automatically:

- Designation, joining date, years of service
- Visa type and visa expiry
- Salary on joining, current salary
- Last increment date and amount
- Evaluation result (if evaluation data exists)

You enter only the **reason**, the **increment amount** and the **effective from** date.

If you see a warning that the split is not configured, the request can still be submitted
but **will not reach pay** until HR completes the split. Tell HR.

After final approval the raise is applied to the salary automatically and recorded in the
history — expand **Full increment history** on the request to see every past change.

---

## Part 5 — Training: HR

**Audience:** HR team. **Duration:** ~40 minutes. Covers Parts 3 and 4 plus the following.

### 5.1 Document reviews

**Timesheets → Document Reviews**

Every employee document update waiting for confirmation. For each row: download the
attachment, check it against the number and expiry entered, then **Approve** or **Reject**
(enter a reason first if rejecting).

**Approving does two things:** it makes that document the current record, retiring the
previous one, and it writes the number and expiry onto the staff master record. That is
the whole point — you do not update the master list by hand.

### 5.2 Expiring documents

**Timesheets → Expiring Documents**

Everything due to expire soon or already expired, across all staff, soonest first. Red
means expired, amber means within 30 days. Click through to the employee's folder.

Employees are reminded automatically, but this is your chase list.

### 5.3 Leave balances

**Timesheets → Attendance Rules → Leave Entitlement**

Balances are **derived from a ledger**, never typed as a single number, so a balance can
never disagree with its own history. For each employee, per leave type, per year, record:

- **Effective from** — the cut-off date the opening balance is true as at. Required. This
  is what makes going live mid-year safe.
- **Opening balance** — days carried in from your old system
- **Entitlement days** — days granted for the period
- **Carried forward** and, optionally, when carried-forward days lapse

Approved annual leave is deducted automatically. If a leave is later rejected or deleted,
the deduction is reversed automatically.

### 5.4 Loan and increment oversight

With HR rights, **Loan Applications** and **Increment Requests** show *all* employees, not
just your own. Use these to see status at a glance.

An increment sitting at **Approved** rather than **Applied to salary** means the split is
not 100% for that category — fix it under HR Master Data → Increment Split.

---

## Part 6 — Training: project managers and site in-charge

**Audience:** project managers, engineers, site in-charge. **Duration:** ~30 minutes.

### 6.1 Setting up a project's scope

Open a project, then the **Scope / Activities** tab.

Click **Add Activity** for each item of work. Per item:

| Field | Why it matters |
|---|---|
| Code, Activity | Your item reference and description |
| Quantity + Unit | Makes "8 of 20 units installed" possible |
| **Weight** | Relative size — a 3-month item should outweigh a 1-day item |
| Responsible | Who owns the item |
| Department | Which department it sits with (QS, Design, …) |
| Status | Not Started / In Progress / On Hold / Completed / Cancelled |
| Budget, Material budget | Cost planning per item |
| Planned start / end | Schedule |
| **Completion needs sign-off** | Leave ticked so claimed progress is reviewed |

**Weight is the important one.** Overall project completion is a *weighted* roll-up, not a
plain average — so getting weights roughly proportional to effort makes the headline
percentage meaningful.

### 6.2 Updating progress, item by item

Click the progress icon on any row, enter the new **percentage** (and quantity done, and a
note), then **Record**.

- If the item needs sign-off, the update is **held pending** and the item's percentage
  does **not** move until a manager approves it. This is deliberate: an unreviewed claim
  cannot inflate the project.
- If it does not need sign-off, it applies at once.
- Reaching **100%** marks the item Completed automatically.

### 6.3 Signing off progress

Pending updates appear at the bottom of the tab, and across all projects under
**Activity Sign-offs** (`admin/project_scope/approvals`). Approve to let the update count,
or reject with a reason — a rejected update leaves the percentage where it was.

### 6.4 Reading the headline numbers

The tab header shows:

- **Overall completion** — weighted, with Cancelled items excluded
- **Budget**, **Material budget**, **Actual (expenses)** and **Remaining** — actual is the
  expenses already booked to the project

> The project's own progress bar is updated from this roll-up **only when the project is
> set to manual progress**. If the project is set to calculate progress from tasks, that
> is left alone so the two calculations never fight.

---

## Part 7 — What happens automatically

Once the scheduled job is running, no one has to remember these:

| Job | What it does |
|---|---|
| Document expiry reminders | Notifies the employee and HR ahead of each expiry, using that document type's lead time. Sent once per document per stage — renewing re-arms it |
| Leave ledger sync | Records approved annual leave as taken, and reverses it if the leave is later rejected or deleted |
| Forgotten check-out alerts | Flags check-ins never closed (from the attendance work) |

---

## Part 8 — Messages and troubleshooting

Common messages, what they mean, and who fixes them.

| Message | Cause | Fix |
|---|---|---|
| "No approval flow is configured for loans for this employee" | No flow for that request type / category | HR: create it under HR Master Data → Approval Flows |
| "The loan approval flow has no levels defined" | Flow exists but is empty | HR: add levels |
| "An approver in the chain could not be identified" | A level uses Direct manager or Head of department, and that person is not set | HR: set the employee's direct manager, or the department's manager |
| "Approved, but not yet applied to pay" | The increment split for that category does not total 100% | HR: complete and activate the split, pick allowance types |
| "Your leave request for X day(s) was not accepted: only Y available" | Annual leave exceeds the ledger balance | Employee: shorten the request. HR: check the entitlement if the balance looks wrong |
| "This record is in use and cannot be deleted" | A category / type is referenced by existing records | Deactivate it instead of deleting |
| "An active policy must set at least one limit" | Loan policy ticked Active with every limit blank | Enter at least one limit |
| "An active allowance slice must name an allowance type" | Split row set Active without choosing the allowance | Choose the allowance type |
| Loan applications accept any amount | No loan policy is active | HR: enter and activate the policy |
| Employee sees no category on their record | Not assigned | HR: HR Master Data → Employee Assignment |

### Known limitations to communicate

Be straight with trainees about these three:

1. **Loan instalments are not yet deducted by payroll automatically.** The schedule,
   balance and recovery functions exist, but the final hook into payslip generation is not
   connected — for now, recovery must be applied in payroll manually.
2. **Available leave days are not shown on the leave form itself.** The balance is enforced
   and you are notified if you exceed it, but the number is not displayed on that screen
   (it belongs to the licensed module and editing it would be lost on its next update).
3. **Evaluation and KPI results** only appear on the increment form if evaluation data
   exists in the system. There is no evaluation module installed at present.

---

## Part 9 — Suggested training session plan

Run configuration before any session. Training an unconfigured system teaches people
error messages.

| Session | Who | Length | Content |
|---|---|---|---|
| 0. Configuration workshop | HR + IT | 90 min | Part 2, all seven steps, completing the checklist together |
| 1. All employees | Everyone | 20 min | Part 3 — My Documents, loans, leave |
| 2. Managers | Managers, supervisors, foremen | 25 min | Part 4 — approving, on-behalf, increments |
| 3. HR team | HR | 40 min | Part 5 — reviews, expiring list, entitlements, oversight |
| 4. Project managers | PMs, engineers, site in-charge | 30 min | Part 6 — scope, progress, sign-off |
| 5. Support desk | IT / super-users | 30 min | Part 8 — messages and troubleshooting |

**Tips for the trainer**

- Do session 1 with a real device in someone's hands — the document upload is the step
  people get wrong.
- For session 2, set up a dummy loan beforehand so approvers can actually click Approve.
- For session 4, build a small scope of 4–5 items live and show how changing a **weight**
  moves the headline percentage. That is the concept people miss.
- Emphasise repeatedly: **status "Approved" is not the same as "Applied"** for increments,
  and **"Pending" progress does not count** for project completion.

---

## Quick reference — where things live

| Task | Location |
|---|---|
| Configure everything | Timesheets → HR Master Data |
| Leave entitlements and balances | Timesheets → Attendance Rules → Leave Entitlement |
| Apply for / manage loans | Timesheets → Loan Applications |
| Raise / manage increments | Timesheets → Increment Requests |
| My personal documents | Timesheets → My Documents |
| Review document updates | Timesheets → Document Reviews |
| Documents expiring soon | Timesheets → Expiring Documents |
| Project scope and progress | Open a project → Scope / Activities tab |
| Progress sign-off queue | `admin/project_scope/approvals` |
| Attendance dashboard | Timesheets → Attendance Dashboard |
| Project site locations | Timesheets → Project Site Locations |

### Status meanings at a glance

**Loans:** Draft → Pending approval → Approved → Disbursed → Closed. Or Rejected /
Cancelled.

**Increments:** Draft → Pending approval → Approved → **Applied to salary**. Or Rejected /
Cancelled. *Approved but not Applied means the split needs fixing.*

**Documents:** Pending HR approval → Current. Older versions become Replaced. Or Rejected.

**Scope activities:** Not Started / In Progress / On Hold / Completed / Cancelled.
*Cancelled items are excluded from the completion percentage.*
