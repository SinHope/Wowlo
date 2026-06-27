# Roll the Dice — Games

A tiny, purely-for-fun second game under the **Games** tab (after
[Spelling Meow](spelling-game.md)). A single tap rolls a 3D die — **no scoring, no
attempts, no database, no tenancy** to reason about. It's shared by **all roles**
(super_admin, tutor, student), so it lives on one plain authed route rather than under
the role-scoped groups.

## Where it lives

- **Route:** `GET /games/roll-the-dice` → `Route::view('/games/roll-the-dice', 'games.roll-the-dice')`
  inside the auth middleware group, `name('games.roll-the-dice')`. No controller — it's a
  static view.
- **View:** `resources/views/games/roll-the-dice.blade.php` — one self-contained Alpine
  component plus a scoped `<style>` block (the CSS 3D cube + pips). No build-time JS/CSS.
- **Image:** `public/images/games/roll-the-dice/3d-dice-studio.jpg` — decorative dice shown
  either side of the title.

## How it works

- The die is a **CSS 3D cube** (`transform-style: preserve-3d`, six `.roll-the-dice-face`
  divs with pip grids). **Roll** bumps `rx`/`ry` by several full turns plus a random
  quarter-turn so the cube keeps spinning forward and lands on a random face; a 1.5 s
  `transition` animates the tumble and the button is disabled while `rolling`.
- The landing face is **purely visual** — nothing reads or records the rolled number.
- A **synthesised rattle** (Web Audio: ~9 short band-passed white-noise "clacks" over the
  tumble) plays on each roll — no audio file or library. The same no-asset Web Audio
  approach as the Spelling Meow typing blip.
- `@media (prefers-reduced-motion: reduce)` shortens the spin; audio failures are swallowed
  silently (`try/catch`).

## Changing it

It's deliberately trivial — edit the one blade file. Because there's no data or
cross-tenant surface, **no isolation/feature tests are needed** (contrast with Spelling
Meow and Resources). If a future game *does* store attempts, follow the Spelling Meow
shape instead (role-scoped routes + tenancy + `MultiTutorTest` coverage).
