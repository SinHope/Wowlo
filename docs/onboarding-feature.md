# Wowlo — Onboarding Tour

**Purpose:** a short, role-aware welcome tour that shows a new user how to navigate Wowlo and what each feature does, and nudges them to set their own password. Shown automatically the first time an account reaches the dashboard; replayable anytime.

> Companion docs: [DATABASE.md](DATABASE.md) · [FEATURE_CHANGES.md](FEATURE_CHANGES.md) · [SCALABILITY.md](SCALABILITY.md).

---

## 1. How it triggers (and why this way)

Trigger is a **server-side flag**, `users.onboarded_at` (nullable timestamp):

- `NULL` → never finished/skipped → the tour **auto-opens on the dashboard**.
- set → never auto-shows again.

**Why server-side, not device-local (`localStorage`)?** It's the best-UX reading of "show on first login *and* first install":

- It shows once **per account**, the first time they land on the dashboard — which is exactly what happens right after a first login *or* right after installing the PWA and opening it.
- It **survives reinstalls and the domain move** (the data lives on the server, not the device), so people aren't re-nagged. A device-local flag would re-trigger on every new device/reinstall.

**Auto-open is scoped to the dashboard route** (`request()->routeIs('dashboard')`) so a new user isn't interrupted on every page — they see it on their natural landing page, and can replay it elsewhere on demand.

---

## 2. What the user sees

- A centered card carousel (bottom sheet on mobile, dialog on desktop) with progress dots.
- One card per feature: an icon, the title, a **"Menu: <name>"** chip telling them where it lives in the sidebar, and a short "what you can do / what happens" description.
- A dedicated **"Change your password"** card with a **"Change my password now"** button that deep-links to `profile.edit#update-password`.
- Controls: **Back / Next**, **Skip** (top-right), **"Got it, take me in"** on the last card, swipe left/right on touch.

Content is **role-specific**:

| Role | Cards covered |
|---|---|
| `super_admin` | Welcome · Dashboard · **Tutors** · Students · Homework · Messages & Inbox · Finance & WhatsApp Billing · Exam Papers (you approve) · Quizzes · Password · Done |
| `tutor` | Welcome · Dashboard · Students · Homework · Messages & Inbox · Finance & WhatsApp Billing · Exam Papers (upload → pending) · Quizzes · Password · Done |
| `student` | Welcome · Dashboard · Homework · Messages · Tuition Fee · Exam Papers · Quizzes · Password · Done |

---

## 3. Architecture (files)

| File | Role |
|---|---|
| `database/migrations/2026_06_04_000010_add_onboarded_at_to_users_table.php` | Adds the nullable `onboarded_at` flag. Additive — every existing user is simply "not onboarded yet". |
| `app/Models/User.php` | Casts `onboarded_at` to datetime; `needsOnboarding()` helper. |
| `app/Http/Controllers/OnboardingController.php` | `complete()` — stamps `onboarded_at` once (idempotent), returns `204`. |
| `routes/web.php` | `POST /onboarding/complete` (auth) → `onboarding.complete`. |
| `resources/views/partials/onboarding.blade.php` | The whole tour: role-based step content (PHP) + Alpine carousel (JS). |
| `resources/views/layouts/app.blade.php` | Includes the partial; adds **"Replay tutorial"** to the name menu (dispatches `wowlo:replay-onboarding`). |
| `resources/views/profile/edit.blade.php` | `id="update-password"` anchor for the password shortcut. |
| `tests/Feature/OnboardingTest.php` | New-user needs onboarding · complete sets the flag · idempotent · auth required. |

**Completion call:** the modal POSTs to `onboarding.complete` via `fetch(..., { keepalive: true })` so the request still finishes if the user is navigating away (e.g. clicking the password button). CSRF token comes from the `<meta name="csrf-token">` already in the app layout.

---

## 4. How to change it

- **Edit / add a step:** open `partials/onboarding.blade.php` and edit the `$steps` array for the relevant role. Each step is `['icon' => <heroicon path>, 'title' => '', 'body' => '', 'menu' => '(optional)', 'action' => '(optional, e.g. "password")']`. Icons are single-path Heroicons in the `$ic` map at the top.
- **Re-trigger for everyone** (e.g. after a big UI change so all users see the new tour): set `onboarded_at = NULL` for the users you want — additive and safe. (Tip: scope it, e.g. only students, to avoid nagging staff.)
- **Replay** is client-only — the name-menu button dispatches `wowlo:replay-onboarding`; it does not touch the flag.

---

## 5. Deploy & data safety

- The migration is **additive + nullable** — see [DATABASE.md](DATABASE.md). No existing data is touched; every current user just gets the tour once on next dashboard visit.
- No new dependency was added (pure Alpine + Tailwind), so nothing extra to install in production.

---

## 6. Known behaviour & future enhancements

- **Modal, not spotlight.** We deliberately use a card modal rather than highlighting live nav elements, because Wowlo is a phone/tablet PWA where the nav is a collapsed sidebar + dropdown — spotlighting hidden elements is fragile on mobile. Trade-off: it *names* the menu item rather than physically pointing at it.
- **Possible future upgrade:** a true element-spotlight walkthrough (e.g. Driver.js) that auto-opens the sidebar and highlights each item, with cross-page step persistence. More immersive, more moving parts — revisit if users want it.
- **Per-feature coach marks:** small one-time tips on individual pages (e.g. first time on the Quiz builder) could complement the global tour later, using the same `onboarded_at`-style flag pattern per feature.
