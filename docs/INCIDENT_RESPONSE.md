# Wowlo — Incident Response Guide

**Purpose:** exactly what to do when something goes wrong. Stay calm, follow the steps, fix it. Every scenario here has a clear action list.

> Companion docs: [SECURITY.md](SECURITY.md) · [DATABASE.md](DATABASE.md) · [MAINTENANCE.md](MAINTENANCE.md)

---

## 0. General principle — stay calm, don't make it worse

When something breaks in production:
1. **Diagnose first, act second.** Understand what's wrong before touching anything
2. **Don't make live edits to the database directly** unless it's the only option and you have a backup
3. **Rollback is always an option** — a working old version is better than a broken new one
4. **Communicate** — if students/parents are affected, send a quick WhatsApp: *"Wowlo is experiencing a brief issue. We'll be back shortly. Sorry for the inconvenience!"*

---

## 1. App is down / not loading

**Symptoms:** UptimeRobot alert, browser shows error, users can't access the app.

**Step 1 — Check Render dashboard**
- Log into [render.com](https://render.com)
- Is the service running (green status)?
- Is there a failed deploy? Check deploy logs for build errors

**Step 2 — Check the logs**
- Render dashboard → your service → Logs tab
- Look for crash errors, out-of-memory, missing env vars

**Step 3 — Common causes and fixes**

| Cause | Fix |
|---|---|
| Failed deploy (build error) | Roll back to previous deploy in Render dashboard (2 minutes) |
| Missing env var | Add the missing var in Render env panel → redeploy |
| Database connection failed | Check Neon dashboard — is the database running? Check `DATABASE_URL` is correct and uses pooled connection |
| Out of memory (free tier) | Restart service in Render dashboard; consider upgrading to Starter |
| R2 connection failed | Check Cloudflare R2 keys in Render env — verify they haven't expired |
| Cold start (slow, not down) | UptimeRobot may have paused — check and restart the monitor |

**Step 4 — Roll back if needed**
- Render dashboard → your service → Deploys → find the last working deploy → click "Redeploy"
- Takes ~2 minutes. Users are back immediately after.

---

## 2. Bad deploy — new code broke something

**Symptoms:** app loads but a feature is broken, errors in logs after a deploy.

**Immediate fix:**
1. Render dashboard → Deploys → click "Redeploy" on the previous commit
2. Takes ~2 minutes — users are back on the working version
3. Diagnose the bug locally, fix it, test it, deploy the fix as a new commit

**If a migration was also part of the bad deploy:**
- Rolling back the code does NOT roll back the migration
- If the migration was additive (new nullable column, new table) → old code tolerates it, rollback is safe
- If the migration was destructive (dropped or renamed a column) → see §3 (Bad Migration)

---

## 3. Bad migration — data looks wrong or migration failed

**This is the most serious non-security incident. Act carefully.**

**Step 1 — Stop the bleeding**
- If the migration is still running, do NOT interrupt it mid-way (can leave the DB in a corrupt state)
- If it's done and data is wrong, do NOT run more migrations yet

**Step 2 — Assess the damage**
- Log into Neon SQL editor
- Check the affected table — are rows missing? Is a column wrong?
- Check `laravel.log` for the exact error

**Step 3 — Restore from backup (if data is lost or corrupted)**
- Neon dashboard → Branches → your database → Point-in-time restore
- Restore to a timestamp **before** the bad migration ran
- Restore into a **new branch** first — verify the data looks correct
- Only then restore over production (Neon makes this a safe operation)

**Step 4 — Fix the migration**
- Write a new corrective migration (never edit the already-ran one — see DATABASE.md rule 1)
- Test locally
- Deploy with the fix

**Step 5 — Verify**
- Smoke test all affected features
- Check `laravel.log` is clean
- Run `php artisan test`

---

## 4. Data looks wrong (not from a migration)

**Symptoms:** a student sees wrong homework, a bill is calculating incorrectly, a quiz shows wrong marks.

**Step 1 — Reproduce it**
- Ask the affected user exactly what they did and what they saw
- Try to reproduce with a test account before touching production data

**Step 2 — Check the logs**
- `laravel.log` — any errors around the time it happened?
- Neon SQL editor — inspect the affected rows directly

**Step 3 — Fix options**

| Scenario | Fix |
|---|---|
| Wrong data was saved (logic bug) | Fix the code, redeploy, manually correct the row in Neon if needed |
| Bill calculation wrong | Bills snapshot totals — the code computes from ledger data. Fix the ledger (add a correcting payment row) rather than editing the bill |
| Quiz marked incorrectly | Check `quiz_answers` rows — manually correct `is_correct` and `marks_awarded` in Neon SQL editor if needed |
| Student sees wrong data (isolation bug) | **Critical** — see §6 (Data Isolation Breach) |

**Step 4 — Never edit money or marks without a paper trail**
- Before editing any financial row, take a screenshot of the current state
- Log what you changed and why (a comment in Slack, WhatsApp, anywhere)
- Inform the affected parent if a payment record was corrected

---

## 5. File upload / download broken

**Symptoms:** uploads fail, files can't be downloaded, attachments show as broken.

**Step 1 — Check R2 credentials**
- Render env panel — verify `R2_ACCESS_KEY_ID`, `R2_SECRET_ACCESS_KEY`, `R2_BUCKET`, `R2_ENDPOINT` are all present and correct
- Cloudflare dashboard — verify the R2 bucket exists and the access key is active (not revoked)

**Step 2 — Check the error**
```bash
# In laravel.log, look for:
# - "S3Exception" (R2 connection issue)
# - "File too large" (upload size limit)
# - "Mime type not allowed" (validation rejection)
```

**Step 3 — Common fixes**

| Cause | Fix |
|---|---|
| R2 key expired or revoked | Generate new R2 API key in Cloudflare → update Render env → redeploy |
| Wrong R2 endpoint | Check Cloudflare dashboard for correct endpoint URL (format: `https://<accountid>.r2.cloudflarestorage.com`) |
| Bucket doesn't exist | Create the bucket in Cloudflare R2 dashboard |
| Download returns 403/404 | Ownership check — the user doesn't own (or isn't assigned) that file. Downloads **stream through the controller** after an auth + ownership check; there are no presigned URLs. Confirm the record really belongs to that user |
| Upload too large | Not a bug — size limits are **per type**: homework 25 MB, exam papers & quiz images 10 MB. Ask the user to compress the file |

