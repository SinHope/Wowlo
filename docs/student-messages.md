# Student → Tutor Messages (Slice 19)

**Status:** ✅ **Built.** Students can now message **their own tutor** (new message or reply), capped at **5 per hour**. Tutors can reply to a student message in one tap. Text only.

> Companion docs: [SECURITY.md](SECURITY.md) (isolation) · [FEATURE_CHANGES.md](FEATURE_CHANGES.md) (this slice is 🟢 — no schema change) · [TESTING.md](TESTING.md) · [SuperAdmin-Admin-Contact-Feature.md](SuperAdmin-Admin-Contact-Feature.md) (the planned tutor → super_admin channel, which reorganises the same tutor Inbox).

---

## 1. What it does

| Who | What's new |
|---|---|
| **Student** | Messages page gains **Inbox / Sent** tabs and a **Message my tutor** button (with "N of 5 messages left this hour" under it). Received messages get a **Reply** button. Sent messages show **Read by tutor / Not read yet**. |
| **Tutor** | Student messages land in the existing **Inbox** (unread badge already there). A message from one of their own students gets a **Reply** button → Compose with the student pre-selected and "Re: …" prefilled. |
| **Push** | The tutor gets the same best-effort web push (`NewMessageNotification`). Its tap-through URL is now **role-aware** (tutor → `tutor.messages.show`, student → `student.messages.show`). Before this, every push linked to the student page, which also broke tutor pushes for exam-paper approvals and answer-sheet submissions. |

## 2. No schema change

A message is still just `sender_id → receiver_id` on the `messages` table. A student message is a row with sender = student, receiver = `student.tutor_id`. Replies are **not threaded**: a reply is a new message whose subject is prefilled "Re: …" (`Message::replySubject()`, which never stacks "Re: Re:"). Messages stay immutable (no edit/delete).

## 3. Routes

All in the `role:student` group (`routes/web.php`), handled by `Student\MessageController`:

| Route | Purpose |
|---|---|
| `GET  /student/messages?box=sent` | Inbox (default) or Sent tab. |
| `GET  /student/messages/create?reply_to={id}` | Compose. `reply_to` only prefills, and only from a message **this student received**. Otherwise it's ignored. |
| `POST /student/messages` | Send. Validates `subject` (≤255) + `body` (≤5000). **No `receiver_id` rule.** |
| `GET  /student/messages/{message}` | Read a message the student **sent or received** (else 403). Marks read only if they're the receiver. |

Tutor side: `GET /tutor/messages/create?reply_to={id}` prefills only from a message **received by this tutor from one of their own students**. Anything else (another tutor's message, an admin notice) is silently ignored, so nothing leaks.

## 4. The hourly limit

- **Value:** `config('wowlo.student_messages_per_hour')` = **5**. Change it there. The compose page, the index hint, the onboarding card and `/how-to-use` all read it.
- **Mechanism:** Laravel's `RateLimiter`, key `student-messages:{student_id}`, 3600 s decay. It's a **fixed window**: the first message starts a one-hour clock, and when it ends all 5 come back at once. The compose page shows the refill time ("You'll have 5 again at 4:05 PM").
- **Why not count `messages` rows?** Automatic notices are also sent *as the student* (e.g. "Answer sheet submitted" from `Student\ResourceSheetController`). Counting rows would let those eat the student's allowance. The limiter only counts the compose form.
- **Order:** validate → check limit → `hit` → create. A form that fails validation **doesn't use up a message**. Over the limit, it redirects back **with input** (their text isn't lost) and a `limit` error.
- **UI:** a status box above the form with pips: violet when ≥2 left, amber "last message for this hour", red "used all 5 — you can send again at …". At 0 the fieldset and Send button are disabled (the server enforces it regardless).
- **Storage:** uses the default cache store (`database`, same as the fee-unlock throttle). Clearing the cache resets counters. That's acceptable for an anti-spam cap.

## 5. Security rules

- **Recipient is always `auth()->user()->tutor_id`, set server-side.** An injected `receiver_id` is dropped by validation. A student without a tutor gets 404 on compose/send.
- A student can read only messages they sent or received (403 otherwise, matching the existing student check).
- Tutors never see another tutor's student messages (Inbox scoped by `receiver_id`; `show` 404s for non-parties; `reply_to` prefill scoped to own students).
- Messages are permanent. Because the student/parent login is shared, a parent can always read the whole conversation. Keep it that way (don't add delete without revisiting this). If parent and student logins are ever split, make sure parents can still see these.

## 6. UX details

- Body placeholder asks the student to think before sending and put all their questions/thoughts in one message.
- The compose form blocks implicit Enter-submit on inputs (`@keydown.enter` guard) so a stray Enter in Subject can't burn a message; the spinner overlay prevents double-submits.
- When replying, the original message is shown above the form ("Replying to …").

## 7. Tests

- `MessageTest.php`: send (injected `receiver_id` ignored, tutor notified), "N of 5 left" display, 6th blocked + allowed after 61 min, validation failure doesn't count, auto-notices/classmates don't count, no-tutor → 404, tutor blocked from student routes, sent-message view (not marked read), classmate's message → 403, reply prefill (own received only), tutor inbox + Reply + prefilled compose, `replySubject()` no "Re: Re:".
- `MultiTutorTest.php`: a student message reaches only their own tutor (other tutor: not in inbox, `show` 404), and another tutor's `reply_to` prefill leaks nothing.
- `PushNotificationTest.php`: push URL matches the receiver's role.

## 8. Later (not built)

- **Attachments** (e.g. a photo of the question): add with the Contact-Admin slice, which already plans `attachment_path` / `attachment_name` on `messages`.
- **Threads:** a nullable `reply_to_id` column would be additive if flat "Re:" ever gets messy.
- **Per-tutor opt-out** if some tutors don't want in-app student messages.
