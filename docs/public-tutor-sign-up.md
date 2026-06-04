# Wowlo — Public Tutor Sign-Up (Phase 2)

**Status:** 🔵 **Not built yet — Phase 2.** Today (testing phase) tutor accounts are created **only** by the super_admin at `/admin/tutors`. This doc is the canonical plan for when we open self-registration so tutors create their own accounts.

> Companion docs: [FEATURE_CHANGES.md](FEATURE_CHANGES.md) (additive change pattern) · [SECURITY.md](SECURITY.md) (the one new public write endpoint) · [DATABASE.md](DATABASE.md) (tenancy) · [MAINTENANCE.md](MAINTENANCE.md) §8 (current manual flow). Architecture: `Wowlo_v2.1_Refined_Decisions_and_Architecture.md` (decision MT5).

---

## 1. The core guarantee — it's purely additive

> Turning on public sign-up **does not migrate, change, or risk any existing account or data.**

A self-registered tutor is **exactly** `role=tutor, tutor_id=null` — byte-for-byte identical to an account the super_admin creates today. There is no schema change and no change to how existing tutors, students or the super_admin work. The only new thing is *another way to create that same row*.

This is why we can safely launch now (manual) and flip on self-registration later without a risky migration.

---

## 2. What already supports it (done)

- **Students are tutor-added.** `Tutor\StudentController` already stamps `tutor_id` server-side from `auth()->id()`. A self-registered tutor adds their own students exactly like an admin-created tutor — nothing to change here.
- **Tenancy isolation** is enforced everywhere (`tutor_id` backbone, scoped lists, 404-not-403 on un-owned records). A new tutor is automatically isolated.
- **Roles** — `role=tutor` already grants the full teaching workspace via `RoleMiddleware('role:tutor,super_admin')`.
- **Onboarding tour** already has a tutor track, so new tutors get guided automatically on first dashboard visit (see [onboarding-feature.md](onboarding-feature.md)).
- **Indexes** for tenant scaling are already in place ([SCALABILITY.md](SCALABILITY.md) §2), so growth in tutor count is handled.

---

## 3. What needs building at Phase 2

1. **Registration route + controller + page.** The Breeze register routes were intentionally removed (`routes/auth.php`). Re-introduce a registration flow that is tutor-only.
2. **Hardcode the role server-side.** The controller sets `role = 'tutor'` and `tutor_id = null` itself. **Never** accept `role` or `tutor_id` from the request — registration validation rules must not include them (same rule as everywhere else: `validated()` strips client input). A self-registrant must never be able to make themselves `super_admin` or attach to another tutor.
3. **Email verification.** New tutors should verify their email (`MustVerifyEmail` on `User`, or the existing `verified` middleware) before reaching the workspace — confirms a real address and curbs throwaway sign-ups.
4. **Abuse / spam controls** (registration is the first *public* write endpoint):
   - Rate-limit the register POST (`throttle:`), like the contact form.
   - Honeypot field (we already use this pattern on the contact form).
   - Optional CAPTCHA if abuse appears.
5. **Decide the approval model** (see §4).
6. **Tests** (see §5).

> Everything above is *additive* — new routes, a new controller, a new Blade page, optionally a new nullable column if an approval model needs one. No existing table or row changes.

---

## 4. Open decisions (make these when we start Phase 2)

| Decision | Options | Lean |
|---|---|---|
| **Approval** | (a) auto-approve on email verify · (b) admin-approves new tutors first (a "pending tutor" queue, mirroring the exam-paper approval pattern) | Start with (a) for low friction; add (b) if spam/quality becomes an issue. (b) would add a nullable `approved_at`/status — additive. |
| **Email verification** | required vs optional | **Required** — it's cheap and filters junk. |
| **Free vs paid** | free for all · free tier + paid plans later | Free at launch; billing is a separate, later, additive concern. |
| **Student caps** | none · soft cap per tutor on free tier | None at first; revisit with scale. |
| **Public marketing page** | reuse landing · dedicated "For tutors" page | Reuse landing + a CTA; dedicated page later. |

These don't block the architecture — they're product choices layered on top of the additive base.

---

## 5. Testing required when built

Following the project rule (authz/isolation must be tested), add to `tests/Feature/` (cross-tenant ones in `MultiTutorTest.php`):

- Registration creates a user with **`role=tutor` and `tutor_id=null`** — and ignores any `role`/`tutor_id` sent in the request (no privilege escalation, no tenant attachment).
- A duplicate email is rejected (unique).
- The register POST is rate-limited.
- (If email verification required) an unverified new tutor can't reach the workspace.
- A freshly self-registered tutor is fully isolated — can't see any other tutor's students/data (the existing isolation tests should hold unchanged).

---

## 6. Security summary

- Registration is the **only public, unauthenticated write endpoint** once enabled → it gets the most scrutiny: strict validation, rate-limit, honeypot, server-set role, email verification.
- No role escalation: `role`/`tutor_id` are server-controlled, never client input — identical to the existing tenancy guarantee in [SECURITY.md](SECURITY.md) §3–§4.
- No data risk to existing users: additive only ([FEATURE_CHANGES.md](FEATURE_CHANGES.md)).

---

## 7. Where this is referenced

- `CLAUDE.md` — guardrail "No public tutor sign-up (yet)" points here.
- `docs/MAINTENANCE.md` §8 — current manual tutor creation + Phase-2 note.
- `Wowlo_v2.1_Refined_Decisions_and_Architecture.md` (decision MT5) — original decision.
