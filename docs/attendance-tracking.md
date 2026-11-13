# Attendance Tracking — Build Plan (Slice 20, Phase 2)

**Status:** BUILT (2026-11-13). The sections below are the spec it was built from; deviations are listed here.

**Deviations / details decided while building**
- **Add to Billing is disabled when the counter is 0** (not "counter 0 and no pending changes") — with nothing unbilled in view there is nothing to bill.
- **Save warning** lists every ticked later-than-today date (saved or pending), exactly as D7 says; **Add to Billing** only checks the dates inside the current window.
- Calendar days **outside the shown month** are muted and not clickable; days beyond today + 1 year are disabled.
- A **draft** of unsaved ticks is stashed in `sessionStorage` on Save / Add to Billing and offered back ("Restore your unsaved ticks?") if the page comes back without a success flash; it is pruned against the saved state, so a successful save never re-offers it.
- Shared UI lives in `resources/views/components/attendance/board.blade.php` (used by tutor **and** student pages). All Tailwind classes are in Blade because Tailwind doesn't scan `resources/js`.
- The nav spinner overlay sits on the **layout root, outside `<aside>`** (the aside's CSS transform would trap a fixed overlay) and is reset on `pageshow` (Back button).
- `Attendance.lesson_date` casts as `date:Y-m-d` (see `debugging.md`).
- Test helpers (`attDay`, `attBill`, `attPay`, …) are in `tests/Pest.php`. E2E: `tests/Browser/AttendanceFlowTest.php`, run with `php vendor/bin/pest tests/Browser`.
- **Billing form fix:** its student `<select>` now re-syncs after the `x-for` options render (`x-init` + `$nextTick`), otherwise a prefilled student showed blank and failed the browser's `required` check.
- **Welcome tour + `/how-to-use`:** an Attendance card was added to all three role tours and both audiences on the public page (see `onboarding-feature.md`). Screenshots `tutor-attendance.jpeg` / `student-attendance.jpeg` can be dropped into `public/images/how-to-use/` (until then the card shows an icon).
- **Not done (optional):** a Patch Note (the super_admin writes that in-app).

> Read first: [DATABASE.md](DATABASE.md) (new table → additive migration rules, tenancy rules) and [FEATURE_CHANGES.md](FEATURE_CHANGES.md) (this is a 🟡 additive change: one new table plus small, backward-compatible changes to billing). Also [SECURITY.md](SECURITY.md) (tenant isolation).

---

## 1. What it does (one paragraph)

A tutor opens **Attendance** in the sidebar, picks one of **their own** students, and marks the days that student attended a lesson. They can do it in a **Calendar View** (click days) or a **List View** (tick a checkbox per date, with a Specific-date / Date-range filter). Ticked days glow **faint purple**. A counter shows **"No. of lessons attended"**. **Save** saves the ticks. **Add to Billing** saves, then opens the WhatsApp Billing form **pre-filled** with those dates as lesson lines, so the tutor doesn't have to flip between the calendar and the bill. Once a lesson's bill has been **paid** (based on the payment ledger), its day turns **faint green**. A student sees a **read-only** view of their own attendance.

---

## 2. Decisions (locked unless the user overrides)

| # | Decision | Why |
|---|---|---|
| D1 | **Purple, not yellow**, for "attended". Faint glow = `bg-primary/15 ring-2 ring-primary/40 text-primary-dark shadow-[0_0_10px_rgba(124,58,237,0.35)]`. | Purple is Wowlo's primary colour. Amber already means "achievement" elsewhere in the app. |
| D2 | **Three day states**, not two: **Attended** (purple glow, editable) → **Billed** (purple glow + small **amber dot**, **locked**) → **Paid** (faint green `bg-success/15 ring-success/40 text-success`, locked). **Billed dot** = `bg-accent` (#F59E0B, Wowlo amber), `size-1.5 rounded-full` in the day cell's top-right corner, with a white 1px ring so it stands out on the purple tint. In List View the same row shows an amber **"Billed"** label (`text-accent-dark`) next to the dot. | Without a "billed" state, a billed-but-unpaid lesson would still look purple and be counted, so a second "Add to Billing" press would **bill it twice**. Amber reads as "waiting" and contrasts with both the purple and the green. |
| D3 | **Counter = purple (attended, not yet billed) lessons in the current view.** In Calendar View that's the visible month; in List View it's the filtered range. A small grey sub-line shows `Billed: n · Paid: n`. | The counter always equals **exactly** the lines that "Add to Billing" will create (what you see is what you bill). |
| D4 | **Counter colour = `text-primary-dark` in a pill**, not red. | In Wowlo red (`danger`) means *errors only* (tailwind palette comment). **User can override to red.** |
| D5 | **Billed/paid days are locked.** To change one, the tutor edits or deletes the bill. Deleting a bill automatically returns its days to purple. | Keeps attendance and bills consistent. The FK has `nullOnDelete`. |
| D6 | **"Paid" is derived, never stored.** A bill counts as paid when payments cover it, oldest-first (FIFO): bills ordered by `(billing_month, id)`, running Σ`charges_total` ≤ Σ`payments.amount_paid`. Partial payment ⇒ still "Billed". | Bills have no paid flag, and outstanding is already derived in `App\Services\Ledger`. Adding a stored flag would duplicate the ledger and drift. |
| D7 | **Future dates: allowed to tick and Save (with a warning), blocked from billing.** "Today" = the server's date (Asia/Singapore), passed to the page, never the browser clock. **Save** with any ticked date later than today → a **warning pop-up** listing those dates: "Some ticked dates are later than today: … Save anyway?" with **Go back** / **Yes, save**. **Add to Billing** with any unbilled ticked date later than today in the current view → a **blocking pop-up**: "Please recheck the dates. A later date can't be added to billing: …" with only **Go back**; nothing is saved or billed. The offending days get a red ring (`ring-danger`) until fixed. Server: Save accepts dates up to **today + 1 year**; `toBilling` re-checks and rejects any future unbilled date in the window (backstop). | The user's rule (2026-11-13). It matches the billing form's existing "Date not allowed" pop-up and `BillRequest` (`before_or_equal:today`), so a future date can never reach a bill. |
| D8 | **One attendance per student per day** (unique `(student_id, lesson_date)`). | Covers the normal case. Two lessons in one day can still be billed by adding a manual line in the bill form. |
| D9 | **Bill prefill hours** = hours of that student's most recent `bill_lines` row; blank if they have none (the tutor fills it in). Billing month defaults to the visible calendar month (List View: month of the latest prefilled date). | Attendance has no hours column; the last bill is the best guess. The tutor still reviews everything before saving. |
| D10 | **Student view is read-only and shows attended days only (purple), with no billed/paid colours.** | Money info sits behind the student fee PIN lock (`fee.unlocked` middleware). Showing green "paid" days would leak it around that lock. |
| D11 | **Bill ↔ attendance link is set by the server on bill save**, by matching the bill's lesson dates to the student's unbilled attendance rows. It works for bills made from attendance **and** for manual bills. Creating a bill manually **does not** create attendance rows (one-way). | Never trusts client-sent IDs, and it naturally handles bill edit/delete. |
| D12 | Calendar ⇄ List toggle, month navigation and filtering are **client-side** (no reload). All of the student's attendance rows load on page open (about 100/year, tiny). Pending changes survive toggling. | It's fast, and unsaved ticks aren't lost when switching views. |
| D13 | Calendar = **Alpine.js port** of the pasted originui/react-day-picker design (same layout and spacing; restyled to Wowlo tokens). No React. | The stack is Blade + Alpine (no React build). |

---

## 3. Database (additive — new table only)

Migration: `database/migrations/2026_11_14_000000_create_attendances_table.php`

```php
Schema::create('attendances', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tutor_id')->constrained('users')->cascadeOnDelete();   // server-side only
    $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
    $table->date('lesson_date');
    $table->foreignId('bill_id')->nullable()->constrained('bills')->nullOnDelete(); // set when billed
    $table->timestamps();

    $table->unique(['student_id', 'lesson_date']);   // also serves student lookups
    $table->index('tutor_id');
    $table->index('bill_id');
});
```

- **A row exists ⇔ the student attended that day.** Unticking deletes the row (only allowed when `bill_id IS NULL`).
- No enum ⇒ no CHECK constraint. No Postgres-only SQL. Real `down()` = `dropIfExists`.
- Existing tables untouched ⇒ zero risk to live data. Update the schema map in `docs/DATABASE.md` §4 (add `attendances` to the "tables carrying `tutor_id`" list too).

**Model** `app/Models/Attendance.php`: `$fillable = ['student_id','lesson_date','bill_id']` (**not** `tutor_id`; set it explicitly in code). Cast `lesson_date => 'date'`. `belongsTo` student / tutor (User) / bill.
**User:** `attendances()` hasMany(Attendance, 'student_id'). **Bill:** `attendances()` hasMany.

---

## 4. Routes

Tutor group (`auth, verified, role:tutor,super_admin`, prefix `tutor`, name `tutor.`):

| Method | URI | Action | Name |
|---|---|---|---|
| GET | `attendance` | `index`: student picker (first view) | `tutor.attendance.index` |
| GET | `attendance/{student}` | `show`: calendar/list page. Query: `view=calendar\|list`, `month=YYYY-MM`, `from`, `to` | `tutor.attendance.show` |
| PUT | `attendance/{student}` | `update`: **Save** | `tutor.attendance.update` |
| POST | `attendance/{student}/billing` | `toBilling`: **Add to Billing** (save, then redirect) | `tutor.attendance.billing` |

Student group (`role:student`, prefix `student`): `GET attendance` → `Student\AttendanceController@index`, name `student.attendance.index`. **No `{student}` parameter**: it always uses `auth()->user()`.

**Ownership (every tutor action):** `$student = $request->user()->students()->findOrFail($id);` → **404** for another tutor's student or a non-student ID. Do **not** use implicit `{student}` model binding without this scope. Super_admin is scoped the same way (they see only their own roster's attendance).

---

## 5. Controllers & server logic

### `App\Http\Controllers\Tutor\AttendanceController`

- **index**: `auth()->user()->students()->orderBy('name')->get(['id','name'])` → view `tutor.attendance.index`.
- **show**: ownership check, then load all `attendances` for the student plus statuses (below). Pass to view as JSON: `rows: [{date:'2026-11-03', status:'attended|billed|paid'}]`, `today` (server SGT `Y-m-d`), initial `view/month/from/to` from the query (validated; fall back to calendar + current month).
- **update** (Save) — `AttendanceRequest`:
  - `added` array max:400, `added.*` `date_format:Y-m-d|after_or_equal:2000-01-01|distinct` + `before_or_equal:{now()->addYear()->toDateString()}` (future dates allowed up to 1 year ahead, per D7; the warning pop-up is client-side only)
  - `removed` same rules. Plus hidden `view/month/from/to` (nullable, validated) to return to the same screen.
  - In a `DB::transaction`: `insertOrIgnore` rows for `added` with `tutor_id = auth()->id()`, `student_id = $student->id` (timestamps set). Delete rows for `removed` **only where `bill_id IS NULL`**. Count the skipped locked rows and, if any, flash `warning`: "N lesson(s) are already billed and were not changed. Edit the bill to change them."
  - Redirect to `show` with the same view state + flash `status` = "Attendance saved."
- **toBilling** (Add to Billing): same validation + save as `update`, plus `window_from`/`window_to` (required dates, from ≤ to, span ≤ 366 days). Then query **server-side** the student's rows with `bill_id IS NULL` and `lesson_date` in the window. If any of them is **later than today** → redirect back with error "Please recheck the dates. A later date can't be added to billing: …" (no prefill; this backstops the client pop-up, D7). Do this check **before** saving, so a blocked billing attempt saves nothing, matching the client behaviour. If none, redirect back with error "Tick at least one unbilled lesson first." Otherwise `session()->flash('attendance_prefill', ['student_id'=>…, 'dates'=>[…], 'billing_month'=>…])` → redirect to `tutor.billing.create`. **Never trust client-sent dates for the bill**: only the server query decides.
- Extract the shared save into a private method or `App\Services\AttendanceSaver`.

### Status computation: `App\Services\Ledger::paidBillIds(User $student): array`

```text
paid = totalPaid(student); running = 0
for bill in student.bills ordered by billing_month, id:
    running += bill.charges_total
    if running <= paid + 0.005: mark bill.id paid   else: stop
```
Row status: `bill_id null` → `attended`; `bill_id in paidBillIds` → `paid`; else `billed`.

```text
Day lifecycle
  (untick)
 ◄────────┐
 empty ───┴─tick──► ATTENDED ──Add to Billing + save bill──► BILLED ──payments cover bill──► PAID
                    purple                                  purple + amber dot              green
                    editable  ◄──────── bill deleted ───────  locked  ◄──── bill deleted ──── locked

FIFO example — total paid = $300
  Bill (oldest → newest)   running total    vs $300    day colour
  Sep  $120          ──►   $120             ≤  ✔       green  (PAID)
  Oct  $120          ──►   $240             ≤  ✔       green  (PAID)
  Nov  $120          ──►   $360             >  ✘       purple + amber dot (BILLED, $60 short)
  Dec  $120          ──►   (stop — everything after the first unpaid bill stays BILLED)
```

### Billing changes (backward-compatible)

- **`BillController::create`**: if `session('attendance_prefill')` exists, re-check ownership (`students()->find($student_id)`; if missing, ignore the prefill) and pass `prefill = {studentId, billingMonth, lessons:[{lesson_date, hours: lastHours|''}]}` to the view. The existing create flow is unchanged when there's no prefill.
- **`partials/form.blade.php`**: accept an optional `prefill` config. Unlike `initial` (edit), it **does not lock** the student. Show an info strip: "Pre-filled from Attendance: N lessons. Check the hours, then save." Keep `locked: !!config.initial`.
- **Linking (D11)**: add `private function syncAttendance(Bill $bill)`, called inside the existing transactions:
  - `store`: `Attendance::where('student_id',$bill->student_id)->whereNull('bill_id')->whereIn('lesson_date', $lineDates)->update(['bill_id'=>$bill->id])`
  - `update`: first `Attendance::where('bill_id',$bill->id)->update(['bill_id'=>null])`, then the same match.
  - `destroy`: the FK `nullOnDelete` handles it. Also null it explicitly before `delete()` (belt and braces; SQLite FKs are on via `DB_FOREIGN_KEYS`, but don't rely on it).
- Don't change `BillRequest` rules or the money math.

### `App\Http\Controllers\Student\AttendanceController@index`
Load `auth()->user()->attendances()` dates only (no bill info, D10) and render the read-only view.

---

## 6. UI spec (consistent with the app — no black blocks)

Use the existing tokens: page `bg-cream`, cards `bg-white rounded-2xl border border-gray-200 shadow-sm`, headings `text-ink`, captions `text-muted`, primary buttons `bg-primary hover:bg-primary-dark text-white`, `cursor-pointer` on everything clickable, Heroicons (no emojis), `[x-cloak]`. Design reference: `.claude/skills/ui-ux-pro-max` (optional), but **the existing app tokens win**. All attendance screens share one look.

### Sidebar
- Tutor menu: `['label'=>'Attendance','icon'=>'calendar','href'=>route('tutor.attendance.index'),'active'=>request()->routeIs('tutor.attendance.*'),'loading'=>'Attendance tracking…']`, placed **just before "WhatsApp Billing"** (it feeds billing).
- Student menu: same with `student.attendance.*`, placed after Homework.
- Add a `calendar` Heroicon path to the `$icons` array in `layouts/app.blade.php`.
- **Nav spinner:** in the plain-link branch of the sidebar loop, when `$item['loading']` is set, render `x-data="spinner(@js($item['loading']))" @click="start()" @pageshow.window="busy = false"` on a wrapper, plus `<x-spinner-overlay />`. The `pageshow` reset stops the spinner sticking when the user presses Back (bfcache).

### First view: `tutor/attendance/index.blade.php`
Vertically and horizontally centred card (`min-h-[60vh] flex items-center justify-center`): a calendar-icon heading "Attendance Tracking", label "Student", a styled `<select>` "Select a student…". On change, navigate to `show` with spinner "Loading attendance…". Empty roster: a friendly empty state linking to Students.

### Main view: `tutor/attendance/show.blade.php`
- Header row: **student name** (left) + "Change student" link. Right: a segmented toggle **Calendar View | List View** (active = `bg-primary text-white`, inactive = `text-ink hover:bg-primary/10`).
- **Calendar** (`<x-attendance-calendar>` + `Alpine.data('attendanceCalendar')`): white card, month caption centred with chevron prev/next (`size-9` ghost buttons), weekday row Su–Sa (`text-xs text-muted`), 7-col grid of `size-10 rounded-lg` day buttons. Outside-month days `text-ink/30`. **Today** = small primary dot under the number. Future days are clickable like any other (D7). States per D1/D2. Days flagged by a D7 pop-up get `ring-2 ring-danger` until unticked or saved. Each day button gets `aria-pressed` and an `aria-label` like "13 November 2026, attended". Keyboard: Tab/Enter/Space toggle.
- **List**: white card with a header row of 3 columns **Date | Filter (funnel icon button) | Attended**. Clicking Filter (or the funnel) opens a small popover: radio **Specific date** (one date input) / **Date range** (from + to, max 92 days), Apply / Clear. Default range = the whole current month (future days included, D7), **newest first**. Each row: formatted date ("16 Aug 2026, Sun"), status text for locked rows ("Billed" / "Paid"), and a checkbox styled as a rounded square that glows purple when ticked, or shows green with a lock icon when paid. Empty filter result → empty state.
- **Footer** (both views): counter pill (D3/D4) bottom-right: "No. of lessons attended: **4**" + sub-line `Billed: n · Paid: n`. A small legend (purple Attended · amber-dot Billed · green Paid). Buttons: **Save** (primary purple) and **Add to Billing** (`bg-success hover:bg-green-700 text-white`). Both are disabled while there's nothing to do (Save: no pending changes; Add to Billing: counter = 0 and no pending changes). An "Unsaved changes" amber chip appears when there are pending changes.
- **Spinners** (reuse `<x-spinner-overlay />` + `spinner()`): Save → "Saving current attendance…"; Add to Billing → "Adding to bill…". Purple spinner (existing component). The spinner starts only **after** any D7 pop-up is passed.
- **Future-date pop-ups (D7)**, styled like the billing form's existing "Date not allowed" pop-up (reuse its markup/classes):
  - *Save warning*: amber warning icon, title "Later date selected", lists the future dates, buttons **Go back** (secondary) / **Yes, save** (primary).
  - *Add to Billing block*: red icon, title "Please recheck the dates", text "A later date can't be added to billing", lists the dates, one button **Go back**.
  - Only **pending or saved purple** future dates trigger them (billed/paid days can't be future).
- **Unsaved-changes guard:** a `beforeunload` prompt while pending changes exist (removed on submit). Before submit, stash pending changes in `sessionStorage` (`attendance-draft-{studentId}`). On page load, if there's a draft and no success flash, offer "Restore your unsaved ticks?". This recovers from a 419/500 bounce. Wrap storage access in try/catch.
- Forms: add the Enter-guard `@keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"` (CLAUDE.md convention; the filter date inputs live inside the form).
- Flash `status` (green) / `warning` (amber) / validation errors banner at top, using the app's existing flash styling.

### Billing form
The prefill info strip (purple-tinted, info icon) above the lesson lines.

### Student view: `student/attendance/index.blade.php`
Same calendar/list components with a `readonly` prop: no clicks, no checkboxes enabled, no Save/Billing buttons, counter "Lessons attended this month: N", attended = purple only.

### JS
New `resources/js/attendance.js` (imported from `resources/js/app.js` before `Alpine.start()`) holding `Alpine.data('attendancePage', cfg)`: state `rows` (map date→status), `pending` {added:Set, removed:Set}, `view`, `month`, `filter`. Getters: `effectiveStatus(date)`, `monthGrid`, `listDates`, `count`, `billedCount`, `paidCount`, `windowFrom/To`. Methods: `toggle(date)` (no-op if locked/future/readonly), `prevMonth/nextMonth`, `applyFilter`, `submit(kind)`. The two forms post hidden `added[]`, `removed[]`, `view`, `month`, `from`, `to`, `window_from`, `window_to`. Use `Js::from()` in Blade to pass data. **Never put `<x-…>` component tags inside a JS comment in a `<script>`** (known gotcha, see `docs/debugging.md`). Run `npm run build` after.

---

## 7. Error pages (new: none exist yet)

Laravel automatically renders `resources/views/errors/{code}.blade.php`, falling back to `4xx.blade.php` / `5xx.blade.php`.

- `errors/layout.blade.php`: **standalone** (doesn't extend `layouts.app`). It must not touch the DB, `auth()->user()`, or the sidebar, because on a 500 the DB may be the thing that's down. Uses `@vite` CSS, cream background, centred white card, Wowlo logo, Heroicon, title, one-line explanation, buttons: **Go back** (`history.back()`), **Go to Dashboard** (`url('/dashboard')`), **Contact us** (`url('/contact')`). `<meta name="robots" content="noindex">`.
- Pages: `403` (no access), `404` (not found; also what cross-tenant access shows, so word it neutrally: "This page doesn't exist or you don't have access"), `419` (session expired: "Go back and try again; your unsaved ticks can be restored"), `429` (too many requests, try again shortly), `500` (something went wrong on our side), `503` (maintenance), `4xx` and `5xx` generic fallbacks.
- **Never show exception details.** Production must have `APP_DEBUG=false`. The **user checks this in the Render environment panel**; Claude never reads `.env`.
- Errors are already logged to `storage/logs/laravel.log` / Render logs. No extra reporting needed.
- Add a "When a page errors" note to `docs/debugging.md`.

---

## 8. Security & isolation checklist

- [ ] Every tutor action resolves the student through `auth()->user()->students()` → 404 otherwise.
- [ ] `tutor_id` is never in request rules and never in `$fillable`; it's set from `auth()->id()`.
- [ ] Student route takes no ID; it reads `auth()->user()->attendances()` only.
- [ ] Students hitting `/tutor/attendance*` → 403 via role middleware. Tutors hitting `/student/attendance` → 403.
- [ ] Bill prefill comes from a server query + server-flashed session, never from client-sent dates. The billing `create` re-checks prefill ownership.
- [ ] Billed/paid rows can't be removed via a forged `removed[]`.
- [ ] Future dates: Save accepts up to today + 1 year (rejects beyond); `toBilling` rejects any future unbilled date server-side, and the bill form's `BillRequest` still rejects them too.

---

## 9. Tests

All Pest, in-memory SQLite (never Neon). `php artisan test` must stay green.

### Feature: `tests/Feature/AttendanceTest.php` (new)
1. Tutor sees the picker listing **only their** students.
2. Tutor opens a student's page (200); the JSON rows contain only that student's dates.
3. Save adds rows (with the correct `tutor_id`/`student_id`), removes unbilled rows, and is idempotent on duplicate `added`.
4. Save **accepts** a future date (e.g. tomorrow) and today; **rejects** a date more than 1 year ahead and malformed dates (session errors).
5. Save cannot remove a **billed** row (row survives, `warning` flashed).
6. A client-supplied `tutor_id` / `student_id` in the payload is ignored.
7. `toBilling` with a future unbilled date in the window (saved or in `added`) → back with the "recheck the dates" error, **no prefill, nothing saved**. With no unbilled rows in the window → back with error. With rows → redirect to `billing.create` with `attendance_prefill` holding **only** unbilled in-window dates, sorted.
8. `billing.create` with a prefill renders the prefilled dates and last-used hours (blank when there's no prior bill line).
9. Status: attended → billed after a bill is stored, → paid after enough payment, → back to attended after the bill is deleted.
10. Student page shows only their own dates, and the response contains no `billed`/`paid` status (D10).
11. Student → tutor attendance routes = 403. Tutor → student attendance route = 403. Guest → redirect to login.

### Feature: additions to `tests/Feature/MultiTutorTest.php`
- Tutor B → `GET/PUT/POST /tutor/attendance/{tutorA's student}` = **404**, and Tutor A's rows are unchanged.
- Tutor B with a forged `attendance_prefill` session for Tutor A's student → the prefill is ignored (no leak of name or dates).
- Super_admin can't see another tutor's student's attendance (404).

### Feature: additions to `tests/Feature/BillingTest.php`
- Storing a bill links matching unbilled attendance rows (`bill_id` set); rows on other dates are untouched; rows already linked to another bill aren't stolen.
- Updating a bill re-syncs (a removed date → unlinked, an added date → linked).
- Deleting a bill nulls `bill_id`.
- Existing billing tests pass **unchanged** (proof the old path isn't broken).

### Unit: `tests/Unit/LedgerPaidBillsTest.php` (new; uses the DB, so put it under `tests/Feature/` or add `RefreshDatabase`)
`paidBillIds` with no payments, partial payment, exact payment, overpayment/credit, multiple bills (FIFO order by `billing_month` then id), a zero-total bill, and the rounding edge (0.005).

### Feature: `tests/Feature/ErrorPagesTest.php` (new)
- An unknown URL → 404 uses the custom view (`assertSee` the friendly heading, `assertDontSee` stack traces).
- A test-only route registered inside the test that does `abort(500)` / `abort(419)` / `abort(429)` / `abort(503)` → each custom view renders. A thrown `RuntimeException` with `app.debug=false` → the 500 page with no exception message.
- The 500 page renders even when unauthenticated.

### E2E (browser): **Pest 4 browser plugin** (recommended, optional suite)
- Dev-only install: `composer require pestphp/pest-plugin-browser --dev`, `npm i -D playwright`, `npx playwright install chromium`. **The user approved this install on 2026-11-13**, so no need to ask again.
- `tests/Browser/AttendanceFlowTest.php` (RefreshDatabase; if in-memory SQLite doesn't share with the browser server, use a SQLite file `database/testing.sqlite`, **never Neon**):
  1. Log in as a tutor → click **Attendance** → spinner text "Attendance tracking…" → picker centred → choose a student.
  2. Calendar: click 3 past days → purple + counter = 3. Tick tomorrow → Save shows the "Later date selected" warning (Go back keeps the ticks unsaved); Add to Billing shows the "recheck the dates" pop-up and doesn't navigate. Untick tomorrow before continuing.
  3. Toggle to List View → the same 3 rows ticked; filter by date range → counter follows.
  4. Save → spinner "Saving current attendance…" → flash; reload → still ticked.
  5. Add to Billing → spinner "Adding to bill…" → bill form pre-filled with 3 lines → save bill → back to Attendance → the days show the "Billed" dot; record a payment covering it → days green.
  6. A student logs in → Attendance shows their purple days, read-only.
- Add a `tests/Browser` testsuite entry, run separately: `./vendor/bin/pest tests/Browser`. Don't block `php artisan test` on Playwright being installed.

### Manual checklist (add to `docs/TESTING.md`)
Phone width (375px) calendar fits; two-window role isolation (tutor A vs tutor B); Back button after a nav click doesn't leave the spinner stuck; leaving with unsaved ticks prompts; 419 recovery (let the session expire → Save → error page → Go back → restore prompt).

---

## 10. Files to create / change

**Create:** migration · `app/Models/Attendance.php` · `app/Http/Controllers/Tutor/AttendanceController.php` · `app/Http/Controllers/Student/AttendanceController.php` · `app/Http/Requests/AttendanceRequest.php` · `resources/views/tutor/attendance/{index,show}.blade.php` · `resources/views/student/attendance/index.blade.php` · `resources/views/components/attendance-calendar.blade.php` · `resources/views/tutor/attendance/partials/list.blade.php` · `resources/js/attendance.js` · `resources/views/errors/{layout,403,404,419,429,500,503,4xx,5xx}.blade.php` · the tests above.

**Change:** `routes/web.php` · `layouts/app.blade.php` (menu items, `calendar` icon, nav spinner) · `resources/js/app.js` (import) · `app/Models/User.php`, `app/Models/Bill.php` (relations) · `app/Services/Ledger.php` (`paidBillIds`) · `BillController` (prefill + `syncAttendance`) · `tutor/billing/partials/form.blade.php` + `create.blade.php` (prefill) · docs: `DATABASE.md` §3–4, `TESTING.md`, `JOURNAL.md`, `debugging.md`, `CLAUDE.md` (mark Built). Optional: a welcome-tour step + `/how-to-use` section (see `onboarding-feature.md`), and a Patch Note (super_admin writes it in-app).

---

## 11. Build order (checkpoint after each)

1. **Error pages** (standalone, independent) + `ErrorPagesTest`. Checkpoint: visit `/does-not-exist`.
2. **Migration + model + relations.** `php artisan migrate`. Checkpoint: the table exists.
3. **Sidebar tab + nav spinner + picker page** (tutor) and the route skeleton. Checkpoint: click Attendance → "Attendance tracking…" → centred picker.
4. **Calendar View + Save** (attendance.js, calendar component, update action, the D7 Save-warning pop-up). Checkpoint: tick days (incl. a future one → warning), save, reload.
5. **List View + filter**, sharing state with the calendar. Checkpoint: toggle views, filter.
6. **Ledger `paidBillIds` + statuses + bill linking (`syncAttendance`).** Checkpoint: make a bill / payment manually → billed dot / green.
7. **Add to Billing + billing-form prefill + the D7 billing-block pop-up** (and its server backstop). Checkpoint: the full flow, plus a future date being blocked.
8. **Student read-only view.**
9. **Isolation tests (MultiTutorTest), Billing tests, the rest of AttendanceTest**, then `php artisan test` all green.
10. **Pest browser E2E** (install approved).
11. Draft-restore polish, docs updates, `npm run build`, JOURNAL entry.
