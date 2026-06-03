# Wowlo — v2.1 Refinement: Resolved Decisions & Architecture

> **Companion to** `Wowlo_Tuition_Management_App_v2.md`. This document does **not** replace the original — it records the decisions made during planning and resolves the open/risky points before any code is written. Where this doc and v2.0 disagree, **this doc wins**.
>
> **Stack (unchanged):** Laravel · Blade · Tailwind CSS · Alpine.js · PostgreSQL (Neon) · PWA
> **Date:** 2026-05-31

---

## 0. Decisions locked in

| # | Decision | Choice |
|---|----------|--------|
| D1 | Build approach | **Refine spec first** (this doc), then scaffold |
| D2 | File storage | **Cloudflare R2** (S3-compatible, private bucket, signed URLs) |
| D3 | Quiz delivery | **Assign to specific students** via a `quiz_assignments` join table |
| D4 | Testing | **Automated tests for authorization + fee calculation**; manual for the rest |

---

## 1. Issue resolutions (the 7 flagged points)

### #1 — File storage: Cloudflare R2 (resolves the Render ephemeral-disk problem)

Render's free filesystem is wiped on every restart/deploy, so local `storage/` is unsafe for uploads. All uploaded files (homework attachments, exam papers) go to a **private** R2 bucket.

- **Driver:** Laravel's built-in S3 driver (`league/flysystem-aws-s3-v3`) pointed at the R2 S3 endpoint.
- **Bucket:** private (not public). Files are never served by a public URL.
- **Download:** a controller action first runs the authorization check (see §2 rules), then returns a **temporary signed URL** (TTL ~5 min) or streams the object. No file is reachable without passing through Laravel auth.
- **DB columns unchanged in name** (`homeworks.attachment_path`, `exam_papers.file_path`) but they now store the **R2 object key**, e.g. `homework/2026/05/uuid.pdf`.
- **Validation:** max size (e.g. 10 MB), allow-list of mime types (`pdf, jpg, png, docx`). Store with a random UUID filename; keep original name in a separate column if display is needed (optional — see schema).

**`config/filesystems.php` disk:**
```php
'r2' => [
    'driver' => 's3',
    'key' => env('R2_ACCESS_KEY_ID'),
    'secret' => env('R2_SECRET_ACCESS_KEY'),
    'region' => 'auto',
    'bucket' => env('R2_BUCKET'),
    'endpoint' => env('R2_ENDPOINT'), // https://<accountid>.r2.cloudflarestorage.com
    'use_path_style_endpoint' => true,
    'visibility' => 'private',
],
```

### #2 — Push notifications: keep, but treat as best-effort

Web push works well on Android + desktop. On iOS it **only** works for PWAs installed to the home screen (iOS 16.4+) and is less reliable. Therefore:

- Push is an **enhancement**, not the source of truth. The dashboard ("upcoming homework", "latest message", unread badges) is always authoritative.
- After a parent installs the PWA we attempt to register a push subscription; if it fails or is unsupported, the app degrades silently.
- Notifications fire on: **new homework assigned**, **new message received** (sent synchronously when the tutor performs the action — no queue/worker needed for MVP).
- Copy on the install prompt should gently encourage home-screen install so iOS users can receive push.

### #3 — Google OAuth account-linking flow (no public registration)

Accounts are created by the tutor only. The Google callback logic:

```
1. Socialite returns { google_id, email, verified_email }.
2. Reject if email not verified by Google.
3. Find user by google_id  → if found: log in. Done.
4. Else find user by email:
     - found AND google_id is null → set google_id (LINK), log in.
     - found AND google_id set to a different id → reject (conflict).
5. Else (no user) → redirect to /login with error:
     "No account found for this Google account. Please ask your tutor to create your account."
   NEVER auto-create an account.
```

This guarantees Google login only works for tutor-provisioned emails, and a student who was created with an email/password can later log in with Google on that same email (their `google_id` gets linked on first Google login).

### #4 — `homework_statuses` table removed (folded into `homeworks`)

Each homework targets exactly one student, so a separate status table was strictly 1:1 with no history. We move status onto the homework row:

