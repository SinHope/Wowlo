# Wowlo — Project Guardrails & Conventions

Wowlo is a tuition-management web app for Singapore tutors (homework, messages, tuition fees, exam papers, MCQ quizzes, and Resources — OAS/short-answer sheets). **Multi-tutor (since Slice 10.5):** three roles — `super_admin` (the owner; teaches a roster AND manages tutor accounts + approves shared exam papers), `tutor` (teaches their own isolated roster), `student`/parent. Built in vertical slices per `Wowlo_v2.1_Refined_Decisions_and_Architecture.md` (authoritative — wins over `Wowlo_Tuition_Management_App_v2.md` on conflicts).

## 🔒 Security guardrails (do not violate)

- **NEVER read, open, `cat`, `Get-Content`, grep, or otherwise inspect `.env`** — not the values, not the key names, not the line count. It is off-limits even though this is local dev and even though I "can't see the values." If I need to know whether something is set, I **tell the user what to look for and they confirm** — I never look myself.
- **NEVER print, echo, or repeat secrets, API keys, passwords, tokens, or credentials** in chat or in command output. When a command (e.g. key generation) would print a secret, suppress its output.
- **`.env` must never be committed to git.** It stays in `.gitignore`.
- **Google OAuth must NEVER auto-create accounts** — it only links to tutor-provisioned accounts. Unknown email → rejected, no account created.
- **Student data isolation:** a student can only ever access their own homework / messages / fees / payments / quiz attempts / quiz diagrams. Enforce + test this on every student-facing feature.
- **Tutor tenancy isolation:** `users.tutor_id` is the ownership backbone (a student's owning tutor; NULL for tutor/super_admin). Every tutor-facing list MUST scope by the acting tutor (`auth()->user()->students()` or `where('tutor_id', auth()->id())`), and every route-bound record MUST be ownership-checked (404, not 403, so IDs don't leak) — a tutor can never see/touch another tutor's students, homework, messages, fees, bills, or quizzes. `tutor_id` is set server-side only (never from client input; request rules never include it). **Exception:** exam papers are a SHARED, moderated library — approved papers are global; a non-admin tutor's upload is `pending` until the super_admin approves (then the uploader gets a Message). New cross-tenant behaviour needs an isolation test in `tests/Feature/MultiTutorTest.php`.
- **No public tutor sign-up (yet):** tutor accounts are created only by the super_admin at `/admin/tutors`. Public self-registration is Phase 2 and must remain purely additive (a new account is just `role=tutor, tutor_id=null` — identical to an admin-created one), so existing tutors never lose data. Full plan: [`docs/public-tutor-sign-up.md`](docs/public-tutor-sign-up.md).
- When a tool/command needs a secret to be set, write to `.env` via tooling **without reading it back**, then ask the user to verify in their editor.

## 📚 Reference docs (read before relevant work)

