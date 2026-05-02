# TaskPad PHP – Test Plan

This document lists the black-box test cases used in `tests/test_cases.json` and the results from running `php tests/run_tests.php`.

---

## TC01 – Create task happy path
- **Purpose:** Verify a valid task is created and redirects (PRG pattern).
- **Inputs:** POST `/public/create.php` with `title="Buy milk"`, `priority="Low"`, empty `description` and `due`.
- **Pre-state:** `data/tasks.json` empty.
- **Steps:** Submit the create form with the above values.
- **Expected Result:** HTTP 302 redirect to `index.php`; `tasks.json` contains one new open task titled “Buy milk”.
- **Post-state:** One new open task stored.

---

## TC02 – Create task missing title
- **Purpose:** Ensure server-side validation rejects an empty title.
- **Inputs:** POST `/public/create.php` with `title=""`, `description="x"`, `priority="High"`, empty `due`.
- **Pre-state:** `data/tasks.json` empty.
- **Steps:** Submit form with empty title.
- **Expected Result:** HTTP 200; HTML shows “Title is required.”; no redirect.
- **Post-state:** No tasks stored.

---

## TC03 – Create task with invalid date
- **Purpose:** Validate that an invalid date format/value is rejected.
- **Inputs:** POST `/public/create.php` with `title="Has bad date"`, `priority="Medium"`, `due="2025-99-99"`.
- **Pre-state:** `data/tasks.json` empty.
- **Steps:** Submit form with invalid date.
- **Expected Result:** HTTP 200; HTML shows “Invalid date.”; no redirect.
- **Post-state:** No tasks stored.

---

## TC04 – Filter by text only
- **Purpose:** Verify text filter matches title/description.
- **Inputs:** GET `/public/index.php?q=milk`.
- **Pre-state:** `tasks.json` has:
  - `t1`: “Buy milk” (Low, open)  
  - `t2`: “Walk dog” (Medium, open)
- **Steps:** Open list view with `q=milk`.
- **Expected Result:** HTTP 200; HTML contains “Buy milk”.
- **Post-state:** No data change.

---

## TC05 – Filter by priority only
- **Purpose:** Verify priority filter limits results correctly.
- **Inputs:** GET `/public/index.php?priority=High`.
- **Pre-state:** `tasks.json` has:
  - `t1`: “Low task” (Low)  
  - `t2`: “High task” (High)
- **Steps:** Open list view with `priority=High`.
- **Expected Result:** HTTP 200; HTML contains “High task”.
- **Post-state:** No data change.

---

## TC06 – Filter text + priority with no matches
- **Purpose:** Show proper “no matches” state.
- **Inputs:** GET `/public/index.php?q=xyz&priority=Low`.
- **Pre-state:** `tasks.json` has one Low-priority task titled “Some task”.
- **Steps:** Open list with both filters applied.
- **Expected Result:** HTTP 200; HTML shows “No tasks matched your filters”.
- **Post-state:** No data change.

---

## TC07 – Complete existing task
- **Purpose:** Verify completing an existing task sets `completed=true` and redirects.
- **Inputs:** POST `/public/actions.php` with `action="complete"`, `id="t1"`.
- **Pre-state:** `tasks.json` has:
  - `t1`: “Complete me” (Medium, `completed=false`)
- **Steps:** Submit complete action for `t1`.
- **Expected Result:** HTTP 302 redirect to `index.php`; at least one task now has `completed=true`.
- **Post-state:** Task `t1` marked completed.

---

## TC08 – Delete existing task
- **Purpose:** Verify delete removes the correct task and redirects.
- **Inputs:** POST `/public/actions.php` with `action="delete"`, `id="t1"`.
- **Pre-state:** `tasks.json` has:
  - `t1`: “To be deleted”  
  - `t2`: “Keep me”
- **Steps:** Submit delete action for `t1`.
- **Expected Result:** HTTP 302 redirect to `index.php`; `tasks.json` contains only the task titled “Keep me”.
- **Post-state:** Task `t1` removed; `t2` remains.

---

## TC09 – Delete non-existing task id
- **Purpose:** Ensure deleting a non-existent ID does not change data but still redirects.
- **Inputs:** POST `/public/actions.php` with `action="delete"`, `id="zzz"`.
- **Pre-state:** `tasks.json` has:
  - `t1`: “Still here”
- **Steps:** Submit delete action for `id="zzz"`.
- **Expected Result:** HTTP 302 redirect to `index.php`; tasks count unchanged.
- **Post-state:** `t1` still present.

---

## TC10 – Redirect after valid create (PRG pattern)
- **Purpose:** Explicitly verify POST–Redirect–GET behavior on a valid create.
- **Inputs:** POST `/public/create.php` with `title="PRG test"`, `priority="Medium"`, empty `description` and `due`.
- **Pre-state:** `data/tasks.json` empty.
- **Steps:** Submit create form with valid data.
- **Expected Result:** HTTP 302; `Location` contains `index.php`.
- **Post-state:** One new task added.

---

## Test Run Results

Command used:

```bash
php tests/run_tests.php

Running TaskPad PHP tests...

[TC01] PASS - Create task happy path
[TC02] PASS - Create task missing title
[TC03] PASS - Create task with invalid date
[TC04] PASS - Filter by text only
[TC05] PASS - Filter by priority only
[TC06] PASS - Filter text + priority with no matches
[TC07] PASS - Complete existing task
[TC08] PASS - Delete existing task
[TC09] PASS - Delete non-existing task id
[TC10] PASS - Redirect after valid create (PRG pattern)

Total: 10 | Passed: 10 | Failed: 0
