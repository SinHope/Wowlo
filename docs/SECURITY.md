# Wowlo — Security Guide

**Purpose:** security rules, practices and checklists for Wowlo. This app handles real personal data — names, addresses, phone numbers of minors and their parents, and payment records. A breach is not just a technical failure; it is a PDPA violation and a trust violation against families who trusted you with their children's data.

> Companion docs: [DATABASE.md](DATABASE.md) (schema rules + tenancy) · [FEATURE_CHANGES.md](FEATURE_CHANGES.md) (safe feature changes) · [SCALABILITY.md](SCALABILITY.md) (performance).
> Security guardrails in `CLAUDE.md` apply throughout — never read/echo `.env` or secrets; `.env` is never committed.

---

## 1. The one rule behind everything here

> **Never trust the client.** Every piece of data that comes from a browser — form inputs, URL parameters, headers, uploaded files — is potentially hostile. Validate, authorise, and recompute server-side. Always.

---

## 2. Authentication

### Email + Password
- Passwords are **bcrypt-hashed** by Laravel — never stored in plain text, never logged
- Minimum password length enforced at registration
- Brute-force protection: Laravel's built-in `ThrottleRequests` middleware on `/login` (limit login attempts per IP)
- **No public registration** — accounts are created by the tutor only. The registration route is disabled or removed entirely
- Forgot password sends a time-limited reset link to the user's real email — link expires after **10 minutes** (`config/auth.php` → `passwords.users.expire`)

### Google OAuth (Socialite)
- Only **verified** Google emails are accepted (`verified_email` must be true in Socialite response)
- Linking rule (see `Wowlo_v2_1_Refined_Decisions_and_Architecture.md` §3):
  1. Match by `google_id` → log in
  2. Match by email with null `google_id` → link and log in
  3. Email conflict (different `google_id`) → reject
  4. No account found → reject with friendly message, **never auto-create**
- `GOOGLE_CLIENT_SECRET` lives only in `.env` / Render dashboard — never in code or Git
- OAuth redirect URI must exactly match `GOOGLE_REDIRECT_URI` in `.env` — any mismatch breaks the flow and prevents login

### Sessions
- Sessions are server-side (database driver) — the browser only holds a signed session cookie
- Session cookie is `HttpOnly` (JS cannot read it), `Secure` (HTTPS only), `SameSite=Lax`
- `APP_KEY` must be a strong random key — never reuse between environments
- Fee unlock is stored as a **session flag** (`fee_unlocked`; the route is guarded by the `fee.unlocked` middleware) — it expires when the session ends, so parents must re-enter the password each browser session

---

## 3. Authorisation & Tenant Isolation

This is the highest-risk area. A bug here exposes one family's data to another.

### Role-based access
| Role | What they can access |
|---|---|
| `super_admin` | Everything across all tutors |
| `tutor` | Only their own students, homework, quizzes, bills, exam papers |
| `student` | Only their own data — never another student's |

- `RoleMiddleware` protects all routes — students cannot reach tutor routes by guessing a URL
- Tutor routes are prefixed `/tutor/*`, student routes `/student/*` — no overlap
- Every controller action checks ownership before acting on a record

### Tenant scoping rules (never skip these)
- `tutor_id` is **always set server-side** from `auth()->id()` — never from request input
- Validation rules never include `tutor_id` so `$request->validated()` always strips any client-supplied value
- Every list query filters by the acting tutor: `where('tutor_id', auth()->id())`
- Route-model binding returns **404** (not 403) on a record the user doesn't own — IDs don't leak
- Add isolation tests in `tests/Feature/MultiTutorTest.php` for every new cross-tenant surface

### Fee section
- Hidden from students by default
- Protected by a global password (`FEE_VIEW_PASSWORD` in `.env`) — compared with `hash_equals()` (constant-time, prevents timing attacks)
- This is UI-level protection to stop students seeing fees on a shared device — it is not a substitute for role-based access control
- ✅ **Brute-force protected:** the fee-unlock POST (`fees.unlock.attempt`) is rate-limited (`throttle:5,1` — 5 attempts/min per user) so the shared static password can't be brute-forced on a device.

---

## 4. Input Validation & Injection Prevention

### SQL Injection
- **Never write raw SQL** in controllers — always use Laravel Eloquent or the Query Builder with parameter binding
- If raw SQL is ever needed (e.g. `CREATE INDEX CONCURRENTLY`), use `DB::statement()` with no user input interpolated

### XSS (Cross-Site Scripting)
- Blade templates auto-escape output with `{{ }}` — never use `{!! !!}` (unescaped) on user-supplied data
- If rich text is ever needed in future, sanitise with a server-side HTML purifier before storing

### CSRF (Cross-Site Request Forgery)
- Laravel's `VerifyCsrfToken` middleware is active on all state-changing routes (POST/PUT/PATCH/DELETE)
- Every Blade form must include `@csrf`
- API endpoints (if ever added) use Sanctum token auth instead of session, which has its own CSRF protection

