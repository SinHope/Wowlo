# Debugging

Quick reference for tracking down bugs in Wowlo. For production incidents use [INCIDENT_RESPONSE.md](INCIDENT_RESPONSE.md); for test commands see [TESTING.md](TESTING.md). Add new gotchas at the bottom of "Known gotchas" as we hit them.

## First moves
1. **Read the log:** `storage/logs/laravel.log` (newest error at the bottom). Never paste secrets from it into chat.
2. **Reproduce in a test** if it's logic/auth/money: `php artisan test --filter=SomeName`.
3. **Clear caches** if config/routes/views look stale: `php artisan optimize:clear`.
4. **Frontend (Alpine/Tailwind) not updating?** Rebuild: `npm run build`, then hard-refresh (Ctrl+Shift+R). Check the browser console for Alpine errors.
5. **Route 404/405?** `php artisan route:list --path=<segment>`.

## Useful commands
- `php artisan test` — whole suite (in-memory SQLite, never touches Neon)
- `php artisan tinker` — poke at models
- `php artisan route:list` — all routes
- `php artisan migrate:status` — which migrations ran

## Known gotchas
- **DB connection fails / SSL handshake error:** turn off Proton VPN. Neon free tier also scales to zero, so the first request can be slow — retry.
- **Google login redirect mismatch:** the OAuth redirect URI must exactly match the one registered in Google Cloud (see memory note on local dev).
- **A form submits when Enter is pressed:** forms with a confirm modal or JS validation need `@keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"` on the `<form>`.
- **Page shows up completely unstyled (no CSS/JS):** a stale `public/hot` file exists (left by `npm run dev`), so Laravel looks for a Vite dev server that isn't running. Delete `public/hot` (or run `npm run dev`), then hard-refresh. `npm run build` alone doesn't remove it.
- **Alpine page renders blank/empty (dropdowns, lists not filled) after editing a Blade file:** check `<script>` blocks for Blade tags (`<x-...>`, `@directive`) hiding in JS comments/strings — Blade compiles them and injects HTML into the JS. Never write `<x-foo />` or `@foo` inside a script comment.
- **`pest tests/Browser` just sits there with no output:** a stuck Playwright run left orphaned `php` / `node` (playwright `run-server`) / `chrome-headless-shell` processes, and the next run hangs. Browser tests print nothing until they finish, so give a normal run ~15s first. If it's stuck: press Ctrl+C, then in PowerShell run `Get-CimInstance Win32_Process | Where-Object { $_.CommandLine -match 'playwright|pest.*tests/Browser' } | ForEach-Object { Stop-Process -Id $_.ProcessId -Force }` and re-run. Run one file or `--filter` at a time.
- **Alpine element flashes on load:** add `x-cloak` (the `[x-cloak]` style is in the layout).
- **404 on a record you own:** tenancy checks return 404 (not 403) when `tutor_id` doesn't match the acting user — check you're logged in as the right tutor.
- **Wrong timestamps:** app timezone is `Asia/Singapore`; don't hardcode offsets.

- **A page errors (400s/500s):** users now see a friendly standalone error page (`resources/views/errors/*`: 403, 404, 419, 429, 500, 503 + `4xx`/`5xx` fallbacks) — it never shows exception details. The real error is in `storage/logs/laravel.log` (Render logs in production). Production must run with `APP_DEBUG=false` — **check this yourself in the Render environment panel** (Claude never reads `.env`). The error pages are deliberately standalone (no sidebar / auth / DB), so they still render when the database is down. A 419 on Save = the session expired; the Attendance page offers to restore the unsaved ticks.
- **Browser (E2E) tests hang with no output:** run them one at a time with `--filter="..."` and write output to a file; a test that waits on an element that never appears (e.g. clicking text that matches a hidden pop-up) can stall for a long time. Use specific CSS selectors (`[aria-label="3 October 2026"]`, `[name="lessons[0][hours]"]` — a bare `lessons[0][hours]` is an invalid CSS selector).
- **Nav spinner stuck after Back button:** the overlay lives on the layout root with `@pageshow.window="busy = false"` — keep that listener or the browser's back-forward cache restores the page mid-spinner.
- **`position: fixed` overlay stuck inside the sidebar:** the `<aside>` has a CSS transform, which traps fixed children. The nav spinner overlay therefore sits OUTSIDE `<aside>`, at the top of the layout root.
- **Attendance dates not matching on SQLite (tests):** `Attendance.lesson_date` is cast `date:Y-m-d` so it stores a bare `Y-m-d` string; a plain `date` cast stores `... 00:00:00` on SQLite and `whereIn('lesson_date', ['2026-06-03'])` stops matching.

- **A pre-selected `<select>` shows blank when its `<option>`s come from `x-for`:** `x-model` runs before the options exist. Re-sync with `x-init="$nextTick(() => { $el.value = String(model) })"` (done on the billing form's student dropdown).

## Bug log (add entries as we fix things)
| Date | Symptom | Cause | Fix |
|---|---|---|---|
| 2026-11-12 | Saved bills couldn't be corrected | No edit/delete flow | Added edit/update/destroy on `BillController` |
| 2026-11-12 | Whole app unstyled after `npm run build` | Stale `public/hot` from `npm run dev` | Deleted `public/hot` |
| 2026-11-13 | WhatsApp billing form empty (no students, no lesson row) | A `<x-spinner-overlay />` mentioned in a JS comment got compiled by Blade into the script | Reworded the comment |
| 2026-11-13 | Generating error views from a shell script broke 403/404/429/500 | Unescaped apostrophes inside single-quoted PHP strings in `@section(...)` | Switched those strings to double quotes |
| 2026-11-13 | Pre-filled bill form (from Attendance) showed a blank student dropdown and blocked saving ("Please select an item") | `x-model` on the `<select>` ran before the `x-for` options rendered | `x-init="$nextTick(...)"` re-sync in `billing/partials/form.blade.php`; E2E test asserts the dropdown value |

- **Dark mode: something is unreadable.** Dark mode is a remap, not `dark:` classes. Text tokens `ink`/`muted`/`cream` are CSS variables (`tailwind.config.js`); stock classes (`bg-white`, `gray-*`, a few tints) are remapped in the `.dark` block of `resources/css/app.css`. If a new view uses a colour that isn't remapped (e.g. `bg-sky-50`, `text-indigo-700`), add it there, keep text >= 4.5:1 on `#211C2C`, then `npm run build`.
- **`bg-amber` (no shade) renders nothing by default.** Tailwind's amber has no DEFAULT; we added `amber.DEFAULT` in the config. Same trap for any other bare palette name.
- **Scripted file edits on Windows:** Python's default write encoding is cp1252. Use `python -X utf8`, build the new text before opening for write, and check that every `replace()` actually matched.
| 2026-11-14 | `pest tests/Browser/BillingFlowTest.php` hung with no output | Orphaned Playwright/Chromium processes from earlier stalled runs | Killed them, re-ran clean (5 passed) |
