# Multiplication Rabbit — Games / Slice 17

A times-table / long-multiplication game for students, with a tutor review + feedback
loop. The **third** game under the **Games** tab. Built to the **same tenancy + "student
does work → tutor marks/comments" shape as [Spelling Meow](spelling-game.md)** — *tracked*,
**not** the throwaway shape of [Roll the Dice](roll-the-dice.md).

> **Why "Multiplication Rabbit":** fits the animal theme next to Spelling Meow, "rabbits
> multiply" is an on-theme pun, and it's concrete for kids.

## The game (student side)

A single Alpine.js page (`student/games/multiplication/play.blade.php`), mirroring Spelling
Meow:

1. **Loading spinner** — a rabbit mascot spins centre-screen with **"Firing Up"** for a
   beat (needs an image at `public/images/games/multiplication/`, like the spelling cat).
2. **Select Your Level** — multiplication questions are *generated*, not pulled from a word
   list, so levels are a **difficulty ladder by digit count** (see below):
   - **1 by 1 digit**, **2 by 2 digits**, **3 by 2 digits**, **3 by 3 digits** — playable.
   - **Mixed** sits at the end — questions drawn at random across all four levels.
   - A level is playable iff it has a definition in `config/multiplication-levels.php`
     (mirrors "an empty spelling level becomes *Building in progress..*").
3. **How many questions?** — after picking a level, a modal asks **10 / 15 / 20 questions**.
   The client then generates that many questions within the level's server-provided ranges
   (the count is one of the allowed set `[10, 15, 20]`; on submit the server caps `items` at
   20 and range-validates every factor — see Security). This is *before* the timer modal.
4. **Timer** — identical to Spelling Meow: a modal offers *Infinite time* or *Set a timer*
   (**1 / 3 / 5 / 7 / 10 min** or **Other**, whole minutes 1–180). A countdown shows in the
   play header; at **15 s left** the `<x-siren-lights/>` banner flashes; at **0** the round
   auto-completes and is marked. Timing is purely client-side UX — the **server still marks
   authoritatively**. (Higher levels are hard to do mentally; Infinite time is the natural
   default for 3×3.)
5. **Play** — each question shows `6 × 7 = ▯`; the student types the answer into a number
   box. **Enter** or the **Hop!** button advances; the final question's button reads
   **Done**. **Each answer submit plays a short synthesised "hop" tone** (a Web Audio tone —
   no audio file, same no-asset approach as the Spelling Meow blip / Roll the Dice rattle;
   silenced under `prefers-reduced-motion` and where Web Audio is unavailable). The sound is
   **on submit, not per digit** — digits are typed fast and per-digit would be noisy.
6. **Review prompt** — after the last question a modal asks *"Do you want to look through
   your answers again?"* **Yes** → back to question 1 with their answers preserved (editable).
   **No** → submit.
7. **Results** (`student/games/multiplication/show.blade.php`) — score + **percentage**, a
   per-question correct/wrong review (revealing the correct product), the **mandatory
   reflection** (below), and the tutor's **Feedback** (read-only here — the tutor fills it
   later).

### Mandatory reflection (hard gate — marks shown first)

Reused verbatim from Spelling Meow: until the student writes a reflection, the results page
renders in **gate mode** — a full-screen scrollable panel (`z-[100]`, covering
sidebar/top-bar) that **shows the marks first** (score %, per-question review), then the
**reflection box** at the bottom. **Save & continue** is disabled until the textarea is
non-empty; **no minimum length** (server trims, rejects only blank/whitespace). Saving
unlocks **normal mode** (same results + editable reflection + tutor-feedback area). The gate
re-appears for any old round (opened from My Progress) that still has no reflection. The
score card + per-question review are shared by both modes via a
`student/games/multiplication/partials/results.blade.php`.

A soft random **background image** is shown behind the play area, pulled from
`public/images/games/multiplication/backgrounds/` (graceful gradient fallback if empty).

## Levels — `config/multiplication-levels.php`

Single source of truth (same convention as `config/spelling-words.php` / `config/wowlo.php`).
Each level defines the **digit range of each factor**; the server uses this both to
**generate** questions and to **validate** submitted factors. Suggested ranges:

