# Wowlo — Resources (answer sheets)

**Slice 13.** A lightweight **answer-sheet** tool under a new **Resources** sidebar group, built to pair with the Exam Papers feature: when a tutor gives a student a paper exam as homework, the student needs somewhere to record/submit their answers digitally, and the tutor needs to mark it.

> Companion docs: [DATABASE.md](DATABASE.md) (schema + tenancy) · [TESTING.md](TESTING.md) (`AnswerSheetTest`, isolation in `MultiTutorTest`) · [FEATURE_CHANGES.md](FEATURE_CHANGES.md).

---

## 1. What it is

Two sheet **types** (canonical list: `config('wowlo.answer_sheet_types')`):

- **MCQ/OAS Sheet** (`mcq`) — numbered rows showing only options **1–4** (no question text). The student picks one per row. Mimics a paper OAS/bubble sheet.
- **Short Answers Sheet** (`short_answer`) — numbered free-text rows; the student types an answer per row.

Each sheet has a **Title** + **Subject** (from `config('wowlo.subjects')`), a `+ Add question` control (plus a bulk "Add N rows" for long papers), and grows vertically. There is **no stored correct answer** — an OAS just records answers against a paper whose key the tutor holds, so **marking is always manual**.

**Remarks (tutor instructions).** On the **Short Answers Sheet** builder *only* (tutor side), the tutor can add an optional sheet-level **Remarks** (special instructions for the whole sheet) and an optional per-question **Remarks**. These are shown to the student at the top of their sheet / under each question while they fill it in (and stay visible after marking). They are distinct from the marking `feedback`/`tutor_feedback`, which the tutor writes *after* the student submits. MCQ sheets and the student-built builder don't expose remarks.

## 2. Roles & flow

Both tutors/super_admins **and** students can author a sheet:

- **Tutor builds → Save → "which student?" modal (one student) → sent.** The student fills it in and submits; the tutor marks it.
- **Student builds → Submit → goes to their own tutor** (e.g. to record their answers to a paper). The tutor marks it.

Status lifecycle (`config('wowlo.answer_sheet_statuses')`, mirrors a Postgres CHECK):

`sent` (tutor → student, awaiting fill) → `submitted` (awaiting the tutor's manual marking) → `marked`.

Student-built sheets skip `sent` (they're `submitted` on creation). On both send and submit, a `Message` is dropped into the recipient's inbox (+ best-effort web push via `NewMessageNotification`).

## 3. Marking

Self-contained in Resources (not folded into Homework — homework marking is binary, this needs per-question marks). On the marking page the tutor:

- sets **each question's marks** (the "out of" — the tutor decides; student-built sheets default to 1/row until then),
- grades each row **Correct / Partial / Wrong** and awards marks (half-marks allowed),
- writes optional per-question feedback + overall remarks.

`total_marks` and `obtained_marks` are **recomputed server-side** from the per-question values on save (awarded validated `lte` the chosen question total; clamped as a safety net). The student is notified and can then see per-question grades/marks + remarks.

## 4. Data model

Because **one sheet belongs to exactly one student**, the sheet row *is* the assignment + the submission — no separate attempt/assignment tables (unlike quizzes). Two tables (see DATABASE.md §4):

- `answer_sheets` — `author_id`, `tutor_id` (owning tutor, set server-side), `student_id`, `type`, `title`, `subject`, `remarks` (tutor's sheet-level instructions, nullable), `status`, `total_marks`, `obtained_marks`, `feedback`, `submitted_at`, `marked_at`.
- `answer_sheet_questions` — `order`, `num_options` (MCQ; fixed at 4 today, column kept for future flexibility), `marks`, `remarks` (tutor's per-question instructions, nullable), the student's answer (`choice` / `answer_text`), and the tutor's marking (`grade`, `marks_awarded`, `tutor_feedback`).

> `remarks` (both tables) were added additively (nullable) in `2026_06_24_000000_add_remarks_to_answer_sheets` — instructions set at *build* time, separate from the marking `feedback`.

## 5. Tenancy & security

Standard Wowlo rules apply: `tutor_id`/`student_id` are **server-side only** (never request input); the send dropdown and all lists scope to the acting user; every route-bound sheet is ownership-checked and **404s** (not 403) cross-tenant. A tutor can only send to `auth()->user()->students()`; a student can only view/submit their own sheets; marking is blocked until the student has submitted. Isolation tests are in `MultiTutorTest.php`.

## 6. Code map

- Routes: `tutor.resources.*` and `student.resources.*` in `routes/web.php` (prefix `resources`; `{type}` constrained to `mcq|short_answer`; sheet actions under `sheets/{sheet}`).
- Controllers: `App\Http\Controllers\Tutor\ResourceSheetController`, `App\Http\Controllers\Student\ResourceSheetController`.
- Models: `App\Models\AnswerSheet`, `App\Models\AnswerSheetQuestion`.
- Views: `resources/views/{tutor,student}/resources/*.blade.php` (the builder is the `create` view; the marking page is `tutor/resources/mark`).
- Sidebar: the collapsible **Resources** group in `resources/views/layouts/app.blade.php`.

## 7. Possible follow-ups (not built)

- Configurable MCQ option count (5-option OAS) — the `num_options` column already supports it.
- Optionally link a sheet to the Exam Paper it answers (today they're paired by title).
