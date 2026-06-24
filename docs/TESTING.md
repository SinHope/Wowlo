# Wowlo — Testing Guide

**Purpose:** what tests exist, how to run them, what to test manually, and what must be green before any production deploy.

> Companion docs: [SECURITY.md](SECURITY.md) · [DATABASE.md](DATABASE.md) · [FEATURE_CHANGES.md](FEATURE_CHANGES.md)

---

## 1. Test suite overview

| Item | Detail |
|---|---|
| Framework | **Pest** (wraps PHPUnit) |
| Total tests | **189** passing (as of Slice 14 — Spelling Meow) |
| Database | **In-memory SQLite** — never touches Neon in tests |
| Run command | `php artisan test` |
| Parallel | `php artisan test --parallel` (faster, safe) |

> SQLite is intentionally different from production Postgres. Postgres-specific SQL (CHECK constraints, `CREATE INDEX CONCURRENTLY`) must be guarded with `if (DB::getDriverName() === 'pgsql')` — see DATABASE.md §2 rule 6.

---

## 2. How to run tests

```bash
# Run all tests
php artisan test

# Run in parallel (faster)
php artisan test --parallel

# Run a specific test file
php artisan test tests/Feature/MultiTutorTest.php

# Run a specific test by name
php artisan test --filter test_student_cannot_see_another_students_homework

# Run with coverage report (requires Xdebug or PCOV)
php artisan test --coverage
```

> **Rule:** `php artisan test` must be **green** before any commit leaves your machine. Never push red tests.

---

## 3. Test files (what actually exists)

Real files under `tests/Feature/` and the area each owns. There is **no** standalone `FileUploadTest`/`RoleMiddlewareTest`/`AuthTest` — upload validation and role enforcement are exercised *inside* the feature tests and `MultiTutorTest`.

