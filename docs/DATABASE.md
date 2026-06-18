# Wowlo — Database Guide

**Purpose:** the rules for changing the database without breaking live users' data. Read this before writing any migration. The database is the one part of Wowlo where a mistake can lose real people's homework, fees, and payment history — there is no "undo" on a dropped column in production.

> Companion docs: [SCALABILITY.md](SCALABILITY.md) (keeping it fast as tutors grow) · [FEATURE_CHANGES.md](FEATURE_CHANGES.md) (shipping features safely after launch).

---

## 1. The golden rule

> **On a live database, changes must be ADDITIVE and BACKWARD-COMPATIBLE.**
> Add new nullable columns / new tables. Never drop or rename a column with data in a single step.

Why: while a deploy is rolling, the *old* code may still be running against the *new* schema (and vice-versa) for a few seconds. Additive changes survive that overlap; destructive ones break it. More importantly, a dropped/renamed column is **permanent data loss** if you got it wrong.

---

## 2. Hard rules (never violate)

1. **Never edit a migration that has already run in production.** Migrations are forward-only history. To change something, write a *new* migration. Editing an old one means it'll never re-run on prod, so prod and your code silently diverge.
2. **New `NOT NULL` columns need a default** (or a backfill step), or the migration fails on tables that already have rows.
3. **`.env` is never committed.** Secrets live only in the host's environment panel. (See the security guardrails in `CLAUDE.md`.)
4. **Money and marks are always recomputed server-side** — the DB stores snapshots (e.g. `bills.grand_total`), never values the client sent. Bills deliberately snapshot totals at generation time so history doesn't change when a rate changes later.
5. **CHECK constraints mirror the canonical lists** in `config/wowlo.php`. If you add a value to a list (a new subject, exam type, status), you MUST add a migration that updates the matching Postgres CHECK constraint, or inserts will be rejected in production even though tests pass.
6. **Tests are SQLite, production is Postgres.** SQLite can't `ALTER TABLE ... ADD CONSTRAINT`, so CHECK constraints are wrapped in `if (DB::getDriverName() === 'pgsql')`. Anything Postgres-specific must be guarded the same way, or the test suite breaks.
7. **Back up before any schema change on production** (see §6).

---

## 3. How tenancy works (the part most likely to leak data)

`users.tutor_id` is the ownership backbone:

- A **student** row has `tutor_id` = the owning tutor's id. **One tutor per student account** — a real-world student with two tutors on Wowlo uses two accounts (different emails), one per tutor. A "shared student" (one account, many tutors) is a parked future slice that would replace this column with a pivot via expand-contract: see [shared-students.md](shared-students.md).
- A **tutor / super_admin** row has `tutor_id = NULL`.
- Everything a student owns (homework, fees, payments, bills, quiz attempts, messages) is reachable from the student, so it inherits the student's tutor.
- Some tables also carry `tutor_id` directly for fast scoping: `homeworks`, `bills`, `quizzes`, `exam_papers`.

**Rules when adding any tenant-owned table or feature:**

- `tutor_id` (or a `student_id` that resolves to one) is set **server-side only** — never from request input. Request validation rules must never include `tutor_id`, so `validated()` strips any client-supplied value.
- Every list query scopes by the acting tutor: `auth()->user()->students()` or `where('tutor_id', auth()->id())`.
- Every route-bound record is ownership-checked and returns **404** (not 403) on a miss, so IDs don't leak across tenants.
- Add an isolation test in `tests/Feature/MultiTutorTest.php` for any new cross-tenant surface.
- **Exception:** exam papers are a shared, moderated library — approved papers are global; a non-admin upload is `pending` until the super_admin approves it.

---

## 4. Schema map (current)

| Table | Owned via | Notes |
|---|---|---|
| `users` | self (`tutor_id`) | roles: `super_admin`, `tutor`, `student`. `role` has a CHECK constraint. |
| `homeworks` | `tutor_id` + `student_id` | status `pending`/`done` (CHECK). |
| `messages` | `sender_id` (tutor) / `receiver_id` (student) | `is_read` flag drives the inbox badge. |
| `tuition_fees` | `student_id` (unique — one per student) | hourly rate snapshot. |
| `payments` | `student_id` | method has a CHECK. |
| `bills` + `bill_lines` + `bill_charges` | `student_id` + `tutor_id` | totals are **snapshots** taken at generation. |
| `exam_papers` | `tutor_id` (uploader) | **shared library**; `status` `pending`/`approved` (CHECK), `approved_by`, `approved_at`. |
| `quizzes` + `quiz_questions` | `tutor_id` | `exam_type` has a CHECK; questions have `question_type` (currently `mcq`). |
| `quiz_assignments` | `student_id` | unique `(quiz_id, student_id)`. |
| `quiz_attempts` | `student_id` | unique `(quiz_id, student_id)` — one attempt per quiz in MVP. Stores `total_marks`, `obtained_marks`. |
| `quiz_answers` | via `attempt_id` | `is_correct`, `marks_awarded`. |
| `push_subscriptions` | `user_id` | web-push (PWA). |

Files (homework attachments, exam papers, quiz diagrams) live on the **private R2 bucket**; the DB stores only the object key, and downloads stream through an authorized controller route — never a public URL.

---

## 5. Writing a migration — checklist

1. `php artisan make:migration describe_the_change` (timestamped; goes after all existing ones).
2. Make it **additive**: new nullable column, new table, new index. If you must change existing structure, use the **expand → migrate → contract** pattern in [FEATURE_CHANGES.md](FEATURE_CHANGES.md).
3. Guard any Postgres-only SQL with `if (DB::getDriverName() === 'pgsql')`.
4. If you touched a canonical list in `config/wowlo.php`, update the matching CHECK constraint in the same migration.
5. Write a real `down()` so the change is reversible locally.
6. `php artisan test` — confirms the migration runs clean on SQLite and nothing regressed.
7. On production, **back up first**, then `php artisan migrate --force`.

> **Index note:** in Postgres, foreign-key columns are **not** auto-indexed. Any column you filter or join on at scale needs an explicit `$table->index(...)`. Add it *before* the table grows — see [SCALABILITY.md](SCALABILITY.md) §2.

---

## 6. Backups (do this before every prod migration)

Neon (managed Postgres) provides **point-in-time restore** on the dashboard — the primary safety net. In addition, take a manual logical dump before risky changes:

```bash
# Reads the pooled connection string from your environment — never paste secrets into a command.
pg_dump "$DATABASE_URL" --no-owner --format=custom --file="backup-$(date +%F).dump"
```

Restore into a scratch database first and verify before ever restoring over production. **Never** test a restore against the live DB.

---

## 7. What NOT to do

- ❌ Edit a migration that already ran on prod.
- ❌ `dropColumn` / `renameColumn` on a populated column in one deploy (use expand-contract).
- ❌ Add `NOT NULL` without a default or backfill.
- ❌ Trust a client-submitted total, mark, or `tutor_id`.
- ❌ Add a list value without updating its CHECK constraint.
- ❌ Run a destructive migration on prod without a fresh backup.
- ❌ Blanket-shift a live timestamp column for a timezone change without a backup **and** a clean cutoff between old (UTC) and new (local) rows — without it you double-shift the rows already written in the new zone. The app is single-region `Asia/Singapore` today; the move to UTC storage + per-user timezone is [SCALABILITY.md](SCALABILITY.md) §9.
