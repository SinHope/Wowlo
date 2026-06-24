# Patch Notes (Slice 16)

A **public changelog** of updates to Wowlo. **Every authenticated user** (tutors,
students, and the super_admin) can read it; **only the super_admin** can create,
edit, or delete entries. The calm, permanent companion to the transient
[Banner Notification](banner-notifications.md) bar.

## What it does

- A **"Patch Notes"** tab in the sidebar for **all roles** (sparkles icon).
- Each note has a **title**, an optional free-text **version** (e.g. `v2.3`,
  `June 2026`), a **rich-text body** (bold / italic / underline / strikethrough /
  **bullet points**), and an optional **attached image**.
- The super_admin authors notes; everyone sees them newest-first on
  `/patch-notes`. Edit/Delete controls show only to the super_admin.

## Where the pieces live

| Piece | File |
|---|---|
| Migration | `database/migrations/2026_06_24_000002_create_patch_notes_table.php` |
| Model | `app/Models/PatchNote.php` |
| Read controller (everyone) | `app/Http/Controllers/PatchNoteController.php` — `index`, `image` (inline R2 stream) |
| Author controller (super_admin) | `app/Http/Controllers/Admin/PatchNoteController.php` |
| Validation | `app/Http/Requests/PatchNoteRequest.php` |
| HTML sanitiser | `app/Support/HtmlSanitizer.php` (shared with banners; allows `ul/ol/li` for bullets) |
| Routes | `routes/web.php` — reading in the shared `auth`/`verified` group; authoring in the `role:super_admin` `admin.` group |
| Public page | `resources/views/patch-notes/index.blade.php` |
| Author UI | `resources/views/admin/patch-notes/{create,edit,_form}.blade.php` |
| Tests | `tests/Feature/PatchNoteTest.php` |

## The shared rich-text editor

Both Patch Notes and Banner Notifications use **`<x-rich-text-editor>`**
(`resources/views/components/rich-text-editor.blade.php`), backed by the
`richEditor` Alpine component registered in `resources/js/app.js`. Props:

- `name` — the form field name (a hidden input carries the HTML value).
- `value` — initial sanitised HTML.
- `tools` — which buttons to show: `bold`, `italic`, `underline`,
  `strikeThrough`, `bulletList`, `color`, `link`.
- `placeholder`.

It tracks the **active formatting at the cursor** via `document.queryCommandState`
and **highlights the buttons currently in effect** (e.g. Bold lights up while the
caret is inside bold text) — updated on click, typing, and selection changes.
After any JS/Blade change here, run `npm run build`.

## Security & storage notes

- **Authorization:** reading is open to any logged-in user; authoring is behind
  `role:super_admin` (tutors/students get 403). Covered by `PatchNoteTest`.
- **Body is sanitised server-side before storage** (`HtmlSanitizer::clean`) — the
  same allowlist as banners, plus `ul/ol/li` for bullet lists. Scripts, event
  handlers, and unsafe URLs are dropped; empty-after-sanitise is rejected.
- **Images go to the private R2 bucket** (disk `r2`), the DB stores only the
  object key (`image_path`) + original name, and the image **streams inline
  through `patch-notes.image`** (auth-gated) — never a public URL. Deleting or
  replacing a note removes the old object from R2. Validation: image, ≤ 4 MB,
  `jpg/jpeg/png/webp/gif`.

## Future ideas (not built)

- Notify users of a new note (push / a "new" dot on the sidebar tab).
- Tie a note to a banner ("Read more" link from the banner to the note).