---

## 6. Suspected data isolation breach (student sees another student's data)

**This is the most critical non-security incident. Treat it with urgency.**

**Step 1 — Verify it actually happened**
- Get the exact URL the student visited
- Check which user was logged in (check session / auth logs)
- Reproduce with test accounts before concluding it's real

**Step 2 — If confirmed — immediate actions**
1. Identify the scope: is this one record, one feature, or all data?
2. If it's a code bug — roll back the deploy immediately
3. Log into Neon — check if any data was actually exposed (which rows, which users)
4. Document everything (time, affected users, what was exposed)

**Step 3 — Inform affected users**
- Contact the affected families directly (WhatsApp)
- Be honest: *"We identified a brief technical issue where [specific data] may have been visible to another user. The issue has been fixed. We are sorry and take this seriously."*

**Step 4 — Fix and test**
- Fix the isolation bug
- Add a test to `MultiTutorTest.php` that catches this exact scenario
- Redeploy
- Verify the fix with two test accounts

**Step 5 — PDPA notification (if required)**
Under Singapore PDPA, a data breach that causes or is likely to cause significant harm must be reported to the PDPC within **3 days**. This includes unauthorised access to personal data.
- Report at: [pdpc.gov.sg/Overview-of-PDPA/The-Legislation/Personal-Data-Protection-Act/Data-Breach-Notification](https://www.pdpc.gov.sg)
- Notify affected individuals as well
- Document everything — the PDPC may ask for details

---

## 7. Google OAuth broken — users can't log in with Google

**Symptoms:** clicking "Login with Google" shows an error or redirects to a confusing page.

**Common causes:**

| Cause | Fix |
|---|---|
| Redirect URI mismatch | In Google Cloud Console, verify `https://wowlo.onrender.com/auth/google/callback` is in the authorized redirect URIs. Must match `GOOGLE_REDIRECT_URI` in Render env exactly |
| Client secret expired or rotated | Generate a new client secret in Google Cloud Console → update `GOOGLE_CLIENT_SECRET` in Render env → redeploy |
| Domain change (moved to wowlo.app) | Add new callback URL `https://wowlo.app/auth/google/callback` to Google Cloud Console AND update `GOOGLE_REDIRECT_URI` env var |
| Google API quota exceeded | Check Google Cloud Console → APIs → usage. Very unlikely for MVP scale |

**Interim workaround while fixing:** email/password login still works — inform users via WhatsApp to use that temporarily.

---

## 8. Push notifications stopped working

**Symptoms:** students/parents no longer receive push notifications for new homework or messages.

**Step 1 — Verify it's a push issue, not a data issue**
- Check that homework is still being created and messages still being sent (the underlying features work)
- Check `laravel.log` for push-related errors

**Step 2 — Common causes**

| Cause | Fix |
|---|---|
| VAPID keys changed | If `VAPID_PRIVATE_KEY` was rotated, all existing subscriptions are invalid. Users must re-subscribe. Update `VAPID_PUBLIC_KEY` in the meta tag and redeploy |
| `push_subscriptions` table empty | Users need to re-enable notifications from the student dashboard |
| iOS users not receiving (never were) | Expected — iOS push only works for installed PWA on iOS 16.4+. Not a bug |
| Push service (browser) outage | Rare — wait and monitor |

**Note:** push notifications are **best-effort** by design. Their failure never breaks the app. The dashboard is always the source of truth.

---

## 9. Email (Resend) not delivering

**Symptoms:** password reset emails not arriving, notification emails missing.

**Step 1 — Check Resend dashboard**
- Log into [resend.com](https://resend.com)
- Check the Logs tab — was the email accepted? Delivered? Bounced?

**Step 2 — Common causes**

| Cause | Fix |
|---|---|
| `RESEND_API_KEY` wrong or revoked | Regenerate in Resend dashboard → update Render env → redeploy |
| `MAIL_FROM_ADDRESS` domain not verified | Use the `resend.dev` fallback address until the real domain is verified in Resend |
| Recipient email invalid | User's email address may be wrong — check the `users` table |
| Resend free tier limit | Free tier is 3,000 emails/month — very unlikely to hit for MVP |

---

## 10. UptimeRobot alerts stopped / monitor went down

**Symptoms:** you stopped receiving UptimeRobot emails, or the monitor is showing red.

- Log into [uptimerobot.com](https://uptimerobot.com)
- Check if the monitor is paused or deleted — recreate if needed
- Verify your alert email is correct and not filtered to spam
- If the monitor is red → the app is actually down → go to §1

> Without UptimeRobot, Render's free tier sleeps after 15 minutes. This means the next visitor gets a 30-60 second cold start. Fix UptimeRobot before users notice.

---

## 11. Escalation contacts

| Service | Support / Status |
|---|---|
| Render | [render.com/docs](https://render.com/docs) · status.render.com |
| Neon | [neon.tech/docs](https://neon.tech/docs) · status.neon.tech |
| Cloudflare R2 | [developers.cloudflare.com/r2](https://developers.cloudflare.com/r2) · cloudflarestatus.com |
| Google OAuth | [console.cloud.google.com](https://console.cloud.google.com) |
| Resend | [resend.com/docs](https://resend.com/docs) |
| UptimeRobot | [uptimerobot.com](https://uptimerobot.com) |
| PDPC (data breach) | [pdpc.gov.sg](https://www.pdpc.gov.sg) — report within 3 days |

---

## 12. Post-incident checklist

After resolving any incident:

- [ ] Root cause identified
- [ ] Fix deployed and verified
- [ ] `laravel.log` is clean
- [ ] Smoke test passed across all 3 roles
- [ ] Affected users informed (if their experience was impacted)
- [ ] Test written to prevent recurrence (especially for data isolation bugs)
- [ ] PDPA notification filed if personal data was exposed
- [ ] Document what happened in a simple note (date, what broke, how it was fixed) — helps you spot patterns

---

*Wowlo — Incident Response Guide. Stay calm. Diagnose first. Fix second.*
