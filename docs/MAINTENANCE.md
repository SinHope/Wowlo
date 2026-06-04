# Wowlo — Maintenance Guide

**Purpose:** routine tasks to keep Wowlo healthy, fast, and secure after it goes live. Not everything needs daily attention — this doc tells you what to check, when, and how.

> Companion docs: [SECURITY.md](SECURITY.md) · [DATABASE.md](DATABASE.md) · [SCALABILITY.md](SCALABILITY.md) · [INCIDENT_RESPONSE.md](INCIDENT_RESPONSE.md)

---

## 1. Maintenance schedule at a glance

| Frequency | Task |
|---|---|
| After every deploy | Check logs, smoke test, verify UptimeRobot |
| Weekly | Check error logs, storage usage, failed jobs |
| Monthly | Package security audit, Neon backup check, UptimeRobot alert check |
| Before any migration | Manual Neon backup |
| When adding a tutor | Create account, verify isolation, onboard |

---

## 2. After every deploy

### Check the logs
```bash
# If you have SSH / Render shell access:
tail -n 100 storage/logs/laravel.log

# Or check via Render's log viewer in the dashboard
```
Look for: `ERROR`, `CRITICAL`, `Exception`, anything unexpected. A clean deploy has only `INFO` level entries.

### Smoke test (5 minutes)
- Log in as super_admin, tutor, and student
- Create one homework → check student sees it
- Check billing page loads correctly
- Upload one file → check it downloads
- Check `laravel.log` shows no new errors

### Verify UptimeRobot
- Confirm the monitor is still green after deploy
- If it went red during deploy → check Render build logs

---

## 3. Weekly checks

### Error log review
- Open `storage/logs/laravel.log` (or Logtail if set up)
- Search for `ERROR` and `CRITICAL` entries
- Common non-critical errors to ignore: `TokenMismatchException` (user submitted expired form — harmless), occasional push notification delivery failures
- Investigate anything else — especially database errors or auth failures

### Storage usage (Cloudflare R2)
- Log into Cloudflare dashboard → R2 → your bucket
- Check total storage used — free tier is 10 GB
- If approaching 8 GB, plan to either archive old files or upgrade

### Render service health
- Log into Render dashboard
- Check the service is running (green status)
- Check memory usage — if consistently near the free tier limit, consider upgrading to Starter ($7/month)
- Check deploy history — confirm no failed deploys went unnoticed

### Neon database health
- Log into Neon dashboard
- Check compute usage — free tier has limited active compute hours
- Check storage size — free tier is 0.5 GB
- If approaching limits, archive old data or upgrade

---

## 4. Monthly tasks

### Package security audit
```bash
# Check PHP dependencies for known vulnerabilities
composer audit

# Check frontend dependencies
npm audit

# Update packages (test first!)
composer update --dry-run   # see what would change
npm outdated                # see outdated packages
```

> Never update packages directly on production. Update locally → run `php artisan test` → if green, commit and deploy.

