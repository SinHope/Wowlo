# Wowlo — Short-Answer Quizzes + Manual Grading (Spec)

**Status:** ✅ **Built (Slice 12, 2026-06-09).** Implemented to this spec. Tests: `tests/Feature/ShortAnswerQuizTest.php` + isolation cases in `tests/Feature/MultiTutorTest.php`.

**As-built note (one deviation):** the spec proposed a new `quiz_attempts.tutor_remarks` column for overall remarks. Since the spec was written, a `quiz_attempts.feedback` column + a "send feedback" flow (that messages the student) were added. We **reused `feedback`** as the overall-remarks field instead of adding `tutor_remarks` — same concept, less schema. Grading sets `feedback` and notifies the student via the shared `notifyStudentFeedback()` path. Everything else below matches the build.

This is the build blueprint.

> Companion docs: [FEATURE_CHANGES.md](FEATURE_CHANGES.md) (additive change pattern — short answer was the worked example) · [DATABASE.md](DATABASE.md) (migration rules) · [SECURITY.md](SECURITY.md) §5 (upload rules) · [TESTING.md](TESTING.md).

---

## 1. What we're building

Today quizzes are MCQ-only and auto-marked. This adds:

- **Question types:** at "+ Create quiz" the tutor picks **MCQ / Short Answer / Both**. A quiz can mix both.
- **Short-answer questions:** free-text answer, **no auto-marking** — the tutor marks each **Correct / Partially correct / Wrong**.
- **Manual marking workflow:** a new attempt state — *submitted → awaiting tutor marking → graded*.
- **Per-question tutor feedback** (MCQ *and* short answer): a text note on how to solve, with an **optional image attachment** (.jpeg/.png → R2) so the tutor can explain by drawing.
- **Per-attempt overall remarks:** a general feedback box on the student's whole submission — so parents see progress at a glance.
- **Percentage** shown alongside raw marks (e.g. `8/10 · 80%`) in **both** student and tutor views.
- **Tutor can see each student's submitted answers** (the grading screen).
- **Corrections** (existing feature) extended: any **partial or wrong** answer requires the student to write a correction.

## 2. Confirmed decisions (2026-06-04)

| # | Decision | Choice |
|---|---|---|
| 1 | Partial-credit marks | **Tutor types the exact marks** awarded per question (e.g. 2 of 3). Not a fixed half. |
| 2 | Results visibility for quizzes with short answers | **Only after the tutor finishes marking.** Student sees "Submitted — awaiting your tutor's marking", then the full result in one reveal. |
| 3 | After student writes a correction | **Just recorded — no re-grade.** Marks don't change; matches today's corrections behaviour. |
| 4 | Sequencing | **Deploy first, build this next slice.** |

## 3. Already scaffolded (don't rebuild)

- `quiz_questions.question_type` already exists (`mcq | short_answer`, CHECK constraint).
- `quiz_questions.image_path` / `image_name` — per-question **diagram** image (set at create), with an authorized stream route. (Separate from the new *feedback* image.)
- `quiz_answers.correction` — student's written correction (currently for wrong MCQs); extend to partial/wrong short answers.
- One-attempt-per-student-per-quiz rule stays.

## 4. Schema changes (all additive / widening — safe)

**`quiz_questions`**
- `correct_answer` → make **nullable** (short answers have no correct option; MCQ unchanged).

**`quiz_answers`**
- `student_answer` → **text**, nullable (longer free-text answers; was varchar A|B|C|D).
- add `grade` — string, nullable: `correct | partial | wrong` (pgsql CHECK). Tutor-set for short answers; can mirror MCQ auto-result for uniform "needs correction" logic.
- add `tutor_feedback` — text, nullable.
- add `tutor_feedback_image_path` / `tutor_feedback_image_name` — string, nullable (R2 key + display name).

**`quiz_attempts`**
- add `tutor_remarks` — text, nullable (overall feedback for the student/parent).
- add `graded_at` — timestamp, nullable. **NULL = awaiting marking; set = fully graded.**

