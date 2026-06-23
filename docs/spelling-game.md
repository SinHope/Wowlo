# Spelling Meow — Games / Slice 14

A spelling game for students, with a tutor review + feedback loop. First game under
the **Games** tab. Built to the same tenancy + "student does work → tutor marks/comments"
shape as [Resources](resources-answer-sheets.md).

## The game (student side)

The flow is a single Alpine.js page (`student/games/spelling/play.blade.php`):

1. **Loading spinner** — the 3D cat mascot (`public/images/games/spelling/3d-smart-cat.png`)
   spins centre-screen with the text **"Firing Up"** for a beat.
2. **Select Your Level** — **Primary School** / **Secondary School**, then the specific levels:
   - Primary 1–6 and Secondary 1–2 are **playable**.
   - **Mixed Primary (Primary 1 - 6)** sits at the end of the Primary group — a **30-word**
     round drawn at random from across all of Primary 1–6 (a normal round is 10 words). It's
     a *virtual* level (not in `config/spelling-words.php`); the controller merges the Primary
     words for it (`SpellingController::MIXED_PRIMARY` / `wordsForLevel()`).
   - **Secondary 3, 4, 5 are disabled** — struck-through with "Building in progress.."
     (a level is playable iff it has words in `config/spelling-words.php`).
3. **Timer (Primary levels + Mixed Primary)** — after picking a level, a modal asks
   *Infinite time* or *Set a timer*. Timed → choose **1 / 3 / 5 / 7 / 10 min** or **Other**
   (whole minutes, 1–180). A countdown shows in the play header; at **15 s left** the
   `<x-siren-lights/>` police-siren banner flashes at the top; at **0** the round
   auto-completes and is marked. Secondary levels skip the timer (infinite). Timing is
   purely client-side UX — the server still marks authoritatively.
4. **Play** — for each question a **wrongly-spelled** word is shown on top; below it are
   per-letter blank boxes (one `_` per letter of the *correct* word). The student types
   the correct spelling. **Enter** or the **Spelt** button advances. The final question's
   button reads **Done With Spelling**.
4. **Review prompt** — after the last question a modal asks *"Do you want to look through
   your answers again?"* **Yes** → back to question 1 with their typed answers preserved
   (same layout, editable). **No** → submit.
5. **Results** (`student/games/spelling/show.blade.php`) — score + **percentage**, a
   per-word correct/wrong review (now revealing the correct spelling), a **required**
   "My Reflection / Learning Points" textarea (the student MUST fill this), and the
   tutor's **Feedback** (read-only here — the tutor fills it later).

A soft random **background image** is shown behind the play area, pulled from
`public/images/games/spelling/backgrounds/` (graceful gradient fallback if empty).

## Navigation — games own their pages

In the sidebar, **Games** lists the games themselves (currently just **Spelling Meow**).
Each game's own pages are sub-navigated *inside* the game, not from the sidebar: the
student spelling pages share a **Play / My Progress** tab bar
(`student/games/spelling/partials/tabs.blade.php`). Add a second game later as another
child under Games with its own internal tabs.

## My Progress (student)

`student/games/spelling/progress.blade.php` (the **My Progress** tab) lists the student's
past rounds (level, score, date). Each links to its results page to re-read the words they
got right/wrong, their reflection, and any tutor feedback.

## Siren lights component

`resources/views/components/siren-lights.blade.php` — the "15 seconds left" alert. The
original (`config/sirentlights.php`) was a React + three.js *fullscreen* WebGL "police
light"; that can't live in `config/` (Laravel loads config files as PHP arrays, and it
would leak its contents into every response) and three.js isn't a dependency here. It was
converted to a dependency-free CSS flashing red/blue siren banner — same intent, consistent
with the app's Blade/Tailwind/Alpine stack. The original file was removed.

## Tutor side

`tutor/games/spelling/` — the tutor sees **their own students'** completed rounds
(`index`), opens one (`show`), and writes **Feedback** which the student then sees on their
results page (and the student is notified via the inbox, same as Resources).

## Data & storage — Neon (Postgres), not R2

Marks, per-word results, reflections and feedback are **structured relational data** →
Postgres. R2 is only for file blobs (it would be the wrong tool here). One table:

`spelling_attempts`
- `student_id`, `tutor_id` (denormalised owner — the tenancy backbone, set server-side)
- `level`, `total_questions`, `correct_count`, `score_percent`
- `results` (JSON: `[{shown, answer, response, is_correct}]`)
- `reflection` (student, required to "complete"), `feedback` / `feedback_by` / `feedback_at` (tutor)

## Words — `config/spelling-words.php`

Single source of truth (same convention as `config/wowlo.php`). Keyed by level label;
each word is `['answer' => 'because', 'shown' => 'becuase']` — `shown` is the misspelled
prompt, `answer` is the correct spelling and its length drives the number of blanks.
`questions_per_round` caps how many words a round picks (at random). **Replace the seeded
sample words with your real Singapore-curriculum lists** — add/remove levels here and the
UI follows automatically (an empty level becomes "Building in progress..").

## Security / correctness

- Correct spellings are **never** sent to the browser during play — only the misspelled
  prompt and the letter count. The browser submits its responses; the **server marks**
  them against `config/spelling-words.php` and recomputes the score (never trusts a
  client total) — per the project guardrails.
- **Tenancy:** a student only ever touches their own attempts (`student_id`, 404 otherwise);
  a tutor only their own students' attempts (`where('tutor_id', auth()->id())`, 404 otherwise).
  Covered by `tests/Feature/MultiTutorTest.php` + `tests/Feature/SpellingGameTest.php`.

## Design

Keeps the Wowlo brand (Nunito, primary purple, cream, rounded-2xl, Heroicons, no emojis)
and adds an energetic **orange** accent + bouncy micro-interactions for game feel — chunky
rounded buttons, per-letter input boxes with auto-advance, a spinning cat. Respects
`prefers-reduced-motion`.
