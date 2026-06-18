# Wowlo — SuperAdmin/Admin ↔ Tutor Contact Feature (Phase 2)

**Status:** 🔵 **Not built — Phase 2, designed and ready to be scheduled as a slice.** This doc is the canonical architecture + design. Build from this, not from memory. Natural companion to [public-tutor-sign-up.md](public-tutor-sign-up.md) — once tutors self-register, they need a support channel to reach the super_admin.

> Companion docs: [DATABASE.md](DATABASE.md) (messages schema, additive-migration rules) · [SECURITY.md](SECURITY.md) (file-upload rules, isolation) · [FEATURE_CHANGES.md](FEATURE_CHANGES.md) (everything here is **additive**) · [TESTING.md](TESTING.md).

---

## 1. What this feature is (3 pieces)

A two-way communication channel between **tutors** and the **super_admin** (and, later, possible non-super "admins" — see §8), layered on the existing messages system:

1. **"Contact SuperAdmin/Admin" sidebar tab — tutors only.** A contact form where a tutor composes any message (subject + body + optional attachment stored in Cloudflare R2) that is sent **only to the super_admin**. The recipient is fixed server-side — the tutor never chooses it.
2. **Super_admin's Messages tab gains filter + sort + audience compose.** Filter conversations by counterpart type (**Students / Tutors / All**), sort **A–Z / Z–A** by counterpart name. "+ Compose" gains an audience picker: a specific student (own roster), a specific tutor, several tutors, or **all tutors** (broadcast — e.g. a general update).
3. **Tutor's Messages tab gains sort + a pinned admin section.** Sort **A–Z / Z–A** only (no filter). A new section **"Messages from SuperAdmin/Admin"** pinned at the **top**, before the students section, so tutors instantly see replies and announcements. Its heading uses the warm amber "pending" colour pair: `bg-accent/15 text-accent-dark` (`#F59E0B` / `#D97706` from `tailwind.config.js`).

---

## 2. What already exists (build on it, don't duplicate)

- **`messages` table** (`2026_06_01_000000_create_messages_table.php`): `sender_id`, `receiver_id` (both FK → `users`), `subject`, `body`, `is_read`, index on `(receiver_id, is_read)`. **No attachment columns yet.**
- **Super_admin → tutor messages already happen**: exam-paper approval sends the uploader a Message, and `Tutor\MessageController@inbox` already lists a tutor's received messages with an unread badge in the sidebar. So the *receiving* half of the channel exists — this feature formalises the *sending* half (tutor → super_admin) and the UI around it.
- **`Tutor\MessageController@show`** already does the party check (sender or receiver, else **404**) and marks received messages read.
- **R2 attachment pattern** (from homework): file → private `r2` disk, DB stores the object key + original name, downloads stream through an authorized controller route. Validation: `pdf,doc,docx,jpg,jpeg,png`, max 25MB.
- **Sidebar** (`layouts/app.blade.php`): the tutor menu is one array with an `$isSuperAdmin` conditional already (the "Tutors" tab). The new tab uses the inverse condition.
- **Push notification on new message** (`NewMessageNotification`, best-effort try/catch) — reuse as-is for admin messages.

---

## 3. Schema changes (all additive — no existing column touched)

One migration on `messages`:

| Column | Type | Notes |
|---|---|---|
| `attachment_path` | `string` nullable | R2 object key (e.g. `message-attachments/{uuid}.pdf`). Same pattern as homework. |
| `attachment_name` | `string` nullable | Original filename for the download link text. |

That's it. **No new tables.**

- **Broadcast = fan-out rows.** "Send to all tutors" creates one `messages` row per tutor (same subject/body/attachment key). This keeps per-recipient `is_read` working with zero schema change, and the inbox/badge queries unchanged. Fine at current scale; if tutor count ever makes the loop slow, move the fan-out into a queued job ([SCALABILITY.md](SCALABILITY.md)) — still no schema change.
- **No `message_type` column needed.** "Admin messages" are identified by the sender's role (`sender.role IN ('super_admin')` — later `'admin'` too), which the UI sections/filters query via a join or `whereHas`. Avoids a second source of truth.

---

## 4. Routes + controllers (sketch)

**Tutor side (new):**

| Route | Purpose |
|---|---|
| `GET  /tutor/contact-admin` | The contact form (subject, body, attachment). |
| `POST /tutor/contact-admin` | Store. **Recipient is resolved server-side**: `User::where('role','super_admin')->firstOrFail()`. The request never contains `receiver_id` — same rule as `tutor_id` everywhere else. |
| `GET  /messages/{message}/attachment` | Streams the R2 file. Authorized: only the message's sender or receiver, else **404**. One shared route can serve tutor/student/admin since the party check is the whole authorization. |

Middleware: `role:tutor` for the contact form (the super_admin doesn't get this tab — they'd be messaging themselves). The form reuses `MessageRequest`-style validation + the homework attachment rules.

**Super_admin side (extend `Tutor\MessageController` or a small `Admin\MessageController`):**

