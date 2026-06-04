# Wowlo — Slice 11 Deployment Runbook

**Goal:** deploy Wowlo to production safely, on the confirmed setup below, without putting any existing data at risk.

**Confirmed choices**
- **Host:** Render
- **Tier:** Free plan + UptimeRobot keep-alive (revisit → Starter ~$7/mo always-on once tutors depend on it)
- **Launch domain:** `wowlo.onrender.com` first; move to the permanent domain (`wowlo.com` / `wowlo.app`) later
- **PWA installs:** suppressed during the subdomain phase, switched on at the permanent-domain cutover (see §8)

> Companion docs: [DATABASE.md](DATABASE.md) · [FEATURE_CHANGES.md](FEATURE_CHANGES.md) · [SCALABILITY.md](SCALABILITY.md).
> Security guardrails (never read/echo `.env` or secrets; `.env` never committed) live in `CLAUDE.md` and apply throughout.

---

## Key mechanism note

**Render runs PHP via a Docker image** — PHP is not a native Render runtime. So the first step is a production `Dockerfile` (nginx + php-fpm) plus a `render.yaml`. Everything after that is configuration.

---

## Steps

### 1. Containerize the app ✅ DONE (committed)
- `Dockerfile` — multi-stage: Vite asset build (node) → `serversideup/php:8.4-fpm-nginx`, `composer install --no-dev --optimize-autoloader`. serversideup runs `migrate --force` + config/route/view cache **on boot** (via `AUTORUN_*`), after Render injects env.
- `.dockerignore` + `render.yaml` (Render Blueprint).
- **Hardening baked in:** trust proxies + force-HTTPS in production; `SecurityHeaders` middleware (X-Frame-Options, nosniff, Referrer-Policy, Permissions-Policy) with a test.
- nginx listens on **8080** (serversideup default) — Render auto-detects; if not, set the port to 8080 in the dashboard.

### 2. Create the Render Web Service
- Push to GitHub, then Render → **New → Blueprint** (it reads `render.yaml`) — or New → Web Service → Docker. **Free** plan, Singapore.
- Render builds from the `Dockerfile` automatically.

### 3. Set environment variables (in Render's dashboard)
Set the keys below in Render. **Copy the *values* from your local `.env` yourself** — they are never read or echoed here. (Cross-check against `.env.example` for the full set your app expects.)

Production-specific:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://wowlo.onrender.com`
- `PWA_PROMOTE_INSTALL=false`  ← keep installs suppressed on the throwaway domain
- `APP_KEY` (same key as local, or generate one for prod)

Database (use Neon's **pooled** connection string — the host with `-pooler`):
- `DB_CONNECTION=pgsql`
- **`DB_URL`** = the Neon pooled connection string (the app reads `DB_URL`, not `DATABASE_URL`)
- `DB_SSLMODE=require`

Storage (Cloudflare R2):
- `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_ENDPOINT`

Web push (VAPID):
- `VAPID_SUBJECT`, `VAPID_PUBLIC_KEY`, `VAPID_PRIVATE_KEY`

Auth (Google OAuth):
- `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI=https://wowlo.onrender.com/auth/google/callback`

Mail (Resend):
- `MAIL_MAILER=resend`, `RESEND_API_KEY`, `MAIL_FROM_ADDRESS` (stays on the `resend.dev` fallback until the real domain is verified), `MAIL_FROM_NAME`

App config:
- `CONTACT_EMAIL`, `FEE_VIEW_PASSWORD`, `PAYNOW_NUMBER`, `WOWLO_CURRENCY` (as needed)

### 4. First deploy + database migration
- Deploy the service.
- **Back up Neon first** (dashboard point-in-time is on; optionally `pg_dump` — see DATABASE.md §6).
- Run `php artisan migrate --force` (additive migrations only; safe).

### 5. Google OAuth callback
- In Google Cloud Console, add `https://wowlo.onrender.com/auth/google/callback` as an authorized redirect URI.
- Login via Google breaks until this exactly matches `GOOGLE_REDIRECT_URI`.

### 6. UptimeRobot
- Add a 5-minute HTTP(s) monitor on `https://wowlo.onrender.com`.
- Keeps the free service warm (avoids cold-start delay) and alerts on downtime.

### 7. Post-deploy smoke test
- Log in as **super_admin**, **tutor**, and **student** — confirm tenant isolation holds.
- Test: a file upload (homework/exam paper), a quiz attempt, the contact form email, and a billing calculation.
- Check `storage/logs/laravel.log` for errors.

### 8. Permanent-domain cutover (later)
When `wowlo.com` / `wowlo.app` is ready:
- Add the custom domain in Render; point DNS.
- Update `APP_URL` and the Google OAuth redirect URI to the new domain.
- Verify the domain in Resend; switch `MAIL_FROM_ADDRESS` to `hello@wowlo.app`.
- Flip **`PWA_PROMOTE_INSTALL=true`** — now invite installs, on the origin that lasts.
- (At growth) switch to Neon pooled compute / paid tier, Render Starter always-on; see SCALABILITY.md.

---

## Rollback
- **Bad code:** redeploy the previous commit in Render.
- **Bad migration:** restore from the Neon backup / point-in-time. (Additive migrations rarely need this — old code tolerates new nullable columns.)

## Pre-flight checklist
- [ ] `php artisan test` green
- [ ] `Dockerfile` builds locally
- [ ] All env vars set in Render (`APP_DEBUG=false`, `PWA_PROMOTE_INSTALL=false`)
- [ ] Neon backup taken
- [ ] Google OAuth redirect URI matches `APP_URL`
- [ ] UptimeRobot monitor live
- [ ] Smoke test passed across all three roles