- [`docs/DATABASE.md`](docs/DATABASE.md) — schema map, tenancy model, and the rules for safe migrations. **Read before writing any migration.**
- [`docs/FEATURE_CHANGES.md`](docs/FEATURE_CHANGES.md) — how to add/change features after launch without breaking live users (additive vs expand-contract). **Read before changing an existing feature.**
- [`docs/SCALABILITY.md`](docs/SCALABILITY.md) — indexing, N+1, pagination, Neon pooling, caching, queues; what to do at each growth stage.
- [`docs/SECURITY.md`](docs/SECURITY.md) — auth, tenant isolation, file-upload rules, secrets, PDPA. **Read before touching auth, data access, uploads, or payments.**
- [`docs/TESTING.md`](docs/TESTING.md) — what tests exist, how to run them, the manual checklist, and what must be green before deploy.
- [`docs/INCIDENT_RESPONSE.md`](docs/INCIDENT_RESPONSE.md) — step-by-step playbooks for when something breaks in production.
- [`docs/MAINTENANCE.md`](docs/MAINTENANCE.md) — routine upkeep (logs, backups, audits, user management) after launch.
- [`docs/deployment-slice11-runbook.md`](docs/deployment-slice11-runbook.md) — the deploy steps (Render + UptimeRobot, subdomain-first).
- [`docs/onboarding-feature.md`](docs/onboarding-feature.md) — how the welcome tour works and how to change it, plus the public `/how-to-use` page that mirrors it.
- [`docs/public-tutor-sign-up.md`](docs/public-tutor-sign-up.md) — Phase 2 plan for tutor self-registration (additive; not built yet).
- [`docs/shared-students.md`](docs/shared-students.md) — future slice for one student shared by multiple tutors (expand-contract; parked until real demand — today's answer is one account per tutor).
- [`docs/SuperAdmin-Admin-Contact-Feature.md`](docs/SuperAdmin-Admin-Contact-Feature.md) — Phase 2 designed slice: tutor → super_admin contact form (attachment via R2), admin message filter/sort/broadcast compose, pinned amber "Messages from SuperAdmin/Admin" section for tutors. **Build from this doc.**
- [`docs/short-answer-quizzes.md`](docs/short-answer-quizzes.md) — build spec for short-answer questions + manual grading (next slice after deploy; decisions locked).
- [`docs/resources-answer-sheets.md`](docs/resources-answer-sheets.md) — Resources feature (Slice 13): OAS/short-answer sheets a tutor sends or a student builds, then the tutor marks per-question. **Built.**
- [`docs/spelling-game.md`](docs/spelling-game.md) — Spelling Meow (Slice 14): the first Games-tab game. Student plays (level → fix-the-spelling → letter blanks, with a per-letter typing sound), writes a mandatory reflection (marks shown first); tutor reviews + leaves feedback. Words live in `config/spelling-words.php`. **Built.**
- [`docs/roll-the-dice.md`](docs/roll-the-dice.md) — Roll the Dice: a tiny for-fun second Games-tab game (`/games/roll-the-dice`, shared by all roles). CSS 3D die + synthesised rattle; **no scoring, no data, no tenancy**. **Built.**
- [`docs/multiplication-game.md`](docs/multiplication-game.md) — Multiplication Rabbit (Slice 17): the third Games-tab game, tracked like Spelling Meow (student plays → tutor reviews + feedback). Levels are a digit-count ladder (1×1, 2×2, 3×2, 3×3, Mixed) defined in `config/multiplication-levels.php`; server generates + marks + range-validates (no hidden answer — it's maths); green/teal accent, hop-on-submit sound, mandatory reflection. **Built.**
- [`docs/banner-notifications.md`](docs/banner-notifications.md) — Banner Notifications (Slice 15): super_admin-only app-wide announcement bar at the top of the app (rich text: bold/italic/underline/strikethrough/colour/link), targeted at everyone/tutors/students, dismissible per-user. HTML is sanitised server-side via `App\Support\HtmlSanitizer`. **Built.**
- [`docs/patch-notes.md`](docs/patch-notes.md) — Patch Notes (Slice 16): public changelog — everyone reads, only the super_admin writes. Title + free-text version + rich-text body (bold/italic/underline/strikethrough/bullets) + optional R2 image (streamed inline, never public). Shares the `<x-rich-text-editor>` component (active-button highlighting) with banners. **Built.**

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
- **Timezone: single-region (Singapore).** `config/app.php` is `Asia/Singapore` (UTC+8) — `now()` and all timestamp displays are SGT. Don't hardcode offsets like `+8` anywhere. Going multi-region (e.g. a tutor in Japan) means switching to UTC storage + a per-user timezone — see [`docs/SCALABILITY.md`](docs/SCALABILITY.md) §9; don't build per-user timezones before there's a real cross-region user.
- Tests required for: authorization/data-isolation and fee/billing calculation. Manual for the rest.
- UI: Heroicons SVGs (no emojis), `cursor-pointer` on clickables, status by text + color, `[x-cloak]` to avoid Alpine flashes.
- **Forms whose real submit is behind a confirm modal / JS validation** (quiz & resources builders, the take-quiz page, the grading/marking pages) must guard against the browser's *implicit Enter submission*, which bypasses the modal and POSTs prematurely. Add `@keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"` to the `<form>` — it blocks Enter on text/number/radio inputs while keeping newlines in `<textarea>` and Enter-to-activate on `<button>`. Simple single-field forms (login, search filters) keep native Enter-to-submit.
- **Public-page SEO lives in the page `<head>`.** Only the 5 public marketing pages (`/`, `/about`, `/how-to-use`, `/contact`, `/privacy-policy`) are indexable — the app is auth-gated and must stay out of the index. They use the `<x-seo-meta>` component for title/description/canonical/OG/Twitter (default share image `images/og/wowlo-og.png`); `sitemap.xml` is a **dynamic route** that lists *only* those public pages (never add an auth route to it); JSON-LD is built via `json_encode` (Organization/WebSite/WebApplication on the homepage, FAQPage on `/how-to-use` — edit the `$faqs` array, not the schema). Full audit + status: [`seo-audit/ACTION-PLAN.md`](seo-audit/ACTION-PLAN.md).