### Mass Assignment
- All models define an explicit fillable allow-list (`#[Fillable([...])]`) — never `$guarded = []`
- **Important nuance for this codebase:** `tutor_id`, `role` and `google_id` *are* in `User`'s fillable list, but they are protected a different way — the request **validation rules never include them**, so `$request->validated()` strips any client-supplied value, and they are set server-side only (`auth()->id()`, the chosen role, the OAuth id). Net effect is the same: a client can never set them. (See the comment on `User::$fillable` and the tenancy rules in `CLAUDE.md`.)

---

## 5. File Upload Security

File uploads (homework attachments, exam papers, quiz diagrams) are high-risk. A malicious file can compromise the server or other users.

### Rules for every file upload
- **Allow-list mime types only** — and the list is enforced **per upload type** in its FormRequest (`mimes:` rule), not one global list. Actual rules today:

  | Upload | FormRequest | Allowed types | Max size |
  |---|---|---|---|
  | Homework attachment | `HomeworkRequest` | `pdf, doc, docx, jpg, jpeg, png` | **25 MB** (`max:25600`) |
  | Exam paper | `ExamPaperRequest` | `pdf, jpg, jpeg, png` | **10 MB** (`max:10240`) |
  | Quiz question image | `QuizRequest` | `pdf, jpg, jpeg, png, gif, webp` | **10 MB** (`max:10240`) |

- **Hashed filename on storage:** uploads use Laravel's `$file->store('<dir>', 'r2')`, which generates a random hashed object key (`hashName()`) — the original filename is never the storage key (stored separately as `original_filename`/`attachment_name` for display only). This prevents path-traversal and collisions.
- **Private R2 bucket:** files are never served by a public URL.
- **Downloads stream through an authorized controller route** — e.g. `Storage::disk('r2')->download($key, $displayName)` — *after* an ownership/assignment check. There are **no public or presigned URLs** in the app, so there is nothing to leak with a TTL; access is re-checked on every request by the authenticated route.
- **Never execute uploaded files** — validate, store to R2, done. No server-side processing of file contents.

---

## 6. Secrets & Environment Security

### The non-negotiables
- `.env` is **never committed to Git** — it is in `.gitignore`
- `.env.example` contains only placeholder values — never real secrets
- `CLAUDE.md` enforces this: Claude Code never reads or echoes secrets from `.env`
- All production secrets live in **Render's environment panel** only

### Secret rotation
If a secret is ever accidentally committed or exposed:
1. Rotate it immediately (new key/token from the provider)
2. Update it in Render's dashboard
3. Redeploy
4. Check Git history — if it was committed, rewrite history and force-push (and assume it was compromised)

### Key secrets and where they live
| Secret | Where | Notes |
|---|---|---|
| `APP_KEY` | Render env | Rotate if ever exposed; changing it invalidates all sessions |
| `GOOGLE_CLIENT_SECRET` | Render env | Rotate in Google Cloud Console |
| `R2_ACCESS_KEY_ID` / `R2_SECRET_ACCESS_KEY` | Render env | Rotate in Cloudflare dashboard |
| `FEE_VIEW_PASSWORD` | Render env | Changing requires a redeploy |
| `VAPID_PRIVATE_KEY` | Render env | Rotation invalidates all push subscriptions — users must re-subscribe |
| `RESEND_API_KEY` | Render env | Rotate in Resend dashboard |
| Neon `DATABASE_URL` | Render env | Use pooled connection string |

---

## 7. HTTPS & Security Headers

### HTTPS
- Render provides **automatic SSL** on all subdomains and custom domains — no setup needed
- `APP_URL` must always start with `https://` in production
- `APP_DEBUG=false` in production — never expose stack traces to users

### Security headers (add to nginx config or Laravel middleware)
These headers protect against common browser-based attacks:

```
X-Frame-Options: SAMEORIGIN          — prevents clickjacking
X-Content-Type-Options: nosniff      — prevents MIME sniffing
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: (configure per your needs)
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

> If using the Dockerfile (nginx + php-fpm), add these to the nginx server block. Alternatively use the `bepsvpt/secure-headers` Laravel package.

---

## 8. PDPA Compliance (Singapore)

Wowlo collects personal data of minors and their parents. This falls under Singapore's **Personal Data Protection Act (PDPA)**.

### What you collect and why
| Data | Purpose | Stored where |
|---|---|---|
| Name, email, address | Account identification, communication | Neon (PostgreSQL) |
| Phone numbers (up to 5) | Parent/student contact | Neon (PostgreSQL) |
| Homework, quiz results | Academic tracking | Neon (PostgreSQL) |
| Payment records | Tuition fee management | Neon (PostgreSQL) |
| Uploaded files | Homework/exam materials | Cloudflare R2 (private) |

### PDPA requirements you must follow
- **Purpose limitation:** data collected for tuition management only — never sold, shared with third parties, or used for anything else
- **Access control:** students only see their own data; parents see their child's data; tutors see only their own students
- **Privacy Policy:** accessible at `/privacy-policy` (route name `privacy-policy`) — already built. Keep it up to date
- **Data retention:** define how long you keep data after a student leaves. Reasonable: 1 year after last active lesson, then delete or anonymise
- **Right to access/delete:** if a parent requests their data or deletion, you must comply. Build a process for this (even if it's a manual database operation for MVP)
- **Breach notification:** if a data breach occurs, you must notify the PDPC and affected individuals within **3 days** if there is significant harm likely
- **Contact:** the Privacy Policy must include a contact email for data queries (`CONTACT_EMAIL` in `.env`)

---

## 9. Push Notification Security

- **VAPID keys** prove push notifications came from your server — keep `VAPID_PRIVATE_KEY` secret
- **`VAPID_PUBLIC_KEY`** is safe to expose in a `<meta>` tag — it is designed to be public
- Push subscriptions are stored in `push_subscriptions` table, scoped to `user_id`
- A student can only subscribe/unsubscribe their own device — the controller verifies `auth()->id()` matches the subscription owner
- Push failures are caught and logged — they **never** break the request that triggered them (`try/catch` + `report()`)

---

## 10. WhatsApp Billing Security

- The WhatsApp billing generator produces a **text message for the tutor to copy** — no data is sent directly to WhatsApp via API
- Bill totals are **always recomputed server-side** — the server never trusts a grand total submitted by the client
- `outstanding_balance` is derived from the ledger (`Σ bills − Σ payments`), never a client-submitted value
- `tutor_id` on bills is set from `auth()->id()` — a tutor cannot generate a bill attributed to another tutor

---

## 11. Monitoring & Incident Response

### Logging
- `storage/logs/laravel.log` captures all errors, exceptions and custom log calls
- In production, check this log after every deploy and smoke test
- **Never log sensitive data** — no passwords, no full card numbers, no `google_id` values, no file contents
- Consider forwarding logs to a service like **Logtail** (has a free tier) for searchable, persistent log storage — Render's local log storage is ephemeral

### UptimeRobot
- Already configured — 5-minute HTTP monitor on `https://wowlo.onrender.com`
- Alerts you by email if the app goes down
- Also serves as the keep-alive ping to prevent cold starts

### If something goes wrong — incident checklist
1. **App is down** → check Render dashboard for deploy errors; check `laravel.log`
2. **Data looks wrong** → never edit production DB directly; restore from Neon point-in-time backup
3. **Suspected breach** → rotate all secrets immediately; check access logs; notify PDPC within 3 days if significant harm is likely; notify affected users
4. **Bad deploy** → redeploy previous commit in Render dashboard (takes ~2 minutes)
5. **Bad migration** → restore from Neon backup taken before the migration (see DATABASE.md §6)

---

## 12. Dependency Security

- Keep Laravel and all Composer/npm packages updated — security patches are released regularly
- Run `composer audit` periodically to check for known vulnerabilities in PHP dependencies
- Run `npm audit` for frontend dependencies
- Subscribe to [Laravel security announcements](https://laravel.com/docs/security) — critical patches are rare but important
- Never install packages from unknown sources — stick to well-maintained, widely-used packages

---

## 13. Security Checklist — Before Every Production Deploy

- [ ] `APP_DEBUG=false` in Render env
- [ ] `APP_ENV=production` in Render env
- [ ] No secrets in `.env.example` or Git history
- [ ] All file uploads validated (mime type + size)
- [ ] All new routes protected by appropriate middleware
- [ ] Any new model has `$fillable` defined (no `$guarded = []`)
- [ ] Any new cross-tenant feature has an isolation test in `MultiTutorTest.php`
- [ ] `php artisan test` green
- [ ] Neon backup taken before running migrations
- [ ] Check `laravel.log` after deploy

---

## 14. Security Checklist — Before Going Live With Real Students

- [ ] Privacy Policy is live at `/privacy-policy` and accurate
- [ ] `FEE_VIEW_PASSWORD` is set to a strong value (not a default)
- [ ] Google OAuth redirect URI matches production `APP_URL` exactly
- [ ] R2 bucket is private (no public access)
- [ ] `APP_DEBUG=false` confirmed
- [ ] HTTPS working on production URL
- [ ] All three roles tested (super_admin, tutor, student) — confirm isolation
- [ ] File upload + download tested end-to-end on production
- [ ] UptimeRobot monitor live and sending alerts to your email
- [ ] Inform parents their data is protected and share the Privacy Policy link

---

*Wowlo — Security Guide. Read before writing any code that touches auth, data access, file uploads, or payments.*
