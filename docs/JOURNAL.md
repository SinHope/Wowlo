# Wowlo Journal

A running log of **what we did**, newest first. Not plans (those live in the feature docs) — just a record so we can take a trip down memory lane. Add an entry at the top after each piece of work.

Entry format: `## YYYY-MM-DD — short title` then a few bullets: what changed, why, anything surprising.

---

## 2026-11-14 — Settings: card transparency slider
- Owner asked for an "opacity" slider because the background was hidden behind solid cards. Reframed as **Card transparency** (cards/sidebar/header become frosted glass) rather than image opacity, which wouldn't help. Capped at 50% see-through so text stays readable; modals, inputs and popovers stay solid.
- `users.card_opacity` (additive migration), `--surface-alpha` CSS var, live preview. 5 new tests (suite 344 green).
- Gotcha: `backdrop-filter` (like `transform`) traps `position:fixed` children, so the blur rule excludes cards containing modals/dropdowns via `:has()`.

## 2026-11-14 — Dark mode contrast fix
- Headings, sidebar items and numbers were invisible in dark mode: `ink` was still a hard-coded hex in `tailwind.config.js` (my scripted edit silently missed it because of a cp1252/"—" mismatch, and left a stray non-UTF-8 byte in the cream comment). Now `ink` uses `--c-ink` like cream/muted.
- Contrast pass (ui-ux-pro-max rule: text >= 4.5:1). Lightened in dark only: `text-primary` (2.9 -> 6.1), `text-danger` (3.4 -> 6.0), `text-success`, teal/emerald text, gray-400 remap. Modal backdrops (`bg-ink/40|50`) pinned to black, since ink goes light in dark. Remapped the pale red/indigo/emerald tints and the Hangman card gradient.
- Fixed a latent bug: bare `bg-amber` / `bg-amber/10` / `border-amber/30` (unread badges, quiz hint boxes) rendered nothing because Tailwind's amber has no DEFAULT. Added `amber.DEFAULT = #F59E0B`, so the sidebar unread badge is visible again (in light mode too).

## 2026-11-15 — Settings: light/dark/system theme + app background
- New `/settings` page (name menu → Settings). Theme stored per user (`users.theme`), background as a path under `public/app-background/<Category>/` (`users.app_background`). Additive migration. Spec: `docs/app-appearance.md`.
- Dark mode via CSS-variable tokens (`cream`/`ink`/`muted`) + a `.dark` remap of `bg-white`/`gray-*`/inputs in `app.css`, so no Blade file needed editing. Inline head script prevents a light-flash.
- Category folders created: Simple, Soft, High Contrast (user wrote "Contract"; assumed typo), Cute Animals. Notification toggles deliberately skipped.
- Tests: `SettingsTest` (13) incl. path-traversal rejection; suite 339 green.
- **Incident:** a Python edit script truncated the uncommitted `layouts/app.blade.php` to 0 bytes (cp1252 write error on a non-ASCII char after opening the file with `'w'`). Rebuilt from `git HEAD` + the compiled Blade cache in `storage/framework/views` (diffed to confirm identical output). Lesson: for scripted edits use `python -X utf8`, build the new string first, write last; commit before scripted edits.

## 2026-11-13 — Attendance added to the welcome tour and /how-to-use
- New **Attendance** card in the tutor, super_admin and student tours (placed to match the sidebar) and for both audiences on `/how-to-use`. Copy is duplicated in both files, as before. Two tests added to `OnboardingTest.php` (suite now 325). Screenshots `tutor-attendance.jpeg` / `student-attendance.jpeg` are optional (the card falls back to an icon). Users who already finished the tour won't see it unless they use Replay tutorial.
- **Follow-up (owner approved the reset):** added data-only migration `2026_11_14_000010_reset_onboarding_for_attendance_step.php` — sets `onboarded_at = NULL` for every user so the tour re-shows once, with the Attendance card. It fires when Attendance deploys (Render migrates on boot). **Deliberately not run against the local DB** (can't confirm from here whether it's the same Neon DB as production; resetting before the new code ships would waste the reset). One test added (`OnboardingTest`, suite now 326). Screenshots for the how-to-use cards should be **portrait phone screenshots** (every existing one is ~1260×2670 or ~960×2048).

## 2026-11-13 — Built Attendance Tracking (Slice 20)
- New **Attendance** tab (tutor + student). Tutor: spinner → centred student picker → calendar / list view; tick days (purple glow), counter pill, **Save**, **Add To Billing** (opens the WhatsApp bill pre-filled with the ticked dates). Days go Attended → **Billed** (amber dot, locked) → **Paid** (green). Student gets a read-only view.
- New `attendances` table (additive migration). "Paid" is derived FIFO from the ledger (`Ledger::paidBillIds`) because bills have no paid flag; saving/deleting a bill links/unlinks its days server-side (`BillController::syncAttendance`).
- Later dates: Save warns (Go back / Yes, save); Add To Billing is blocked with a "recheck the dates" pop-up; the server enforces both.
- First **custom error pages** (403/404/419/429/500/503 + 4xx/5xx), standalone so they work even if the DB is down.
- Tests: `AttendanceTest`, `LedgerPaidBillsTest`, `ErrorPagesTest`, isolation cases in `MultiTutorTest`, linking cases in `BillingTest` (suite 323 green) plus a new Pest-browser E2E suite (`tests/Browser`).
- **Bug found by the E2E test:** the bill form's student dropdown stayed blank when pre-selected (Alpine ran `x-model` before the options rendered), so a pre-filled bill couldn't be saved. Fixed with an `x-init` re-sync.
- Gotchas: a transformed `<aside>` traps `position:fixed` overlays; SQLite needs the `date:Y-m-d` cast so `whereIn` on dates matches; Git Bash can't run `./vendor/bin/pest` (use `php vendor/bin/pest`).

