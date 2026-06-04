# Wowlo — Feature Changes & Updates Playbook

**Purpose:** how to add or change features *after* Wowlo is live, without breaking anyone — tutors, students, parents, or the super_admin. This is the doc to read when you think "I want to add X to quizzes / change how Y works."

> Companion docs: [DATABASE.md](DATABASE.md) (the schema rules) · [SCALABILITY.md](SCALABILITY.md) (keeping it fast).

---

## 1. The one idea behind everything here

> **Add, don't break.** New things are safe. Changing or removing existing things is where users get hurt.

Once real users exist, you can't rebuild the database — you evolve it with migrations, one safe step at a time. Done right, existing users never notice a thing while new features simply appear.

---

## 2. Three kinds of change, ranked by risk

| Risk | Kind of change | Examples |
|---|---|---|
| 🟢 None | **Display / computed only** — no schema change | Show a `%` next to a quiz score (it's `obtained ÷ total × 100` from data you already store). A new chart. Reworded copy. |
| 🟡 Low | **Additive** — new nullable column / new table / extend a CHECK list | A "Short Answer" question type. A new optional field on homework. A new report table. |
| 🔴 High | **Destructive** — rename/drop/retype an existing column with data | Renaming `obtained_marks`. Splitting `name` into first/last. Changing a column's type. |

🟢 and 🟡 are everyday safe work. 🔴 is rare and must use **expand-contract** (§4). If you can solve a problem additively, always do — don't reach for 🔴 out of tidiness.

---

## 3. Worked example — adding a "Short Answer Section" to quizzes (🟡 additive)

This is the real deferred feature, walked through so the pattern is concrete. **It now has a full build spec with locked decisions → [short-answer-quizzes.md](short-answer-quizzes.md)** (next slice after deploy); the below stays as the illustrative pattern.

The schema already anticipates it: `quiz_questions.question_type` exists and is currently always `'mcq'`. Adding short-answer is what that column was *for*.

1. **Migration (additive):**
   - Extend the `question_type` CHECK constraint to allow `'short_answer'` (and update the canonical list in `config/wowlo.php` — see [DATABASE.md](DATABASE.md) §2 rule 5).
   - Add **nullable** columns for the new type, e.g. `model_answer` (text, nullable). MCQ columns (`option_a..d`, `correct_option`) stay nullable for short-answer rows.
   - Existing rows: `question_type` stays `'mcq'`, new column is `NULL`. **Nothing breaks.**
2. **Scoring:** short-answer can't be auto-graded like MCQ. Decide: tutor marks it manually (writes `marks_awarded` on the `quiz_answer`), or it's ungraded practice. Keep MCQ scoring untouched.
3. **Apply new behaviour to NEW quizzes only.** A student mid-attempt on an existing MCQ quiz must not see scoring change under them. New question types appear on quizzes created after the change.
4. **Tests:** add cases for the new type; the existing MCQ tests must still pass unchanged (proof you didn't break the old path).
5. Ship via the deploy flow in §6.

Same shape applies to most "add a feature" requests: extend an enum if needed, add nullable columns, leave old rows valid, gate new behaviour to new records.

---

## 4. Changing existing structure — expand → migrate → contract (🔴)

When you genuinely must change an existing column (rename/retype/split), never do it in one step. Spread it across **separate deploys** so old and new code are always compatible with the schema in between:

1. **Expand** — add the new column alongside the old (nullable). Deploy.
2. **Backfill + dual-write** — copy/transform existing data into the new column; change code to *write both* and *read the new one*. Deploy. Verify.
3. **Contract** — once nothing reads the old column, drop it in a later migration. Deploy.

There is never a moment where running code meets a column that isn't there. This is how you rename or restructure without downtime or data loss.

---

## 5. Who can be affected, and how to protect them

| Group | Risk during a change | Protection |
|---|---|---|
| **Students/parents** mid-action | An in-progress quiz attempt or a half-loaded page meeting changed logic | Gate new behaviour to new records; deploy at low-traffic hours; additive-only. |
| **Tutors** | A list query breaking, or losing access to existing data | Ownership scoping unchanged; run the full test suite (incl. `MultiTutorTest`) before deploy. |
| **Super_admin** | Moderation/admin flows assuming old shape | Cover admin paths in tests; additive changes keep old flows working. |
| **Everyone** | Data loss | Backup before every prod migration ([DATABASE.md](DATABASE.md) §6); additive-only; expand-contract for the rest. |

---

## 6. The deploy flow for any change

1. Build the change locally; `npm run build` if JS/CSS/Blade-with-Alpine changed.
2. **`php artisan test`** — green before anything leaves your machine. The suite (esp. `tests/Feature/MultiTutorTest.php`) is your regression net for data isolation and billing math.
3. Write the migration (additive, reversible, CHECK updated, Postgres-guarded).
4. Commit & push (never commit `.env`).
5. On production: **back up the DB**, deploy code, run `php artisan migrate --force`.
6. Smoke-test the live feature with a real account; check `storage/logs/laravel.log`.
7. **Rollback plan:** if code is bad, redeploy the previous commit. If a migration is bad, restore from the backup / Neon point-in-time. This is *why* changes are additive — additive migrations rarely need rollback because old code tolerates the new column.

---

## 7. Quick decision guide

- *"Just showing existing data differently?"* → 🟢 No migration. Ship it.
- *"Adding a new field / option / type?"* → 🟡 Additive migration (nullable column / new table / extend CHECK). Gate new behaviour to new records.
- *"Renaming or removing something with data?"* → 🔴 Expand-contract across deploys. Backup first. Don't rush it.
- *"Touching money or marks?"* → Recompute server-side; snapshot into the row; never trust the client; cover with a test.
- *"Adding a value to a dropdown list?"* → Update `config/wowlo.php` **and** the CHECK constraint in the same migration.