### A. Authorisation & Tenant Isolation — `MultiTutorTest.php` ⭐ most critical
Data must never leak between tutors or students. Covers cross-tutor isolation (a tutor can't see another tutor's students/homework/messages/finance/bills/quizzes/answer sheets), IDOR (route-bound records the user doesn't own return **404, not 403**), role enforcement (`tutor_id` set server-side, students blocked from tutor routes), and admin-only tutor management + exam-paper approval.

> Add a test here for **every new cross-tenant surface** (new table, route, or controller action). This is the non-negotiable file.

### B. Authentication — `tests/Feature/Auth/*` (Breeze defaults)
`AuthenticationTest`, `RegistrationTest`, `PasswordResetTest`, `PasswordConfirmationTest`, `PasswordUpdateTest`, `EmailVerificationTest`. Cover login/logout, password reset + update, and that **public registration is disabled** (the Breeze register routes are removed — see `routes/auth.php`). Google OAuth linking/rejection logic lives with the auth flow.

### C. Fee & Billing — `BillingTest.php` + `FinanceTest.php`
Money is the highest-risk area — these must never be skipped. `FinanceTest` covers fee setup, recording payments, and outstanding-balance math; `BillingTest` covers bill generation and that totals are **recomputed server-side** and **snapshotted** at generation time (changing a rate later doesn't alter old bills).

### D. Homework & Messages — `HomeworkTest.php`, `MessageTest.php`
Create/assign homework (with attachment), student mark-as-done, the messages a tutor sends, inbox/read state, and that a student only sees their own.

### E. Quizzes — `QuizTest.php` (tutor) + `StudentQuizTest.php` (student)
Tutor quiz creation/assignment/results, and the student side: taking an assigned quiz, **server-side auto-marking** of MCQs (`obtained_marks` recomputed, never client-trusted), one-attempt rule, and that a student can't open an unassigned quiz.

### F. Exam Papers — `ExamPaperTest.php`
Shared moderated library: upload goes to R2 and is **`pending`** until the super_admin approves; approved papers are visible/downloadable to students; pending papers are not; downloads require auth.

### F2. Resources (answer sheets) — `AnswerSheetTest.php`
The build → send/submit → mark flow for OAS + short-answer sheets: a tutor builds + sends to one of their own students (and can't send to another tutor's), a student fills + submits a sent sheet (MCQ choice range validated) or builds + submits their own (which resolves to their tutor), and the tutor marks it — **the tutor decides each question's marks and the totals are recomputed server-side** (awarded `lte` the chosen total). Also covers the tutor **remarks** (sheet-level + per-question) persisting on a short-answer sheet, and that the remarks fields render on the short-answer create page but not MCQ. Cross-tenant isolation for sheets lives in `MultiTutorTest.php`.

### F3. Spelling Meow (Games) — `SpellingGameTest.php`
The first Games-tab game. Covers **server-side marking** (responses graded against `config/spelling-words.php`; the correct spelling is never sent to the browser during play), case-insensitivity, the virtual **Mixed Primary** level drawing words from across Primary 1–6, the **mandatory reflection** (rejects blank/whitespace-only, accepts any non-empty text — *no minimum*), the **blocking reflection gate** showing until a reflection exists, the tutor **feedback** loop, and student/tutor **tenant isolation** (a student/tutor only touches their own rounds; cross-tenant → 404).

### G. Push Notifications — `PushNotificationTest.php`
Uses `Notification::fake()`. New-homework / new-message notifications fire to the right user; a push failure is caught + logged and **never** 500s the request; a user can only manage their own subscription.

### H. Student & Profile — `StudentManagementTest.php`, `ProfileTest.php`, `OnboardingTest.php`
Tutor adding/editing their own students (tenancy stamped server-side), profile/password update, and the onboarding flag (`onboarded_at`) lifecycle.

> File-upload validation (per-type `mimes` + size — see SECURITY.md §5) and role-middleware behaviour (`abort(403)` for the wrong role; 404 for un-owned records) are asserted within the feature tests above and `MultiTutorTest`, not in dedicated files.

---

## 4. Manual testing checklist

Run this manually before every production deploy. Automated tests catch logic; this catches UI and integration issues.

### Auth
- [ ] Login with email + password (all 3 roles)
- [ ] Login with Google OAuth (student/parent account)
- [ ] Wrong password shows error, not a crash
- [ ] Forgot password sends email and link works
- [ ] Logout clears session — back button doesn't show protected page

### Role isolation (do this with two separate browser windows)
- [ ] Student A cannot see Student B's homework by changing the URL ID
- [ ] Tutor A cannot see Tutor B's students
- [ ] Student cannot reach any `/tutor/*` URL

### Homework
- [ ] Tutor creates homework with attachment → appears in student's list
- [ ] Student marks as done → status updates
- [ ] Attachment downloads correctly (not a broken link)
- [ ] Attachment is served from R2 (check URL is a signed Cloudflare URL)

### Messages
- [ ] Tutor sends message → appears in student's inbox
- [ ] Unread badge shows on student dashboard
- [ ] Message marked as read after opening
- [ ] Student cannot reply (no reply button in MVP)

### Finance
- [ ] Fee section hidden for student by default
- [ ] Correct parent password unlocks fee section
- [ ] Wrong password is rejected
- [ ] Fee section re-locks after session ends (open new browser tab)
- [ ] WhatsApp billing message generates correctly with correct totals
- [ ] Outstanding balance calculates correctly after partial payment

### Quizzes
- [ ] Tutor creates MCQ quiz → assigned student sees it
- [ ] Student takes quiz → auto-marked on submit
- [ ] Results page shows score and correct/wrong per question
- [ ] Student cannot re-submit completed quiz
- [ ] Student cannot access unassigned quiz
- [ ] **Enter key does NOT submit early** — pressing Enter while typing in the quiz builder, the student "take quiz" page, or the grading page must not save/submit (only the Save/Submit button + confirm modal does); Enter inside a textarea still adds a newline

### Exam papers
- [ ] Tutor uploads paper → status shows pending
- [ ] Super admin approves → paper appears for students
- [ ] Filter by level/subject/year works
- [ ] Download works and file is correct

### Resources (answer sheets)
- [ ] Sidebar **Resources** expands to MCQ/OAS Sheet + Short Answers Sheet (both roles)
- [ ] Tutor builds a sheet → **Save** prompts "which student?" → sends; student sees it under "To do"
- [ ] Student fills + submits a sent sheet → tutor's list shows "To mark"; student sees "Awaiting marking"
- [ ] Student builds + submits their own sheet → lands with their tutor to mark
- [ ] Tutor marks: sets each question's marks + awarded, running total/percentage updates live; awarded can't exceed the chosen total
- [ ] After marking, student sees per-question grade/marks + feedback; both parties get an inbox notice
- [ ] **Short Answers Sheet remarks (tutor only):** a **Remarks** field under Subject + a **Remarks** box per question on the *tutor* create page (not MCQ, not the student builder); the student sees them at the top of their sheet and under each question
- [ ] **Enter key does NOT submit early** — pressing Enter while typing in any sheet builder or the marking page must not save/send (only the Save/Submit button + modal does); Enter inside a textarea still adds a newline

### Games — Spelling Meow
- [ ] Sidebar **Games → Spelling Meow**; the game's own **Play / My Progress** tabs switch inside it
- [ ] Loading cat spinner → **Select Your Level** → Primary/Secondary → levels; Secondary 3–5 show "Building in progress.."
- [ ] **Mixed Primary (Primary 1 - 6)** plays a 30-word round from across P1–P6
- [ ] **Timer** (Primary + Mixed only): Infinite vs Set a timer (1/3/5/7/10 / Other minutes); countdown shows; at 15s left the siren banner flashes; at 0 it auto-submits and marks
- [ ] Per-letter boxes auto-advance/backspace; **Enter** advances; last button reads **Done With Spelling**; "review again?" returns to Q1 with answers kept
- [ ] Results show score % + per-word right/wrong with the correct spelling
- [ ] **Reflection gate:** the results page is fully blocked (can't click the sidebar/anything) until a reflection is written; Save is disabled until non-empty; any single character is accepted (no minimum)
- [ ] Tutor sees their students' rounds under **Games → Spelling Meow**, opens one, leaves **Feedback**; the student is notified and sees it on their results page

### PWA & Onboarding
- [ ] **"Install app"** button shows on the landing + login pages (only when `PWA_PROMOTE_INSTALL=true`); native prompt on Android/desktop Chrome, "Add to Home Screen" steps on iOS
- [ ] Install toast appears once and stays dismissed (also gated by `PWA_PROMOTE_INSTALL`)
- [ ] Onboarding tour auto-opens on the dashboard for a fresh account (`onboarded_at` NULL); Skip/Finish stops it returning; **Replay tutorial** in the name menu re-opens it
- [ ] Onboarding shows the right cards per role (super_admin sees "Tutors"; student sees the student set)
- [ ] Enabling push notifications works (on Android Chrome); new homework triggers a push

### Mobile
- [ ] Test on real phone browser (375px width)
- [ ] Sidebar collapses to drawer on mobile
- [ ] All buttons are tappable (min 44px)
- [ ] Tables stack correctly on small screens
- [ ] Forms are usable one-handed

---

## 5. What "green" means before deploying

Before any production deploy, ALL of the following must be true:

- [ ] `php artisan test` — 0 failures, 0 errors
- [ ] `MultiTutorTest.php` specifically — all passing (data isolation is non-negotiable)
- [ ] `BillingTest.php` — all passing (money is non-negotiable)
- [ ] Manual smoke test across all 3 roles completed locally
- [ ] No `dd()`, `dump()`, `var_dump()`, or `console.log` left in code
- [ ] `APP_DEBUG=false` confirmed in Render env

---

## 6. Adding tests for new features

Every new feature needs tests before it ships. Follow this pattern:

Use the global Pest helpers from `tests/Pest.php` — `tutor()`, `superAdmin()`, and `student(['tutor_id' => $tutor->id])` (a student must be placed on a tutor's roster for any tenant action). The `UserFactory` has no `tutor()`/`student()` *states* — use the helpers.

```php
// tests/Feature/NewFeatureTest.php
use function Pest\Laravel\actingAs;

it('tutor can do the new thing', function () {
    $tutor = tutor();
    actingAs($tutor)->post('/tutor/new-thing', [/* ... */])->assertRedirect();
    // ... assert DB state
});

it('a student cannot reach the tutor route', function () {
    actingAs(student())->get('/tutor/new-thing')->assertForbidden(); // RoleMiddleware abort(403)
});

it('a tutor cannot see another tutor\'s thing', function () {
    $owner = tutor();
    $thing = NewThing::factory()->create(['tutor_id' => $owner->id]);
    actingAs(tutor())->get("/tutor/new-thing/{$thing->id}")->assertNotFound(); // 404, not 403 — IDs don't leak
});
```

> **Minimum 3 tests per new feature:** happy path, unauthorised access, cross-tenant isolation. Cross-tenant ones belong in `MultiTutorTest.php`.

---

*Wowlo — Testing Guide. Green tests are not optional.*