## 2026-11-13 — Planned Attendance Tracking (no code yet)
- Wrote the build spec `docs/attendance-tracking.md` from Nas's wireframes (picker → calendar/list → Save / Add to Billing).
- Surprise while planning: bills have **no "paid" flag** (outstanding is derived), so "turn green when paid" is computed FIFO from the ledger. Also added a "Billed" state so a lesson can't be billed twice.
- The plan also adds the app's first custom error pages (404/419/500…). Until now users saw Laravel's defaults.

## 2026-11-14 — First Playwright browser tests for billing (learning exercise)
- Wrote `tests/Browser/BillingFlowTest.php` (5 real-Chromium tests): confirm pop-up, "Date not allowed" pop-up, edit, delete.
- It caught a real bug: after deleting a bill the list page never showed "Bill deleted." (that page had no status banner). Added the banner to `tutor/billing/index.blade.php`.
- Learned: `php artisan serve` is just a dev web server, not a test; browser tests run with `php vendor/bin/pest tests/Browser` (add `--headed` to watch).

## 2026-11-13 — Billing polish: spinner, a broken form, and no future lesson dates
- After "Yes, save" the billing form now shows the app's standard full-screen spinner with "Saving bill…" (reuses `<x-spinner-overlay />`).
- **Bug I caused:** while adding the spinner I wrote `<x-spinner-overlay />` inside a JS comment in the form's `<script>`. Blade compiled it into HTML inside the script, Alpine crashed, and the form showed no students. Fixed by rewording the comment; logged in `docs/debugging.md`.
- New rule: a lesson date later than today can still be picked, but **Save** now opens a "Date not allowed" pop-up (lists the offending dates, turns them red) instead of the confirm pop-up. The server enforces it too (`before_or_equal:today` in `BillRequest`, so create *and* edit). "Today" comes from the server (Singapore time), not the browser clock.
- Save also runs the browser's required-field check first, so a blank lesson row shows the normal "fill out this field" message instead of the confirm pop-up.
- Heads-up: an old bill that already has a future-dated lesson must have that date fixed before it can be edited and re-saved.
- Tests added in `BillingTest.php` (future date rejected on create and edit, today accepted).
- Docs synced for bill edit/delete + the date rule: `SECURITY.md` §10, `DATABASE.md` (bills row), `TESTING.md`, and the v2.1 architecture doc (§B billing decisions).

## 2026-11-12 — Edit & delete bills in WhatsApp Billing
- Found a gap: once a bill was saved it could never be corrected. Added **Edit** (golden) and **Delete** (red) buttons at the bottom of the bill page.
- Edit reuses one shared form (`resources/views/tutor/billing/partials/form.blade.php`) for create + edit. The student is locked on edit; the bill's original "outstanding before" snapshot is kept so the carried-over balance doesn't double-count the bill itself.
- "Save & generate" is now green and asks "Are you sure? Have you checked that all entries are correct?" before saving (also on edit).
- Delete asks for confirmation. Balances are derived (billed − paid), so deleting a bill fixes the student's balance automatically; lines/charges cascade.
- Tests added in `BillingTest.php` and a tenancy test in `MultiTutorTest.php`. No migration.

## Earlier history (reconstructed from git log)
- **2026-07-01** — Changed the Hangman wheel of fortune.
- **2026-06-27** — Added the Multiplication Rabbit game.
- **2026-06-25** — Added banner notifications and patch notes.
- **2026-06-24** — Spelling Meow: per-letter typing sound, 200+ more Primary 5 words, reflection UX fix; remarks field on Short Answer Sheets; docs refreshed.
- **2026-06-20** — Games page created (empty at first); fixed Enter key submitting forms prematurely.
- **2026-06-19** — Added the Resources tab (OAS / short-answer sheets).
- **2026-06-18** — Tutor can mark homework "done"; student "submit" triggers "awaiting my check".
- **2026-06-10** — Homework URLs auto-detected; short-answer quizzes, half-marks, per-tutor payment details.
- **2026-06-08/09** — SEO fixes, OG image; quiz feedback; Singapore time fix; tutor-viewing-attempts bug fixed.
- **2026-06-05** — Public tutorial page (`/how-to-use`); emails made case-insensitive.
- **2026-06-04** — Slice 11: production deploy scaffolding + hardening; landing hamburger menu; PWA install prompt.
