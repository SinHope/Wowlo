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

## 7. Public "How to use" page (a non-modal mirror of this tour)

There's a **public** companion to the tour at **`/how-to-use`** (route name `how-to-use`, view `resources/views/how-to-use.blade.php`), linked from the landing page navbar (desktop + mobile) and the footer. It shows the **same walkthrough content as the modal, but inline on the page** — a stepper card with progress dots and Back/Next instead of an overlay — so visitors can learn the app **before** logging in.

| Aspect | The tour (`partials/onboarding.blade.php`) | The page (`how-to-use.blade.php`) |
|---|---|---|
| Trigger | Auto-opens on first dashboard visit (`onboarded_at` NULL); replayable | A normal public page you navigate to |
| Form | Fixed modal overlay | Inline carousel card, centered on the page |
| Audience | Role-aware (reads `auth()->user()`) | Public, so it can't read a role — a **For students / For tutors** toggle picks the set |
| Auth | Behind login | Logged-out friendly (last card CTA is "Log in", or "Go to Dashboard" if authed) |

**Screenshots.** Each step can carry a list of screenshots (`'imgs' => [...]`) saved in **`public/images/how-to-use/`** (`.jpeg`). They render as a small side-by-side gallery (text on the right; stacks on mobile). A file is shown **only if it exists** (`file_exists` check), so missing/added screenshots never break the page — a step with no image falls back to its Heroicon. Filenames follow `student-<feature>.jpeg` / `tutor-<feature>.jpeg` (some features have a `-2` second shot or an extra screen, e.g. `tutor-homework-status*.jpeg`, `tutor-whatsapp-billing.jpeg`).

> ⚠️ **Content is duplicated.** The step copy lives in **both** `partials/onboarding.blade.php` (`$steps`) and `how-to-use.blade.php` (`$studentSteps` / `$tutorSteps`). If you reword a step, update **both**. (A future refactor could lift the steps into `config/` as a single source of truth.)

---

## 8. Known behaviour & future enhancements

- **Modal, not spotlight.** We deliberately use a card modal rather than highlighting live nav elements, because Wowlo is a phone/tablet PWA where the nav is a collapsed sidebar + dropdown — spotlighting hidden elements is fragile on mobile. Trade-off: it *names* the menu item rather than physically pointing at it.
- **Per-feature coach marks:** small one-time tips on individual pages (e.g. first time on the Quiz builder) could complement the global tour later, using the same `onboarded_at`-style flag pattern per feature.

### Decision: "navigate to each feature page" is deferred to Phase 2 (2026-06-04)

A natural ask came up: instead of the tour staying on the dashboard and *naming* each menu item, could it **actually navigate to each feature page** as you step through it?

**It's possible**, in three shapes (least → most effort):

1. **"Show me" link per step** — keep this carousel, add a "Take me to <feature> →" button on each card (same proven pattern the password step already uses). Tour pauses when they click through.
2. **Guided walk (auto-navigate)** — Next loads the real feature page and the card reappears shrunk into a corner so the page is visible behind it. Requires step state persisted across full page reloads (server-rendered Blade = a reload per step), the onboarding partial loaded on *every* feature page, and the card re-worked from full overlay → corner popover. Most "wow", most moving parts, and heavier on the mobile PWA.
3. **Element-spotlight walkthrough** (e.g. Driver.js) that auto-opens the sidebar and highlights each item — rejected for the mobile-fragility reason above.

**Decision — leave the current dashboard modal as-is for now, and let real usage decide.** The live audience is small and known (the owner's own students plus a few tutors and theirs), so we'll **gather their feedback first**:

- If users say the current tour is clear and good enough → **keep it unchanged.**
- If they say they'd rather be walked into each section → **revisit in Phase 2**, most likely starting with option 1 ("Show me" links — small, safe, additive) before considering option 2.

This keeps us from building immersive tour machinery nobody has asked for yet. Phase 2 is also where public tutor sign-up lands (see architecture §H, MT5), so onboarding polish is a natural fit to reassess then.

---

## 7. Public "How to use" page (`/how-to-use`)

A **public** companion to the in-app tour — the *same* walkthrough, shown inline on a page so prospective users and parents can see how Wowlo works **without logging in**.

- **Route:** `GET /how-to-use` → `how-to-use` view (in `routes/web.php`, public).
- **View:** `resources/views/how-to-use.blade.php` — self-contained (its own nav/footer, the step content in a `@php` block, and a small `howToUse()` Alpine component).
- **Audience toggle:** a **For students / For tutors** switch (the page is public, so it can't read a logged-in role — it offers both). Each shows that audience's steps as an inline carousel (Back/Next, swipe), ending with a **Log in** CTA.
- **Linked from:** the landing nav (desktop + mobile hamburger) and the landing/how-to-use footers, as **"How to use"**.

### Screenshots (optional, graceful)
Each step may name screenshot files (the `'imgs'` arrays in `how-to-use.blade.php`). They're resolved with a `file_exists()` check against **`public/images/how-to-use/`** — a screenshot shows **only if the file is present**, otherwise the card falls back to its icon. So you can add/replace screenshots anytime (`.jpeg`/`.png`, named as in the `imgs` arrays, e.g. `student-dashboard.jpeg`, `tutor-finance.jpeg`) without touching code or risking a broken image.

### ⚠️ Keep the two tours in sync
The step wording/content is **duplicated** between:
- `resources/views/partials/onboarding.blade.php` (the in-app, role-based modal), and
- `resources/views/how-to-use.blade.php` (this public, toggle-based page).

If you change a step's title/body or add/remove a step in one, **mirror it in the other** so the in-app tour and the public guide stay consistent. (They were intentionally kept as separate files — one is a logged-in modal keyed off role, the other a public page with a manual toggle — rather than a shared partial, to keep each simple.)

### SEO: FAQ section + `FAQPage` schema (below the tour)
The page also carries a **static FAQ** section (seven Q&As) beneath the carousel, plus matching **`FAQPage`** JSON-LD. This is the page's main SEO / AI-Overview asset — unlike the Alpine carousel (whose copy is JS-rendered), the FAQ is plain crawlable HTML.

> ⚠️ Both the visible FAQ cards **and** the schema render from a single `$faqs` array in `how-to-use.blade.php`. **Edit the array, never the JSON-LD by hand** — that's what keeps the visible text and the structured data identical (Google rejects `FAQPage` markup that doesn't match on-page text). Keep the answers accurate to how Wowlo actually works (free, tutor-provisioned accounts, data isolation, PWA). Context: [`seo-audit/ACTION-PLAN.md`](../seo-audit/ACTION-PLAN.md) item M2.