- `index` gains query params: `?filter=students|tutors|all` and `?sort=az|za` (counterpart name). Filter is **super_admin only** — guard server-side, not just hidden in the UI.
- `create`/`store` gain the audience picker. Server-side rules:
  - Audience `student` → must be in `auth()->user()->students()` (the super_admin's own roster — unchanged tenancy rule).
  - Audience `tutor(s)` / `all tutors` → **super_admin only**; recipients resolved server-side from `role IN ('tutor')` (+ future `'admin'`), never trusted from a raw ID list without role-checking each.

**Tutor Messages page (extend existing `index`/`inbox` views):**

- Pinned section "Messages from SuperAdmin/Admin": received messages `whereHas('sender', role super_admin/admin)`, newest first, amber heading.
- Students section below: the existing list, with the new `?sort=az|za` (by student name). No filter control for tutors.

---

## 5. UI design

**Sidebar (tutor only, not super_admin):** new entry after "Inbox":

```
['label' => 'Contact SuperAdmin/Admin', 'icon' => 'chat', 'href' => ..., 'active' => ...]
// wrapped in:  ! $isSuperAdmin ? [...] : null   (inverse of the "Tutors" tab condition)
```

Heroicons SVG (no emoji), `cursor-pointer`, consistent with every other entry.

**Contact form page:** mirrors the homework create form — subject (text), message (textarea), attachment (file input, same accept list + 25MB hint). On send: redirect back with a "Message sent to SuperAdmin" status flash. **No recipient field is rendered at all.**

**Tutor Messages page layout:**

```
┌──────────────────────────────────────────────┐
│ ▸ Messages from SuperAdmin/Admin             │  ← heading: bg-accent/15 text-accent-dark
│   (received from super_admin, newest first) │
├──────────────────────────────────────────────┤
│ ▸ Students                    [Sort: A–Z ▾]  │  ← existing list + sort control
│   ...                                        │
└──────────────────────────────────────────────┘
```

**Super_admin Messages page:** same page plus a filter control next to sort:
`[Filter: All ▾ | Students | Tutors]  [Sort: A–Z ▾ | Z–A]`

**Compose (super_admin only) audience picker:** radio/segmented control — *A student* (dropdown of own roster) / *A tutor* (dropdown of tutors) / *Multiple tutors* (multi-select) / *All tutors*. Tutors and students never see this picker; their compose stays exactly as today.

**Attachment display (message show page):** if `attachment_path` is set, render the same "Download attachment" button used on homework show, pointing at the streaming route.

---

## 6. Security rules (the non-negotiables)

- **Recipient of the contact form is hardcoded server-side** to the super_admin. `receiver_id` must not appear in that request's validation rules, so `validated()` strips any injected value — identical philosophy to the `tutor_id` rule.
- **Tutor ↔ tutor messaging stays impossible.** A tutor's compose recipient list remains *their own students only*. The contact form is the single tutor-initiated exception, and it can only reach the super_admin.
- **Attachment downloads stream through the authorized route** — private R2 bucket, never a public URL, party check (sender or receiver) with **404** on miss.
- **Upload validation mirrors homework**: extension allow-list (`pdf,doc,docx,jpg,jpeg,png`), 25MB max, stored under a non-guessable key.
- **Audience filter + broadcast compose are role-gated server-side** (`super_admin`), not merely hidden in Blade.
- **Students are completely untouched** — their messages UI and rules do not change in this slice.
- New cross-tenant surfaces ⇒ isolation tests in `tests/Feature/MultiTutorTest.php` (see §7).

---

## 7. Tests required when built

- Tutor sends via contact form → message lands with `receiver_id` = super_admin even if the request injects a different `receiver_id`.
- Tutor A **cannot** read tutor B's message to the super_admin (show route → 404), and cannot download B's attachment (→ 404).
- A student cannot reach the contact form routes (403/404 by middleware) and sees no UI change.
- Super_admin broadcast to "all tutors" creates one row per tutor; each tutor sees only their own copy; `is_read` is independent per tutor.
- A plain tutor calling the broadcast/audience endpoints with `tutors`/`all` is rejected.
- Filter/sort params on the super_admin index don't leak other tutors' student conversations beyond what the super_admin legitimately is party to.
- Attachment upload: oversize and wrong-type files rejected; valid file stored on `r2` disk (faked in tests) and download streams for sender + receiver only.

---

## 8. Open decisions (settle when the slice is scheduled)

| Decision | Options | Lean |
|---|---|---|
| **The "admin" role** | The feature name anticipates non-super "admins" who can also message/reply to tutors. Today only `super_admin` exists. | Build for super_admin only now; the role CHECK constraint + `sender.role` queries make adding `'admin'` later additive. Don't pre-build the role. |
| **Contact form replies** | Tutor replies via the same contact form vs a thread view | Same form is simplest; threads are a later nicety. |
| **Does "All" broadcast include students?** | Tutors only vs tutors + own students | **Tutors only** — the stated use case is tutor-wide updates; students already get messages individually. |
| **Sort scope on tutor page** | A–Z applies to students section only (admin section stays newest-first) vs both | Students section only — the admin section is one sender, chronological is what you want there. |
| **Edit/delete sent broadcasts** | none vs delete-unread-copies | None at first (matches current messages — immutable once sent). |

---

## 9. Build-order checklist (when scheduled)

1. Migration: `attachment_path` + `attachment_name` on `messages` (additive, nullable).
2. Attachment streaming route + party-checked controller method; wire the download button into message show views.
3. Tutor contact form (route, controller, Blade, sidebar tab with `! $isSuperAdmin`).
4. Tutor Messages page: pinned amber admin section + A–Z/Z–A sort.
5. Super_admin Messages page: filter + sort.
6. Super_admin compose audience picker + fan-out store logic.
7. Tests (§7), `npm run build`, manual checkpoint in browser per convention.