| Level           | Factor A   | Factor B   | Notes                          |
| --------------- | ---------- | ---------- | ------------------------------ |
| 1 by 1 digit    | 1–9        | 1–9        | includes ×1                    |
| 2 by 2 digits   | 10–99      | 10–99      |                                |
| 3 by 2 digits   | 100–999    | 10–99      |                                |
| 3 by 3 digits   | 100–999    | 100–999    |                                |
| Mixed           | —          | —          | random across the four above   |

**1×1 includes ×1** (e.g. `7 × 1`) — the trivial cases are deliberately allowed.

The **number of questions per round is chosen by the student** (the 10/15/20 modal), so the
config does **not** fix a per-round count. The server clamps the requested count to the
allowed set **`[10, 15, 20]`** (default 10 if missing/invalid) and generates that many.
Adjust the digit ranges here and the UI follows automatically (a level with no definition
becomes "Building in progress..").

## Navigation — games own their pages

Add **Multiplication Rabbit** as a third child under **Games** in
`resources/views/layouts/app.blade.php` — both the tutor branch
(→ `tutor.games.multiplication.index`) and the student branch
(→ `student.games.multiplication.play`). Each game's own pages share a **Play / My Progress**
tab bar (`student/games/multiplication/partials/tabs.blade.php`), sub-navigated *inside* the
game, not from the sidebar.

## My Progress (student)

`student/games/multiplication/progress.blade.php` (the **My Progress** tab) lists the
student's past rounds (level, score, date), each linking to its results page.

## Tutor side

`tutor/games/multiplication/` — the tutor sees **their own students'** completed rounds
(`index`), opens one (`show`), and writes **Feedback** the student then sees on their results
page (and the student is notified via the inbox, same as Spelling Meow / Resources).

## Data & storage — Neon (Postgres), not R2

Marks, per-question results, reflections and feedback are **structured relational data** →
Postgres. One table, mirroring `spelling_attempts`:

`multiplication_attempts`
- `student_id`, `tutor_id` (denormalised owner — the tenancy backbone, set server-side)
- `level`, `total_questions`, `correct_count`, `score_percent`
- `results` (JSON: `[{a, b, response, answer, is_correct}]`)
- `reflection` (student, required to "complete"), `feedback` / `feedback_by` / `feedback_at` (tutor)

Model `App\Models\MultiplicationAttempt`; controllers
`App\Http\Controllers\Student\MultiplicationController` +
`App\Http\Controllers\Tutor\MultiplicationController`; routes added under the existing
`games/...` prefixes in `routes/web.php` (`games/multiplication`), exactly parallel to the
spelling routes.

## Security / correctness

- **The server marks authoritatively** and recomputes the score — never trusts a
  client-submitted total (project guardrail). On `finish`, the client submits
  `[{a, b, response}]`; the server recomputes `is_correct = (a * b === response)`,
  **validates each `a`/`b` falls within the chosen level's digit range** (rejects tampered or
  out-of-range factors), and computes `score_percent`.
- ⚠️ **Why this game does NOT hide the answer** (unlike Spelling Meow): a multiplication
  question *is* its own answer — `6 × 7` is trivially computable in the browser. There is no
  secret to protect, so we don't try to hide it. The **server-side mark + digit-range
  validation** is what keeps the stored score trustworthy, and that's enough. (Spelling hides
  the correct word because the prompt doesn't reveal it; multiplication has no such secret.)
- **Tenancy:** a student only ever touches their own attempts (`student_id`, 404 otherwise);
  a tutor only their own students' attempts (`where('tutor_id', auth()->id())`, 404
  otherwise). Covered by additions to `tests/Feature/MultiTutorTest.php` + a new
  `tests/Feature/MultiplicationGameTest.php` (mark correctness, digit-range validation,
  reflection gate, isolation).

## Design

Keeps the Wowlo brand (Nunito, primary purple, cream, rounded-2xl, Heroicons, no emojis) and
adds an energetic **green/teal** accent (distinct from Spelling Meow's orange, so students
tell the games apart) + bouncy micro-interactions — chunky rounded buttons, a number input
with auto-advance, a hopping rabbit. Respects `prefers-reduced-motion`.

## Assets to provide

- A rabbit mascot image for the loading spinner (like `3d-smart-cat.png`).
- Optional backgrounds in `public/images/games/multiplication/backgrounds/` (gradient
  fallback if empty).
