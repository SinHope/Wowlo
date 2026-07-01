<?php

namespace App\Http\Controllers;

use App\Models\HangmanWheel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Hangman Wheel Panda — gameplay (see docs/hangman-wheel-panda.md).
 *
 * Shared by all roles. The game is SERVER-AUTHORITATIVE: the secret word is
 * picked here and kept in the session — it is NEVER sent to the browser until
 * the round is over (won/lost), so a player can't peek at the answer in the
 * page source (contrast with Multiplication Rabbit, which has no secret). The
 * client only ever sees the masked word (revealed letters + blanks), the wrong
 * count, and the status. Each wrong letter OR wrong whole-word guess draws one
 * more panda part; count(config parts) wrong = a loss.
 *
 * No DB attempts are stored — this is throwaway play (like Roll the Dice). The
 * only persistent, tenant-scoped data is the wheels (HangmanWheel), managed in
 * Tutor\HangmanWheelController.
 */
class HangmanController extends Controller
{
    private const SESSION_KEY = 'hangman_game';

    /** The play screen — pick a wheel, then start a round (the category is random). */
    public function play(): View
    {
        $wheels = HangmanWheel::availableTo(auth()->user())
            ->orderByRaw("CASE WHEN type = 'standard' THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'slices']);

        return view('games.hangman.play', [
            'wheels'          => $wheels,
            'maxWrong'        => $this->maxWrong(),
            'canManageWheels' => auth()->user()->actsAsTutor(),
        ]);
    }

    /**
     * Start a new round. The category is chosen at RANDOM (the student doesn't
     * pick) — the picked category name is returned + shown as a hint. A specific
     * category may still be requested (must exist — a tampered name 422s).
     */
    public function start(Request $request): JsonResponse
    {
        $cats = (array) config('hangman-words.categories', []);
        abort_if($cats === [], 422, 'No categories are configured.');

        $category = $request->input('category');
        $category = is_string($category) ? trim($category) : '';

        if ($category === '') {
            $names = array_keys($cats);
            $category = $names[array_rand($names)];   // random category
        } else {
            abort_unless(array_key_exists($category, $cats), 422, 'Unknown category.');
        }

        $puzzles = $this->cleanPuzzles($cats[$category]);
        abort_if($puzzles === [], 422, 'No puzzles in this category.');

        $word = strtoupper($puzzles[array_rand($puzzles)]);

        session([self::SESSION_KEY => [
            'word'     => $word,
            'category' => $category,
            'guessed'  => [],
            'wrong'    => 0,
            'status'   => 'playing',
        ]]);

        return response()->json($this->publicState());
    }

    /** Guess a single letter or the whole word. The server marks it. */
    public function guess(Request $request): JsonResponse
    {
        $state = session(self::SESSION_KEY);
        abort_if(! is_array($state), 422, 'No game in progress. Start a round first.');

        // Already finished — just echo the (revealed) state back.
        if ($state['status'] !== 'playing') {
            return response()->json($this->publicState());
        }

        // Manual validation + abort(422): these are AJAX/JSON endpoints, but the
        // app only renders JSON exceptions for api/* (see bootstrap/app.php), so
        // $request->validate() would 302-redirect instead of returning 422.
        $rawWord = $request->input('word');
        $rawLetter = $request->input('letter');

        $wordGuess = is_string($rawWord) && trim($rawWord) !== '' ? strtoupper(trim($rawWord)) : null;
        $letter = is_string($rawLetter) && $rawLetter !== '' ? strtoupper($rawLetter) : null;

        if ($wordGuess !== null) {
            abort_unless(strlen($wordGuess) <= 100, 422);

            // Compare letters only (ignore spaces/punctuation + case) so a kid can
            // solve "ICE CREAM" by typing "ice cream" or "icecream".
            if ($this->lettersOnly($wordGuess) === $this->lettersOnly($state['word'])
                && $this->lettersOnly($wordGuess) !== '') {
                // Solved — reveal every letter.
                $state['guessed'] = $this->letters($state['word']);
                $state['status'] = 'won';
            } else {
                $state['wrong']++;
            }
        } elseif ($letter !== null) {
            abort_unless(ctype_alpha($letter) && strlen($letter) === 1, 422);

            if (! in_array($letter, $state['guessed'], true)) {
                $state['guessed'][] = $letter;
                if (! str_contains($state['word'], $letter)) {
                    $state['wrong']++;
                }
            }
        } else {
            abort(422, 'Provide a letter or a word to guess.');
        }

        // Resolve the outcome (only if the word-guess didn't already win it).
        if ($state['status'] === 'playing') {
            if ($state['wrong'] >= $this->maxWrong()) {
                $state['status'] = 'lost';
            } elseif ($this->allRevealed($state)) {
                $state['status'] = 'won';
            }
        }

        session([self::SESSION_KEY => $state]);

        return response()->json($this->publicState());
    }

    /**
     * Apply a wheel "smart effect" that changes server state: reveal a letter,
     * reveal a vowel, or lose a guess (draw a panda part). Kept server-side so
     * the secret word + the score stay authoritative (the client can't reveal
     * letters or fake the panda). Spin-bonus *guess counts* are pure client-side
     * turn pacing and don't need the server.
     */
    public function effect(Request $request): JsonResponse
    {
        $state = session(self::SESSION_KEY);
        abort_if(! is_array($state), 422, 'No game in progress.');

        if ($state['status'] !== 'playing') {
            return response()->json($this->publicState());
        }

        // Manual validation (see guess() — app only JSON-renders api/* routes).
        $type = (string) $request->input('type');
        abort_unless(in_array($type, ['reveal', 'reveal_vowel', 'lose_guess'], true), 422);

        if ($type === 'lose_guess') {
            $state['wrong']++;
        } else {
            // Reveal a still-hidden letter (a vowel first, for reveal_vowel).
            $hidden = array_values(array_diff($this->letters($state['word']), $state['guessed']));

            if ($type === 'reveal_vowel') {
                $vowels = array_values(array_filter($hidden, fn ($c) => in_array($c, ['A', 'E', 'I', 'O', 'U'], true)));
                $pick = $vowels[0] ?? ($hidden[0] ?? null);
            } else {
                $pick = $hidden !== [] ? $hidden[array_rand($hidden)] : null;
            }

            if ($pick !== null) {
                $state['guessed'][] = $pick;
            }
        }

        if ($state['wrong'] >= $this->maxWrong()) {
            $state['status'] = 'lost';
        } elseif ($this->allRevealed($state)) {
            $state['status'] = 'won';
        }

        session([self::SESSION_KEY => $state]);

        return response()->json($this->publicState());
    }

    /** Max wrong guesses allowed = the number of panda parts. */
    private function maxWrong(): int
    {
        return count((array) config('hangman-words.parts', []));
    }

    /** Distinct A–Z letters of a puzzle (ignores spaces/punctuation). */
    private function letters(string $word): array
    {
        $only = $this->lettersOnly($word);

        return $only === '' ? [] : array_values(array_unique(str_split($only)));
    }

    /** The puzzle stripped to A–Z, uppercase — used for guessing + solve comparison. */
    private function lettersOnly(string $word): string
    {
        return preg_replace('/[^A-Z]/', '', strtoupper($word));
    }

    /** Keep only non-empty string puzzles that contain at least one letter. */
    private function cleanPuzzles(mixed $puzzles): array
    {
        return array_values(array_filter(
            (array) $puzzles,
            fn ($p) => is_string($p) && $this->lettersOnly($p) !== ''
        ));
    }

    /** Every distinct letter of the secret word has been guessed. */
    private function allRevealed(array $state): bool
    {
        foreach ($this->letters($state['word']) as $ch) {
            if (! in_array($ch, $state['guessed'], true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * The client-safe view of the game: the mask (each position is the revealed
     * letter or null), the wrong count, the status — and the full word ONLY once
     * the round is over.
     */
    private function publicState(): array
    {
        $state = session(self::SESSION_KEY);

        if (! is_array($state)) {
            return ['status' => 'none'];
        }

        $finished = $state['status'] !== 'playing';

        // Letters are masked until guessed; spaces + punctuation are always shown
        // (so phrases like "ICE CREAM" or "VALENTINE'S DAY" render correctly).
        $mask = array_map(
            fn (string $ch) => ! ctype_alpha($ch)
                ? $ch
                : (in_array($ch, $state['guessed'], true) ? $ch : null),
            str_split($state['word'])
        );

        return [
            'status'   => $state['status'],
            'category' => $state['category'] ?? '',
            'wrong'    => $state['wrong'],
            'maxWrong' => $this->maxWrong(),
            'length'   => strlen($state['word']),
            'mask'     => $mask,
            'guessed'  => $state['guessed'],
            'word'     => $finished ? $state['word'] : null,
        ];
    }
}
