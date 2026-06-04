# Wowlo — Scalability Guide

**Purpose:** keep Wowlo fast and cheap as it grows from 2 tutors to thousands. Today's data is tiny; the goal is to make sure the *shape* of the code and schema doesn't develop habits that hurt at scale, and to know which lever to pull at each growth stage.

> Companion docs: [DATABASE.md](DATABASE.md) (safe schema changes) · [FEATURE_CHANGES.md](FEATURE_CHANGES.md) (safe feature changes).

---

## 1. Scale assumptions

| Stage | Tutors | Students (≈10 each) | Rough total rows | Posture |
|---|---|---|---|---|
| Now | 1–10 | 10–100 | thousands | Anything works. |
| Growth | 100–1,000 | 1k–10k | low millions | Indexes + eager loading + pagination matter. |
| Big | 1,000–10,000 | 10k–150k | tens of millions | Connection pooling, caching, queues, read-path tuning. |

The tables that grow **fastest** are the per-student, per-event ones: `quiz_answers`, `quiz_attempts`, `payments`, `bills`/`bill_lines`, `homeworks`, `messages`. The one global table that grows independent of tenancy is the **shared `exam_papers` library**.

---

## 2. Indexing (the #1 lever) — DONE for current scoping

Postgres does **not** auto-index foreign-key columns (only the primary key they point to). Every tenant-scoping query filters on an FK, so each unindexed one is a full table scan.

Migration `2026_06_04_000000_add_tenant_scope_indexes` added the gaps:
`users.tutor_id`, `homeworks.tutor_id`, `messages.sender_id`, `bills.tutor_id`, `quizzes.tutor_id`, `exam_papers.tutor_id`, `exam_papers.status`.
(Columns already covered by a unique constraint or a composite whose leading column matches the filter were left alone.)

**Rule going forward:** any new column you filter, join, or sort on at scale gets an explicit index in the same migration. Add it *before* the table is large — indexing an empty/small table is instant; indexing a large **live** table requires `CREATE INDEX CONCURRENTLY` (raw SQL, run outside a transaction) to avoid locking writes:

```php
// In a migration, for a big live table:
DB::statement('CREATE INDEX CONCURRENTLY IF NOT EXISTS idx_name ON table (col)');
// and set: public $withinTransaction = false;
```

**When in doubt, ask Postgres.** Prefix a slow query with `EXPLAIN ANALYZE` — if you see `Seq Scan` on a big table where you expected a filter, you're missing an index.

---

## 3. N+1 queries (the #1 silent killer)

A list page that loads 50 rows and then lazy-loads a relation per row fires 51 queries. Invisible with 2 students, brutal with 2,000.

- **Always eager-load** relations you render in a loop: `Homework::where(...)->with('student')->latest()->get()`.
- The dashboard already does this (`->with('student')`). Keep the habit on every new list.
- In local dev, watch the Laravel Debugbar / query count. A list view should be a small constant number of queries, not "one per row."

---

## 4. Pagination (do this as lists grow)

Today rosters are ≤15 students so unpaginated lists are fine. Two places to paginate **before** they get big:

- The **shared `exam_papers` library** — it's global and grows without bound. Paginate it (`->paginate(24)`) and add filter-by-subject/level/year (the `(subject, year)` index already supports this).
- Any tutor list once a power-tutor can have hundreds of homework/quiz/bill rows.

Use `->paginate()` / `->simplePaginate()` rather than `->get()` on anything unbounded. `simplePaginate` is cheaper (no total-count query) when you don't need page numbers.

---

## 5. Database connection (Neon) — switch to the pooled string at growth

Neon free tier scales to zero and has a modest direct-connection limit. Each PHP request opens its own DB connection, so under real concurrency you can exhaust direct connections.

- **Use Neon's POOLED connection string** (the host with `-pooler` in it, PgBouncer) for the app's `DATABASE_URL` in production. It multiplexes many app connections onto few Postgres ones.
- PgBouncer runs in *transaction* mode → **do not** use persistent PDO connections or session-level features that assume a sticky connection.
- Keep `sslmode=require`. (Reminder from `CLAUDE.md`: Proton VPN breaks the Neon SSL handshake locally — unrelated to prod, but it bites during dev.)
- At the "Big" stage, move off the free tier (autosuspend + compute limits) to a paid Neon compute size, and consider a read replica for heavy read pages.

---

## 6. Caching (add when reads dominate)

Cache driver is currently the database (`cache` table). That's fine early. At the Growth stage:

- Move cache + sessions to **Redis** (one env change + driver) so they don't add load to Postgres.
- Cache things that are read often and change rarely: the approved exam-paper list, config-derived dropdowns, per-tutor dashboard counts (short TTL).
- Always recompute money/marks live — never cache a financial total and serve it as truth.

---

## 7. Queues (move slow work off the request)

Email (Resend) and web-push are done in-request today. As volume grows, a slow third-party call shouldn't make a tutor wait or fail their action.

- Push email + web-push onto a **queued job** (`ShouldQueue`) with a worker (`php artisan queue:work`, kept alive by the host's process manager).
- Bill generation across a whole roster, and any future "notify all students" feature, should be queued/chunked, never a single synchronous loop over thousands of rows.

---

## 8. File storage (already scalable)

Files go to the private Cloudflare R2 bucket; the DB stores only the object key; downloads stream through an authorized route. R2 scales effectively without limit and has no egress fees — no action needed beyond watching total storage cost. Don't ever switch these to public URLs (breaks data isolation).

---

## 9. Growth checklist (pull the lever when you hit the stage)

- [x] Index every tenant-scoping FK *(done — migration `2026_06_04_000000`)*
- [ ] Eager-load relations on every new list view *(ongoing habit)*
- [ ] Paginate the shared exam-paper library + any unbounded tutor list
- [ ] Switch production `DATABASE_URL` to Neon's **pooled** endpoint
- [ ] Move cache + sessions to Redis
- [ ] Queue email + web-push; chunk roster-wide jobs
- [ ] Add `EXPLAIN ANALYZE` to any page that feels slow; index what's scanning
- [ ] Leave Neon free tier for a paid compute (and a read replica) at the Big stage
