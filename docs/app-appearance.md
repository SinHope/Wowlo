# App Appearance (Settings) — theme + background

Per-user **Settings** page (`/settings`, name menu → Settings): a **Light / Dark / System** theme and an optional **app background** image. Appearance only — notification toggles were deliberately left out (see "Later").

## How it works
- **Storage:** `users.theme` (`system` default | `light` | `dark`; list in `config/wowlo.php` → `themes`) and `users.app_background` (nullable, e.g. `Soft/clouds.jpg`). Additive migration `2026_11_15_000000`. Server-side so it follows the user across devices.
- **No flash:** an inline `<script>` in `layouts/app.blade.php` sets `<html class="dark">` before first paint. `System` follows `prefers-color-scheme` live. The same script exposes `window.wowloAppearance` so the Settings page previews choices instantly (saved on **Save settings**).
- **Dark mode without editing every view:** `cream` / `ink` / `muted` are CSS variables (`tailwind.config.js` → `rgb(var(--c-…) / <alpha-value>)`), and `resources/css/app.css` has a `.dark` block that remaps the stock `bg-white`, `gray-*`, a few amber/emerald/teal/red shades and form inputs. **If a new view looks wrong in dark, add its class to that block** (or use `ink`/`muted`/`cream` tokens).
- **Background:** `public/app-background/<Category>/<image>` (jpg, jpeg, png, webp, avif). Drop files in and they appear automatically; a new folder is a new category. Current categories: Simple, Soft, High Contrast, Cute Animals. Rendered as two fixed `body::before/::after` layers (image + 35% wash, iOS-safe); cards stay solid. Tune the wash in `app.css` (`html[data-bg] body::after`).
- **Card transparency (slider):** a background is invisible if every card is solid, so Settings has a slider (0 = solid, up to 50% see-through; floor in `config('wowlo.card_opacity_min')`). Stored as `users.card_opacity` (percent, default 100; migration `2026_11_15_000010`). It sets `--surface-alpha` on `<html>`; `html[data-bg]` rules in `app.css` make `bg-white`/`bg-gray-50` (cards, sidebar, header) use that alpha, with a frosted blur (`data-glass`) while transparent. Stays solid on purpose: inputs, modals/anything inside a `.fixed.inset-0` overlay, `shadow-lg+` popovers. Blur is skipped on any surface containing a `.fixed` or z-indexed `.absolute` child, because `backdrop-filter` traps fixed modals and clips dropdowns. The slider is disabled until a background is picked. Live preview via `wowloAppearance.opacity()`.
- **Security:** the stored value is user input, so `App\Support\AppBackgrounds::isValid()` only accepts `Category/file.ext` that exists on disk (no `..`, no deeper paths, allowed extension). A deleted file silently falls back to no background. Static images are public assets (not user data), so they live in `public/`, not R2.
- **Scope:** logged-in app layout only. Public pages and login stay light.

## Files
`SettingsController`, `App\Support\AppBackgrounds`, `views/settings/edit.blade.php`, `layouts/app.blade.php` (head script, `app-shell` class, menu link), `resources/css/app.css`, `tests/Feature/SettingsTest.php`.

## Later (not built)
- **Push notification toggles** (Phase 2 plan line: "user can choose which notifications to receive"). Notes for when it's wanted: all pushes are `NewHomeworkNotification` or `NewMessageNotification` (12 `->notify()` sites; messages mix chat with automated notices); push is best-effort, so muting should affect push only; tutors currently have no enable-push UI. Add as another card on this page.
- Upload-your-own backgrounds; a fine-tune for wash strength; dark-specific polish of the games' own colours.