### Laravel version check
- Check [laravel.com/docs](https://laravel.com/docs) for security releases
- Check [github.com/laravel/framework/releases](https://github.com/laravel/framework/releases)
- Apply security patches promptly — they are rare but important

### UptimeRobot alert check
- Log into UptimeRobot dashboard
- Check alert history for the past month
- Any downtime episodes? Investigate root cause
- Confirm your alert email is still correct and receiving alerts

### Neon backup verification
- Log into Neon dashboard → Backups
- Confirm point-in-time restore is enabled
- Optionally do a test restore into a scratch database to verify the backup is usable (never restore over production to test)

---

## 5. Database maintenance

### Before any schema change (migration)
Always take a manual backup first:
```bash
# Copy the pooled connection string from Neon dashboard (never paste secrets here)
pg_dump "$DATABASE_URL" --no-owner --format=custom --file="backup-$(date +%F).dump"
```

### Checking for slow queries
If any page feels slow:
```sql
-- In Neon's SQL editor, prefix the slow query with EXPLAIN ANALYZE:
EXPLAIN ANALYZE SELECT * FROM homeworks WHERE tutor_id = 1 ORDER BY created_at DESC;
```
If you see `Seq Scan` on a large table, you're missing an index. Add one — see SCALABILITY.md §2.

### Expired sessions
If using the database session driver, Laravel **garbage-collects expired sessions automatically** via a per-request lottery (`config/session.php` → `'lottery' => [2, 100]`). There is **no** `php artisan session:gc` command and nothing to schedule — leave it alone. (If the `sessions` table ever grows oddly large, check that `SESSION_LIFETIME` is sane rather than pruning by hand.)

### Clearing expired password reset tokens
```bash
php artisan auth:clear-resets
```
Run monthly — expired tokens have no value and add unnecessary rows.

---

## 6. Storage maintenance (Cloudflare R2)

### Orphaned files
Over time, files may be deleted from the database but remain in R2 (e.g. if a homework was deleted without cleaning up the attachment). Periodically check for orphaned files:

1. Get all `file_path` values from `homeworks` and `exam_papers` tables
2. Compare against R2 bucket contents
3. Delete R2 objects not referenced by any database row

This is a manual check for MVP. In Phase 2, wire up model observers to delete R2 objects when records are deleted.

### File size monitoring
Cloudflare R2 dashboard shows per-object sizes. Upload limits are enforced **per type** (homework 25 MB; exam papers & quiz images 10 MB — see SECURITY.md §5). A file larger than 25 MB shouldn't exist; if one does, investigate whether the `max:` rule was bypassed.

---

## 7. Render free tier management

### Cold starts
- UptimeRobot pings every 5 minutes → prevents the 15-minute idle sleep
- If UptimeRobot goes down or misconfigured, users may hit cold starts (30-60 second wait)
- Check UptimeRobot weekly to confirm it's running

### When to upgrade to Render Starter ($7/month)
Upgrade when any of these are true:
- Real students complain about slowness (cold starts getting through)
- You have more than ~5 active tutors with regular daily usage
- You're collecting fees and the app is business-critical
- Memory usage on the free tier is consistently near the limit

### Deploy management
- Render auto-deploys on every push to `main`
- If you need to prevent an auto-deploy (e.g. mid-feature work), use a feature branch and only merge to `main` when ready
- Render keeps the last few deploys — you can roll back in the dashboard in ~2 minutes

---

## 8. User management

### Adding a new tutor (current: testing phase — manual only)
1. Log in as `super_admin`
2. Create the tutor account with their real email
3. Set a temporary password (e.g. `Wowlo2026!`)
4. WhatsApp them: *"Your Wowlo login: [email] / password: Wowlo2026! Please change it after first login."*
5. Verify they can log in and only see their own data (not other tutors' students)
6. Remind them to use Google OAuth for easier future logins

> **Phase 2 (public tutor sign-up):** later, tutors will self-register instead of being created here. By design that's purely additive — a self-registered tutor is just `role=tutor, tutor_id=null`, identical to an admin-created one, and students stay tutor-added. Existing accounts are unaffected. Full plan: [public-tutor-sign-up.md](public-tutor-sign-up.md) (also architecture decision MT5).

### Removing a student or tutor
- Deleting a **student** cascades to their records (homework, messages, fees, payments, bills, quiz attempts/assignments — all `cascadeOnDelete`)
- Deleting a **tutor** who still has students is **blocked** — `users.tutor_id` is `restrictOnDelete` and the admin UI guards it too. Reassign or remove their students first
- For PDPA: a parent who requests data deletion must have all their data removed. For MVP, do this manually via database:
  ```bash
  # In Neon SQL editor — replace the ID with the actual user ID
  DELETE FROM users WHERE id = [student_id];
  # Cascades should handle related records if foreign keys are set up with ON DELETE CASCADE
  ```
- Also delete their files from R2 manually (no auto-cleanup in MVP)

### Password resets
If a student/parent can't log in:
1. Check their email is correct in the database
2. Use the "Forgot Password" flow — sends a reset link to their email
3. If they don't receive the email, check Resend dashboard for delivery status
4. As a last resort, use `php artisan tinker` to manually set a temporary password:
   ```php
   $user = App\Models\User::find([id]);
   $user->password = bcrypt('TemporaryPassword123!');
   $user->save();
   ```

---

## 9. Monitoring setup checklist (do once, check monthly)

- [ ] UptimeRobot monitor active on `https://wowlo.onrender.com` (5-min interval)
- [ ] UptimeRobot alerts going to your email
- [ ] Neon point-in-time restore enabled (on by default)
- [ ] Render email notifications enabled for failed deploys
- [ ] `APP_DEBUG=false` in Render env (check this — it can accidentally get switched)
- [ ] Logtail or similar connected for persistent log storage (optional but recommended)

---

*Wowlo — Maintenance Guide. A maintained app is a trustworthy app.*