- **Drop** the `homework_statuses` table entirely.
- **Add** to `homeworks`: `status` (string: `pending` | `done`, default `pending`) and `completed_at` (timestamp, nullable).
- "Mark as done" sets `status='done'`, `completed_at=now()`. "Not done" reverts.

*(If multi-student assignment is ever needed in a later phase, introduce a proper `homework_student` pivot then — explicitly out of scope for MVP per v2.0 "no batch logic".)*

### #5 — `quiz_assignments` join table (resolves quiz delivery)

A quiz is created by the tutor, then **assigned to specific students**. A student's quiz list is the set of quizzes assigned to them; status is **derived**, not stored:

- No attempt row → **Not Started**
- Attempt exists, `completed_at` null → **In Progress**
- Attempt `completed_at` set → **Completed** (show score)

New table `quiz_assignments`: `id`, `quiz_id` FK, `student_id` FK, `assigned_at` timestamp, **unique(`quiz_id`, `student_id`)**.

### #6 — Fee-due accrual algorithm (makes "outstanding" deterministic)

Outstanding stays **calculated, not stored**. We pin the accrual rule for MVP (whole-period billing, billed at the start of each period, **no pro-ration**):

```
periods_elapsed =
    monthly: count of calendar months from start_date's month
             through the current month, inclusive.
    weekly:  count of ISO weeks from start_date's week
             through the current week, inclusive.

If start_date is in the future → periods_elapsed = 0.

total_due   = periods_elapsed * monthly_fee
total_paid  = SUM(payments.amount_paid) for the student
outstanding = max(total_due - total_paid, ... can be negative if prepaid)
```

- The **current period counts as due** (fee charged at the start of the month/week), matching how tuition is normally billed in advance.
- Negative outstanding (parent prepaid / credit) is allowed and shown as a credit.
- Pro-ration for mid-period joins and pausing billing are **Phase 2**.

### #7 — `enum` columns → `string` + validation/check constraints

PostgreSQL native enums are painful to alter in Laravel migrations. All "enum" fields become `string` columns, enforced by **application validation rules** and optional **DB CHECK constraints**. Affected: `users.role`, `homeworks.status`, `tuition_fees.billing_cycle`, `payments.payment_method`, `quizzes.exam_type`, `quiz_questions.question_type`.

**Plus minor fix:** add `homeworks.start_date` (date, nullable) so the "Start date / Due date" shown on the homework detail page has a real column (if null, UI falls back to `created_at` as the given date).

---

## 2. Refined database schema (deltas only)

Unchanged tables from v2.0 keep their definitions: `messages`, `tuition_fees` (cols unchanged), `payments`, `exam_papers` (file_path now an R2 key), `push_subscriptions`, `quizzes`, `quiz_questions`, `quiz_attempts`, `quiz_answers`. Changes below.

**`homeworks`** (status folded in, start_date added)

| Column | Type | Notes |
|---|---|---|
| id | PK | |
| tutor_id | FK → users.id | creator |
| student_id | FK → users.id | assignee (single) |
| title | string | |
| subject | string | |
| description | text | |
| start_date | date (nullable) | ✦ new — fallback to created_at if null |
| due_date | date | |
| status | string | ✦ new — `pending`\|`done`, default `pending` (CHECK) |
| completed_at | timestamp (nullable) | ✦ new |
| attachment_path | string (nullable) | R2 object key |
| attachment_name | string (nullable) | ✦ optional — original filename for display |
| created_at / updated_at | timestamp | |

**`homework_statuses`** — ❌ **REMOVED**.

**`quiz_assignments`** ✦ **NEW**

| Column | Type | Notes |
|---|---|---|
| id | PK | |
| quiz_id | FK → quizzes.id | |
| student_id | FK → users.id | |
| assigned_at | timestamp | |
| | | **unique(quiz_id, student_id)** |

**`exam_papers` / `homeworks` file columns:** value semantics change to R2 object keys (no schema change beyond the optional `attachment_name`).

**Enum → string** across `users.role`, `tuition_fees.billing_cycle`, `payments.payment_method`, `quizzes.exam_type`, `quiz_questions.question_type` (add CHECK constraints).

**Relationships delta:** drop `User hasMany HomeworkStatus` and `Homework hasOne HomeworkStatus`; add `Quiz hasMany QuizAssignment`, `User(Student) hasMany QuizAssignment`. Homework completion is now an attribute of `Homework`.

