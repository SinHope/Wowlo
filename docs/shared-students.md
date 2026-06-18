# Wowlo — Shared Students (one student, multiple tutors)

**Status:** 🔵 **Not built — future slice, parked until real demand.** Today a student account belongs to exactly **one** tutor (`users.tutor_id` is a single value). This doc records the workaround, the trigger for building it, and the architectural shape — so the idea isn't lost and we don't accidentally build it the risky way.

> Companion docs: [DATABASE.md](DATABASE.md) §3 (tenancy backbone) · [FEATURE_CHANGES.md](FEATURE_CHANGES.md) (expand-contract pattern — this slice **needs** it) · [SECURITY.md](SECURITY.md) (isolation rules that must keep holding).

---

## 1. The scenario

A real student (e.g. John) has **two independent tutors on Wowlo**: Tutor A teaches him English/Math/Science, Tutor B teaches him Chinese. Both want to send him homework, messages, quizzes, and bills through the app.

## 2. Today's answer — the workaround (works now, zero build)

Each tutor creates **their own student account** for John on their own roster:

- Emails are unique, so the second account needs a different email — a parent's email or a `+` alias (`john+chinese@gmail.com`) works fine.
- John gets two logins, one per tutor. Each login shows **only** that tutor's homework, messages, quizzes and fees.
- Isolation is automatic and total — neither tutor can see the other's content. This is the tenancy model working as designed, not a bug.

**Friction:** John (or his parent) juggles two logins. Acceptable at small scale.

## 3. When to build the real thing

Build only when one of these actually happens:

- Repeated **user feedback** asking for one login across tutors (track it — gut feel doesn't count).
- Shared-student counts grow to where the two-login workaround is a real support burden.

Until then: **do nothing.** The workaround costs zero and carries zero risk.

## 4. What the real slice looks like (architecture sketch, decisions NOT locked)

This is **not** additive — it touches the tenancy backbone, so it must follow the
[FEATURE_CHANGES.md](FEATURE_CHANGES.md) **expand-contract** pattern:

1. **Expand:** add a `student_tutor` pivot table (`student_id`, `tutor_id`, timestamps; unique pair). Backfill one row per existing student from `users.tutor_id`. Keep `users.tutor_id` populated and authoritative during the transition.
2. **Migrate reads:** re-scope every tutor-facing query from `where('tutor_id', auth()->id())` / `auth()->user()->students()` to go through the pivot. Every route-binding ownership check changes too (still 404, never 403). This is the big, careful part — it touches homework, messages, fees, payments, bills, quizzes, dashboards.
3. **Per-tutor partitioning stays:** John's English homework belongs to Tutor A; Tutor B must never see it. Content tables already carry `tutor_id` directly (`homeworks`, `bills`, `quizzes`), so the student sees the union, each tutor sees only their slice — that property must survive, with isolation tests proving it.
4. **Contract (much later):** once everything reads from the pivot, `users.tutor_id` can be retired — separate deploy, after backups.

**Open product decisions when we get there:** how a second tutor attaches to an existing student (invite code? email match + student approval?), what the student/parent consents to, how billing stays per-tutor.

## 5. Tests required when built

- Every isolation test in `tests/Feature/MultiTutorTest.php` re-run and extended: Tutor B must never see Tutor A's homework/messages/bills/quizzes for the *same shared student*.
- Student sees the union of both tutors' content, and only their own.
- A tutor can never attach themselves to a student without the agreed consent flow.
- 404-not-403 still holds for un-owned records.
