# Banner Notifications (Slice 15)

A **super_admin-only**, app-wide announcement bar shown at the **top of the app**
to a chosen audience. Built so the platform owner can broadcast a notice the
whole user base must see — most importantly **"we are moving to a new domain,
tap here to reinstall the app"** (a PWA is bound to its origin, so a domain
change means every user must reinstall), plus feature updates / general notices.

> **Planned sibling — Patch Notes (not built yet):** a separate "Patch Notes"
> tab is intended next, for a running changelog of updates. Keep banners focused
> on transient broadcasts; Patch Notes will be the persistent history.

## What it does

- A **"Banner Notification"** sidebar tab appears **only for the super_admin**
  (`$isSuperAdmin` in `layouts/app.blade.php`, megaphone icon, after "Tutors").
- The owner composes a message in a small **rich-text editor** supporting
  **bold, italic, underline, strikethrough, font colour, and hyperlink**.
- They pick an **audience**: `everyone`, `tutors`, or `students`.
- On publish, the banner shows as a purple bar at the very top of the app for
  every matching user, until they **dismiss** it. The owner can switch a banner
  **off/on** or **delete** it without losing the others.

## Where the pieces live

| Piece | File |
|---|---|
| Migration | `database/migrations/2026_06_24_000001_create_banners_table.php` |
| Model | `app/Models/Banner.php` — `scopeActive`, `scopeVisibleTo($user)` |
| Audience list (canonical) | `config/wowlo.php` → `banner_audiences` (mirrors the Postgres `banners_audience_check`) |
| Controller | `app/Http/Controllers/Admin/BannerController.php` |
| Validation | `app/Http/Requests/BannerRequest.php` |
| HTML sanitiser | `app/Support/HtmlSanitizer.php` |
| Routes | `routes/web.php` — inside the `role:super_admin` / `admin.` group |
| Admin UI | `resources/views/admin/banners/{index,create,edit,_form}.blade.php` |
| Display partial | `resources/views/partials/banners.blade.php` (included in `layouts/app.blade.php`) |
| Tests | `tests/Feature/BannerTest.php` |

## Security & rendering notes

- **Authorization:** the whole admin area is behind `role:super_admin`, so tutors
  and students get a 403 — covered by `BannerTest`.
- **HTML is sanitised server-side before storage**, never on render. The banner
  is rendered with `{!! !!}` to *every* user, so even though only the trusted
  super_admin can write it, `HtmlSanitizer::clean()` strips everything outside a
  tiny allowlist: tags `b/strong/i/em/u/s/strike/span/a/br/p/div`; on `<a>` only
  a safe `href` (`http(s)://` or `mailto:`, forced `target=_blank rel=noopener`);
  on any tag only `style: color`. Scripts, event handlers, `javascript:`/`data:`
  URLs, and all other attributes are dropped. Empty-after-sanitise content is
  rejected with a validation error.
- The compose editor uses the browser's `document.execCommand` (with
  `styleWithCSS` so colour emits `<span style="color:…">`). It is an inline
  Alpine component (`bannerEditor()` in `_form.blade.php`); no new dependency.

## Audience targeting

`Banner::visibleTo($user)` returns the active banners a user should see, newest
first: students see `everyone` + `students`; tutors and the super_admin see
`everyone` + `tutors`.

## Dismissal

Per-user, client-side via `localStorage`, keyed `wowlo.banner.{id}.{updatedAt}`.
Editing a banner bumps its `updated_at`, so an edit **re-shows** it to people who
had dismissed it. A reinstall / new device naturally re-shows everything — which
is fine (and on-purpose for the domain-move use case).

## Changing the audience list

It's a CHECK-constrained list: edit `config('wowlo.banner_audiences')` **and**
update the `banners_audience_check` constraint via a new migration (see
[`DATABASE.md`](DATABASE.md) §5). Don't change one without the other.