---

## 3. Design system (concrete tokens)

Brand direction from v2.0 confirmed (warm violet / cream / amber / Nunito). Concrete, contrast-checked values:

| Role | Hex | Notes |
|---|---|---|
| Primary | `#7C3AED` | Buttons, links, active nav |
| Primary (text-on-light) | `#6D28D9` | Use for violet text on cream for ≥4.5:1 |
| Background | `#FFFBF5` | Warm cream page bg |
| Surface | `#FFFFFF` | Cards |
| Accent | `#F59E0B` | Achievement/badges; `#D97706` for amber text |
| Success | `#16A34A` | Done / paid |
| Error | `#DC2626` | Errors only |
| Text | `#2A2235` | Warm charcoal, body + headings |
| Muted text | `#6B5E73` | Captions (still ≥4.5:1 on cream) |

**Tailwind `theme.extend` colors:**
```js
colors: {
  primary:    { DEFAULT: '#7C3AED', dark: '#6D28D9' },
  cream:      '#FFFBF5',
  accent:     { DEFAULT: '#F59E0B', dark: '#D97706' },
  success:    '#16A34A',
  danger:     '#DC2626',
  ink:        '#2A2235',
  muted:      '#6B5E73',
},
fontFamily: { sans: ['Nunito', 'system-ui', 'sans-serif'] },
```

**Font:** Nunito only (Bold headings / Regular body / Light captions). Body ≥16px on mobile.
**Style:** "Micro-interactions" — subtle Alpine feedback, 150–300ms transitions, **no dark mode in MVP**.
**Layout:** cards dashboard, one-column forms, ≥44px touch targets with ≥8px gaps, fixed sidebar on desktop → collapsible drawer on mobile, tables → stacked cards on mobile, full-width buttons on mobile.
**Hard rules (from UI/UX skill):** SVG icons (Heroicons) not emojis; `cursor-pointer` on all clickables; visible focus rings; status by **text + color** (never color alone); respect `prefers-reduced-motion`; z-index scale (10/20/30/50); `viewport` meta set.

---

## 4. Environment variables (additions/changes to v2.0)

```diff
  APP_NAME=Wowlo
  ...
  GOOGLE_CLIENT_ID / GOOGLE_CLIENT_SECRET / GOOGLE_REDIRECT_URI
  FEE_VIEW_PASSWORD=...
  VAPID_PUBLIC_KEY / VAPID_PRIVATE_KEY / VAPID_SUBJECT

+ FILESYSTEM_DISK=r2
+ R2_ACCESS_KEY_ID=...
+ R2_SECRET_ACCESS_KEY=...
+ R2_BUCKET=wowlo
+ R2_ENDPOINT=https://<accountid>.r2.cloudflarestorage.com
```

---

## 5. Test plan (automated — scope D4)

Laravel feature tests (Pest or PHPUnit). Two areas only; everything else manual per v2.0.

**A. Authorization / data isolation**
- Student GET on any `/tutor/*` route → 403/redirect.
- Student cannot view another student's homework / message / fee / payment / quiz attempt (404/403).
- Tutor can view all students' data.
- Fee page is blocked until the correct `FEE_VIEW_PASSWORD` is entered for the session; wrong password stays locked.
- OAuth linking: (a) known email with null google_id → links + logs in; (b) unknown email → rejected, **no** account created; (c) google_id conflict → rejected.

**B. Fee / billing calculation** *(updated for per-lesson model — see §7)*
- Per-lesson amount = `fee_rate_per_hour × hours` (test varying hours, e.g. 1.5h, 2h).
- Lessons subtotal = sum of all lesson lines.
- Grand total = lessons subtotal + additional charges + outstanding balance.
- `outstanding = total_due − sum(payments)`; multiple payments sum correctly.
- Prepaid case → negative outstanding shown as credit.
- *(Obsolete: the old monthly-accrual tests — `monthly_fee × periods`, future start_date, weekly ISO weeks — no longer apply.)*

---

## 6. Build sequence (vertical slices)

