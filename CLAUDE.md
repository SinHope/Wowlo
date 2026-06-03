# Wowlo — Project Guardrails & Conventions

Wowlo is a tuition-management web app for Singapore tutors (homework, messages, tuition fees, exam papers, MCQ quizzes). **Multi-tutor (since Slice 10.5):** three roles — `super_admin` (the owner; teaches a roster AND manages tutor accounts + approves shared exam papers), `tutor` (teaches their own isolated roster), `student`/parent. Built in vertical slices per `Wowlo_v2.1_Refined_Decisions_and_Architecture.md` (authoritative — wins over `Wowlo_Tuition_Management_App_v2.md` on conflicts).

## 🔒 Security guardrails (do not violate)

- **NEVER read, open, `cat`, `Get-Content`, grep, or otherwise inspect `.env`** — not the values, not the key names, not the line count. It is off-limits even though this is local dev and even though I "can't see the values." If I need to know whether something is set, I **tell the user what to look for and they confirm** — I never look myself.
- **NEVER print, echo, or repeat secrets, API keys, passwords, tokens, or credentials** in chat or in command output. When a command (e.g. key generation) would print a secret, suppress its output.
- **`.env` must never be committed to git.** It stays in `.gitignore`.
- **Google OAuth must NEVER auto-create accounts** — it only links to tutor-provisioned accounts. Unknown email → rejected, no account created.
- **Student data isolation:** a student can only ever access their own homework / messages / fees / payments / quiz attempts / quiz diagrams. Enforce + test this on every student-facing feature.
- **Tutor tenancy isolation:** `users.tutor_id` is the ownership backbone (a student's owning tutor; NULL for tutor/super_admin). Every tutor-facing list MUST scope by the acting tutor (`auth()->user()->students()` or `where('tutor_id', auth()->id())`), and every route-bound record MUST be ownership-checked (404, not 403, so IDs don't leak) — a tutor can never see/touch another tutor's students, homework, messages, fees, bills, or quizzes. `tutor_id` is set server-side only (never from client input; request rules never include it). **Exception:** exam papers are a SHARED, moderated library — approved papers are global; a non-admin tutor's upload is `pending` until the super_admin approves (then the uploader gets a Message). New cross-tenant behaviour needs an isolation test in `tests/Feature/MultiTutorTest.php`.
- **No public tutor sign-up (yet):** tutor accounts are created only by the super_admin at `/admin/tutors`. Public self-registration is Phase 2 and must remain purely additive (a new account is just `role=tutor, tutor_id=null` — identical to an admin-created one), so existing tutors never lose data.
- When a tool/command needs a secret to be set, write to `.env` via tooling **without reading it back**, then ask the user to verify in their editor.

## Stack

Laravel 13 · PHP 8.4 (Laravel Herd, Windows) · Blade · Tailwind CSS v3 · Alpine.js · PostgreSQL (Neon, Singapore region) · Cloudflare R2 (private bucket, disk `r2`) · PWA. Auth via Breeze + Socialite (Google).

## Local dev

- Run: `php artisan serve` → http://localhost:8000
- Build assets: `npm run build` (after any JS/CSS/Blade-with-Alpine change)
- Tests: `php artisan test` (Pest; in-memory SQLite — never touches Neon)
- Neon scales to zero on the free tier; **Proton VPN breaks the Neon SSL handshake** — turn the VPN off when the DB connection fails.

## Conventions

- Build one vertical slice at a time; show the user an in-browser checkpoint each step (they're a learner who wants explanations + visual confirmation).
- Canonical lists (levels, subjects, exam types) live in `config/wowlo.php` — single source of truth for dropdowns + `Rule::in` validation. Postgres CHECK constraints that mirror a list are updated via migration whenever the list changes.
- Run `php artisan migrate` only after creating/changing a migration file — not after every change.
- Files (homework, exam papers, quiz diagrams) go to the private R2 bucket; DB stores the object key; downloads stream through an authorized controller route (never a public URL).
- Server always recomputes money/marks (never trusts client-submitted totals).
- Tests required for: authorization/data-isolation and fee/billing calculation. Manual for the rest.
- UI: Heroicons SVGs (no emojis), `cursor-pointer` on clickables, status by text + color, `[x-cloak]` to avoid Alpine flashes.