> All of the above are new nullable columns or column-widenings — no data loss, existing quizzes keep working. The `->change()` widenings run on both Postgres and SQLite (Laravel 13, no doctrine/dbal needed). Postgres CHECK for `grade` guarded by `if (DB::getDriverName()==='pgsql')`.
>
> Quiz "type" (MCQ/Short/Both) is **derived** from its questions — no `quizzes.type` column needed (a quiz "needs marking" if any question is `short_answer`).

## 5. Workflow

**Create (tutor):** pick MCQ / Short Answer / Both → each question has a type; the form shows options + correct answer for MCQ, just question + marks for short answer. (Per-question diagram image as today.)

**Submit (student):**
- MCQ → auto-marked as now (`is_correct`, `marks_awarded`, `grade`).
- Short answer → store `student_answer`, `grade=null`, `marks_awarded=0` (pending).
- `total_marks` = Σ all question marks. `obtained_marks` = Σ MCQ awarded so far.
- If the quiz has **no** short answers → `graded_at = now()` (done, as today). If it **has** short answers → `graded_at = null` (**awaiting marking**).

**Grade (tutor)** — new "Submissions" view per quiz (assigned students · status: Not attempted / Awaiting marking / Graded · score %), launching a **grading page** per attempt:
- shows each question + the student's submitted answer;
- for each short answer: pick `correct/partial/wrong` + type the **marks awarded** (validated `0..question.marks`);
- for any question: optional `tutor_feedback` text + optional **feedback image** (.jpg/.png → R2);
- an overall **remarks** box;
- save → recompute `obtained_marks = Σ marks_awarded`, set `graded_at = now()`.

**Result (student):**
- While `graded_at` null and the quiz has short answers → "Submitted — awaiting your tutor's marking" (no marks shown).
- Once graded → **marks + %**, per-question tutor feedback (+ image), overall remarks, and a **corrections** box for every partial/wrong answer (recorded, no re-grade).

## 6. Percentage

`percentage = total_marks > 0 ? round(obtained_marks / total_marks * 100) : 0`. Show alongside raw marks in: student quiz list, student result, tutor submissions list, tutor grading page. Add as a `QuizAttempt::percentage()` helper.

## 7. Validation (`QuizRequest` rework)

- add `questions.*.question_type` ∈ `mcq,short_answer`.
- conditional: `required_if:questions.*.question_type,mcq` for `option_a..d` + `correct_answer`; short answers need only `question_text` + `marks`.
- grading request: `grade` ∈ `correct,partial,wrong`; `marks_awarded` integer `0..max`; `tutor_feedback` text; feedback image `mimes:jpg,jpeg,png max:10240`.

## 8. Routes (new)

- `GET tutor/quizzes/{quiz}/submissions` (or fold into `show`)
- `GET tutor/quizzes/{quiz}/attempts/{attempt}/grade`
- `POST tutor/quizzes/{quiz}/attempts/{attempt}/grade`
- `GET tutor|student .../answers/{answer}/feedback-image` — authorized stream (owning tutor, or the student who owns the attempt)
- reuse student corrections route; extend to short answers.

All tutor routes ownership-checked (tutor owns the quiz; 404 on miss). Feedback image streamed via the controller (no public URL), like every other file.

## 9. Tests required (TESTING.md)

- MCQ-only quiz still auto-grades instantly (**regression**).
- Short-answer quiz: submit → `graded_at` null; student result shows "awaiting marking", no marks leaked.
- Tutor grades: partial marks recompute `obtained_marks`; `grade` stored; `graded_at` set; student then sees the result + %.
- Corrections required for partial/wrong; recorded without changing marks.
- Percentage math (incl. total = 0 guard).
- **Isolation** (`MultiTutorTest`): a tutor can't grade another tutor's quiz/attempt; a student can't grade or view another student's submission or feedback image.

## 10. Out of scope (later)

AI-assisted short-answer suggestions (`quiz_answers.ai_feedback` already reserved), re-grade-after-correction loop (decided against for now), multiple attempts.