1. **Foundation** — Laravel + Breeze, Tailwind tokens (§3), Nunito, Neon connection, base layout + role-based sidebar, `RoleMiddleware`, R2 disk (§1), CI running the test suite.
2. **Auth** — email/password + Google OAuth with linking (#3), no public registration, role redirect. *(tests: authz/OAuth)*
3. **Students** — tutor CRUD; ≥1 phone-number validation.
4. **Homework** — tutor create/assign + R2 attachment; student list/detail/mark-done (status on homeworks, #4).
5. **Messages** — tutor compose → student inbox; read/unread.
6. **Finance** — fee setup, record payments, dynamic outstanding (#6), fee-password unlock, WhatsApp billing generator. *(tests: fee calc)*
7. **Exam papers** — R2 upload + subject/year filter + authorized download.
8. **Quizzes (MCQ)** — create, **assign via quiz_assignments** (#5), take, auto-mark, results + corrections.
9. **PWA + push** — manifest, service worker, one-time install prompt, web-push subscriptions (best-effort, #2).
10. **Public** — landing (Dribbble-inspired) + privacy policy (PDPA).
11. **Hardening** — finish test suite, mobile QA at 375/768/1024/1440, deploy to Render + UptimeRobot, run post-deploy checklist.

---

## 7. Addenda (post-v2.1 changes)

Changes requested after v2.1 was written. These **supersede** the relevant earlier sections.

### A. Favicon (added 2026-05-31)
- File: `public/images/favicon/wowlo_favicon.ico` (16×16). Separate from the main logo PNG.
- Added to `<head>` of `layouts/app.blade.php`, `layouts/guest.blade.php`, `welcome.blade.php` via `<link rel="icon" type="image/x-icon" href="{{ asset('images/favicon/wowlo_favicon.ico') }}">`.

### B. Billing model → per-lesson by actual hours (supersedes #6 fee accrual)
The flat monthly-fee model is replaced. Tutor bills by **rate/hour × actual hours per lesson**, itemised.

- **`tuition_fees`**: replace `monthly_fee` with **`fee_rate_per_hour`** (decimal). Remove `billing_cycle` and `start_date`. Keep `currency`, `remarks`.
- **WhatsApp Billing page**: tutor picks student + month, then adds **itemised lesson lines (date + actual hours)**; each line = `fee_rate_per_hour × hours` (hours vary per lesson). Plus **optional, repeatable additional charges** (description + amount) and an **outstanding balance** (tutor-entered in MVP).
- **Calculation:**
  ```
  per_lesson_amount = fee_rate_per_hour × actual_hours
  lessons_subtotal  = Σ per_lesson_amount
  grand_total       = lessons_subtotal + additional_charges + outstanding_balance
  ```
- **Message template** lists: student, month, each dated lesson line (hours × rate = amount), lessons subtotal, additional charges (if any), outstanding (if any), **grand total**, PayNow number.
- **The v2.1 §1 #6 monthly-accrual formula is obsolete.** "Total Tuition Fee Due" is now `Σ(rate × hours) + additional charges`.
- **RESOLVED (Finance slice): PERSIST bills.** Implemented 5 tables — `tuition_fees` (one `fee_rate_per_hour` per student), `payments`, `bills`, `bill_lines`, `bill_charges`. Outstanding is **never stored**: `App\Services\Ledger` derives it as `Σ(bills.charges_total) − Σ(payments.amount_paid)` (negative = credit/prepaid). Each bill snapshots `outstanding_before` (ledger value at creation) and `grand_total = charges_total + outstanding_before`. `App\Services\BillMessage` is the single source of truth for the WhatsApp text; the create page mirrors it live in Alpine. Server always recomputes the math (never trusts client). Fee page gated by `fee.unlocked` middleware + session flag (password = `config('wowlo.fee_view_password')`, constant-time compare). So "outstanding" is now **auto-derived from real billing history** — when a parent owes from a prior month, it's a positive value that adds to the next grand total.

### C. Exam Papers → 3-level hierarchy (supersedes #1/§6.7 "subject/year filter only")
The original `exam_papers` schema (v2.0 §7: tutor_id, title, subject, year, file_path, remarks) gains a **`level`** column and an **`original_filename`** column, and the UI is organised as a 3-level tree instead of a flat filtered list.

- **Schema add:** `exam_papers.level` (string, e.g. "Primary 4"; nullable in DB but required on upload), `exam_papers.original_filename` (string — the display name; `file_path` stays the R2 object key).
- **Organisation: Level → Subject → Year.** Both tutor and student pages render nested collapsible accordions (Level contains Subjects contains Year-grouped papers). Only levels/subjects that actually have papers appear. `ExamPaper::groupForDisplay()` builds the nested structure in canonical config order.
- **Canonical lists in `config/wowlo.php`:** `levels` (Primary 1–6, Secondary 1–5) and `subjects` (27 SG subjects incl. G1–G3 streams, Combined/Pure sciences, IP, etc.). These drive the upload dropdowns, `Rule::in` validation, and grouping order — single source of truth.
- **Student filters:** Level + Subject + Year dropdowns (each lists only values that have papers). All students download all papers (`Storage::disk('r2')->download`); tutor uploads/deletes.
- **Dependency:** `@alpinejs/collapse` installed and registered in `resources/js/app.js` for the accordion animation.

### D. Quizzes — exam_type expanded, per-question diagrams, persisted corrections (supersedes v2.0 §9–§12)
- **`quizzes.exam_type` expanded** from the original 4 (`WA1|MidYear|WA2|EndYear`) to **14**, in `config('wowlo.exam_types')`: the original 4 plus `Quiz`, `PeriodicAssessment`, `TopicEvaluation`, `PSLE`, `PrelimPSLE`, `NLevel`, `PrelimNLevel`, `OLevel`, `PrelimOLevel`, `CompetitionPrep`. Enforced by a Postgres CHECK constraint that is **updated via migration** whenever the list changes (keep config + CHECK in sync).
- **`quiz_questions` gains `image_path` + `image_name`** — an **optional per-question diagram/attachment** (PDF/JPG/PNG/GIF/WebP, max 10 MB) stored in the private R2 bucket under `quiz-questions/`. Served only through authorised streaming routes (tutor: any; student: only for a quiz assigned to them). Deleting a quiz also deletes its R2 attachments.
- **`quiz_answers.correction`** (text) — the student's written correction for a wrong answer is **persisted** (the original spec described corrections as on-screen only). Editable any time from the results page.
- **`quiz_attempts`** has `unique(quiz_id, student_id)` — **one attempt per student per quiz** in MVP; re-submit is blocked once `completed_at` is set (taking it again redirects to results).
- **Status is derived** (per #5): no attempt = Not Started; `completed_at` null = In Progress; set = Completed.
- **Auto-marking** happens server-side in a transaction on submit: each `student_answer` is compared to `correct_answer`; blanks count as wrong; `obtained_marks = Σ` of correct questions' marks.
- **Tutor create form** uses Alpine dynamic question rows with explicit JS field validation, a confirm-before-save modal, and a processing spinner. (Note: the modal's confirm button is a native `type=submit` — calling `form.submit()` from JS hung silently.)

### E. PWA + Web Push (Slice 9 as-built)
- **Installable PWA:** `public/manifest.json` (standalone, brand colors, icons), `public/sw.js` (minimal — install/activate/fetch passthrough, **no offline cache** in MVP), registered in `resources/js/app.js`. `<link rel="manifest">`, `theme-color`, and `apple-touch-icon` in both `layouts/app` and `layouts/guest`. App icons (192/512/512-maskable) generated from `wowlo_logo.png` via `scripts/make-pwa-icons.php` into `public/images/pwa/`.
- **One-time install prompt:** `partials/pwa-install.blade.php` — Alpine banner that captures `beforeinstallprompt`; dismissal stored in `localStorage` (`wowlo_pwa_prompt`) so it shows once; hidden if already installed (standalone).
- **Web push (best-effort, #2):** `laravel-notification-channels/webpush`. `push_subscriptions` table (package migration); `User` uses `HasPushSubscriptions`. VAPID keys in `.env` (generated with `php artisan webpush:vapid`). Subscribe/unsubscribe via `PushSubscriptionController` (`POST`/`DELETE /push/subscribe`, in the `auth` group). Student dashboard shows an "Enable notifications" card (`partials/push-enable.blade.php`) — requests permission, subscribes via `PushManager` using the **public** VAPID key (exposed in a meta tag — safe), POSTs the subscription. VAPID public key + endpoints passed to JS via `<meta>` tags.
- **Notifications:** `NewHomeworkNotification` + `NewMessageNotification`, deliver only via `WebPushChannel`, fired **synchronously** from `Tutor\HomeworkController@store` (→ assigned student) and `Tutor\MessageController@store` (→ receiver), each wrapped in `try/catch` + `report()` so a push failure never breaks the request. **Push targets students only** (homework/message flow is tutor→student).
- **Windows/Herd note:** `php artisan webpush:vapid` (and real push *sending* locally) needs `OPENSSL_CONF` pointing at Herd's `openssl.cnf` (`...\bin\php84\extras\ssl\openssl.cnf`) or EC key creation fails. Irrelevant on the Linux deploy. Tests use `Notification::fake()` so they never sign/send.

### F. Homework form hardening (2026-06-02)
- **All fields required** except the attachment: `title`, `subject`, `description`, `student_id`, **`start_date` (now required, was nullable)**, `due_date`.
- **Subject is now a dropdown** from `config('wowlo.subjects')` (validated with `Rule::in`), matching Exam Papers/Quizzes. The `_form` partial keeps an existing non-canonical subject selectable so editing never silently changes it.
- **Two-stage submit spinner** on create ("Connecting to server…" → "Saving to database…"), shown via an `@submit` handler that only fires after native validation passes.

### G. Build progress
- ✅ **Slices 1–10 complete**: Foundation, App Shell, Auth, Students, Homework (+R2), Messages, Finance, Exam Papers, Quizzes, **PWA + Push**, public landing page + PDPA privacy policy.
- ✅ **Slice 10.5 — Multi-tutor tenancy (2026-06-03)** — see §H below.
- **Test count: 119** (Pest feature tests; in-memory SQLite — never touches Neon). Includes `tests/Feature/MultiTutorTest.php` (cross-tutor isolation, admin tutor mgmt, exam-paper approval).
- **Next: Slice 11 (hardening + deploy to Render + UptimeRobot).**

### H. Multi-tutor tenancy (2026-06-03) — supersedes the single-tutor assumption

The app was built assuming one tutor (`auth()` *was* the tutor). It is now multi-tutor. **Decisions locked in:**

| # | Decision | Choice |
|---|----------|--------|
| MT1 | Student ownership | **1 student → 1 tutor** (the creator). No student-shared-across-tutors model. |
| MT2 | Exam papers | **Shared, moderated library.** Approved papers are global (all tutors + students). A non-admin tutor's upload is `pending`; the super_admin approves (→ live + a Message to the uploader) or rejects (→ deleted + a Message). The super_admin's own uploads auto-approve. |
| MT3 | Super_admin | The owner **also teaches** their own roster, plus manages tutor accounts and approves papers. Does NOT browse other tutors' students' data (privacy/PDPA). |
| MT4 | Tutor creation | **Super_admin provisions tutors** at `/admin/tutors`. No public sign-up yet. Deleting a tutor with students is **blocked** (`restrictOnDelete` + a friendly app error). |
| MT5 | Public tutor sign-up | **Deferred to Phase 2.** Purely additive — a self-registered tutor is structurally identical (`role=tutor, tutor_id=null`), so existing tutors lose nothing when it ships. |

**Architecture.** `users.tutor_id` (nullable FK → users) is the backbone: a student's owning tutor; NULL for tutor/super_admin. Content tables already carried `tutor_id` (homeworks, quizzes, exam_papers, bills); fees/payments/bills/quiz_assignments resolve ownership through the student. The real work was **enforcement**, not schema: every tutor list scopes to `auth()->id()` and every route-bound model is ownership-checked (404). `RoleMiddleware` now accepts a list (`role:tutor,super_admin`). Roles enforced by a widened Postgres CHECK (`student|tutor|super_admin`). `exam_papers` gained `status` (`pending|approved`, default `approved` to backfill), `approved_by`, `approved_at`. Tutors got a received-message **Inbox** (`tutor.messages.inbox`) so approval notices land somewhere. New migrations: `2026_06_03_000000/000010/000020`. Pre-existing orphaned students were backfilled to the super_admin.

---

*Wowlo v2.1 — Refinement & Architecture Decisions. Supersedes v2.0 where they conflict.*
