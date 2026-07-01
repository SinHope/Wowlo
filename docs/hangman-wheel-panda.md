# Hangman Wheel Panda — Games / Slice 18

A Wheel-of-Fortune-style hangman game under the **Games** tab. The student
**spins a wheel** for a fun surprise, then **guesses letters** (or the whole
word) to uncover a secret word. Each wrong guess draws one more piece of a
**panda**; complete the panda and the round is lost.

This game is **server-authoritative throwaway play** (like
[Roll the Dice](roll-the-dice.md)) — it stores **no attempts** and has **no
tutor-review / reflection loop**. The only persistent, tenant-scoped data is the
**wheels**. (Contrast with [Spelling Meow](spelling-game.md) /
[Multiplication Rabbit](multiplication-game.md), which are tracked.)

## The panda (the "hangman")

The panda is an inline SVG in the play view, revealed one part per wrong guess,
in this exact order (the **face fills in last**, as requested):

1. face (head outline) · 2. body · 3. left hand · 4. right hand · 5. left leg ·
6. right leg · 7. left eye · 8. right eye · 9. nose · 10. **mouth**

`count(config('hangman-words.parts'))` **is** the max wrong guesses (10). Once
the **mouth** (part 10) is drawn, the student loses. Change the list in
`config/hangman-words.php` and both the allowed-guess count and the loss point
follow automatically (the SVG draws part N once `wrong >= N`).

## Why the word is server-side (security)

Hangman **has a secret** (the word), unlike Multiplication Rabbit. So the game
is server-authoritative:

- `POST /games/hangman/start` picks a random word from `config/hangman-words.php`
  and stores it in the **session** (`hangman_game`). The response is the
  **masked** state only — word length + which letters are revealed + the wrong
  count — **never the word**.
- `POST /games/hangman/guess` (`{letter}` or `{word}`) is **marked on the
  server**: a correct letter reveals positions; a wrong letter **or** wrong
  whole-word guess increments `wrong` (draws a part). The full word is returned
  **only once the round is over** (`won`/`lost`).

So the answer can't be read from the page source mid-game. No DB row is written.

Routes (shared, `auth`+`verified`, all roles) live in
`App\Http\Controllers\HangmanController`: `games.hangman.{play,start,guess,effect}`.

## The wheel — spin-gated + smart effects

**Letters are locked until you spin.** Each spin lands on a slice and grants
guesses based on the slice text; each letter (or whole-word) guess costs one,
and when you run out you must spin again. Turn pacing (how many guesses a spin is
worth) is **client-side**; state-changing effects go through `POST
.../effect` so the secret word + score stay **server-authoritative** (the client
can't reveal letters or fake the panda).

Recognized slice phrases (case-insensitive — match the builder's suggestion
chips) do real things; **any other custom text = 1 normal guess** (so free-form
tutor text still works):

| Slice text (recognized)        | Effect |
| ------------------------------ | ------ |
| `+1 Free Guess`                | 2 guesses this spin |
| `+2 Free Guesses` / `Double Trouble` | 3 guesses this spin |
| `Reveal a Letter` / `Mystery Letter` | server reveals a hidden letter, + 1 guess |
| `Free Vowel`                   | server reveals a hidden vowel, + 1 guess |
| `Lose a Guess`                 | server draws a panda part, + 1 guess |
| `Spin Again` / `Skip a Turn`   | 0 guesses — spin again |
| *(anything else)*              | 1 normal guess |

The mapping lives in the play view's `applyEffect()`; the server-side
`reveal` / `reveal_vowel` / `lose_guess` actions live in
`HangmanController@effect`. The landed slice text is shown both **on the wheel**
(radial labels that spin with the disc) and in the "You landed on" banner.

Two kinds of wheel (`hangman_wheels` table, `App\Models\HangmanWheel`):

- **Standard** — `type=standard`, `tutor_id=NULL`. Authored **only by the
  super_admin**; **global** (everyone can spin it). The seeder creates one
  ("Wowlo Standard Wheel").
- **Custom** — `type=custom`, `tutor_id` = the owning tutor. Authored by a tutor
  (or the super_admin acting as a tutor); seen **only by that tutor + their own
  students** (`HangmanWheel::availableTo($user)`).

`tutor_id` is **set server-side only**; a non-admin's `is_standard` request is
ignored (no tutor can mint a global wheel). Management lives in
`App\Http\Controllers\Tutor\HangmanWheelController` at
`tutor.games.hangman.wheels.*` (tutor+super_admin group). Cross-tenant access
**404s** (standard wheels: super_admin only; custom: owner only).

## Sound (Web Audio, no asset files)

Synthesised in the play view (same no-asset approach as the other games;
silenced under `prefers-reduced-motion` / where Web Audio is unavailable):

- **Spin** — a Wheel-of-Fortune ratchet: clicks that **widen** over the spin
  (deceleration), synced to the CSS spin.
- **Solve (win)** — an ascending arpeggio.
- **Lost** — a descending sawtooth.
- (Plus a tiny correct/wrong blip per letter.)

## Navigation

A child of **Games** in `resources/views/layouts/app.blade.php`:
- tutor/super_admin → `tutor.games.hangman.wheels.index` (manage wheels; the
  page cross-links to "Play the game").
- student → `games.hangman.play` (the shared play route).

## Categories & phrases — `config/hangman-words.php`

Single source of truth: `parts` (panda parts / max wrong) and `categories` (a
map of category name → list of puzzles). The student does **not** pick — `start`
chooses a **random category** (and a random puzzle from it) and returns the
category name, which is shown as a **hint** while playing ("Guess the puzzle ·
Animals"). A specific category can still be requested by name (an unknown/tampered
name 422s). The original flat word bank is kept as the **"Wowlo Mix"** category.

Puzzles may be **single words or phrases**. Only **A–Z letters are guessed**;
spaces and punctuation (e.g. the apostrophe in `VALENTINE'S DAY`) are **always
shown for free**, so phrases like `ICE CREAM` render correctly. The server
compares letters-only (case- and space-insensitive) for whole-puzzle solves, so
a kid can solve `ICE CREAM` by typing `ice cream` or `icecream`. On the play
screen each word's blanks stay together (only the gaps between words wrap), and
the chosen category is shown as a hint. `start` takes a `category` (unknown →
422; absent → Surprise Me); the resolved category is returned in the state (safe
— it's a hint, not the answer).

## Tests

`tests/Feature/HangmanGameTest.php` — the secret word is never leaked on start,
server-side marking (reveal / wrong / win / lose), whole-word solve, **phrases**
(spaces shown free, letters-only win + solve), **categories** (chosen category is
used + reported, unknown category → 422), the wheel
**effect** actions (`reveal` without a wrong, `lose_guess` draws a part, reveal
can win, bad type → 422), wheel ownership stamping, `is_standard` ignored for
non-admins, standard-is-admin-only, cross-tenant 404s, and students blocked from
wheel management.

> Note: these are AJAX endpoints but the app only renders JSON exceptions for
> `api/*` (see `bootstrap/app.php`), so `guess`/`effect` **validate manually and
> `abort(422)`** rather than `$request->validate()` (which would 302-redirect).

## Design

Wowlo brand (Nunito, rounded-2xl, Heroicons, no emojis in chrome — the panda 🐼
mascot aside) with an **emerald/teal** play accent and an **amber** wheel, so it
reads distinctly from Spelling Meow (orange) and Multiplication Rabbit
(green/teal review). Respects `prefers-reduced-motion`.
