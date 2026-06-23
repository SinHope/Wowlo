<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\SpellingAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

/**
 * Spelling Meow — student side (see docs/spelling-game.md).
 *
 * The student plays a round (level → misspelled prompts → letter blanks), the
 * SERVER marks the responses against config/spelling-words.php (the correct
 * spellings are never sent to the browser during play), then the student writes
 * a mandatory reflection on the results page. A student can only ever see/touch
 * their own attempts (student_id, checked server-side); anything else 404s.
 */
class SpellingController extends Controller
{
    /** Virtual level: 30 random words drawn from across Primary 1–6. */
    private const MIXED_PRIMARY  = 'Mixed Primary (Primary 1 - 6)';
    private const MIXED_PER_ROUND = 30;

    /** The play screen — level select + the Alpine-driven game. */
    public function play(): View
    {
        $wordLevels = config('spelling-words.levels', []);

        // Every level label (from the canonical list) flagged playable iff it
        // has words configured. Disabled ones render struck-through in the UI.
        $levels = collect(config('wowlo.levels'))
            ->map(fn (string $label) => [
                'label'   => $label,
                'group'   => str_starts_with($label, 'Primary') ? 'Primary' : 'Secondary',
                'enabled' => ! empty($wordLevels[$label] ?? []),
            ]);

        // The virtual "Mixed Primary" level sits at the end of the Primary group.
        $levels->push([
            'label'   => self::MIXED_PRIMARY,
            'group'   => 'Primary',
            'enabled' => $this->wordsForLevel(self::MIXED_PRIMARY) !== [],
        ]);

        // Per playable level, expose ONLY the misspelled prompt + the correct
        // word's length (for the blanks). Never the answer.
        $prompts = fn (array $words) => collect($words)
            ->map(fn (array $w) => ['shown' => $w['shown'], 'length' => mb_strlen($w['answer'])])
            ->values();

        $wordsByLevel = collect($wordLevels)->map($prompts)->toArray();
        $wordsByLevel[self::MIXED_PRIMARY] = $prompts($this->wordsForLevel(self::MIXED_PRIMARY))->all();

        return view('student.games.spelling.play', [
            'levels'        => $levels->values(),
            'wordsByLevel'  => $wordsByLevel,
            'perRound'      => (int) config('spelling-words.questions_per_round', 10),
            'mixedLevel'    => self::MIXED_PRIMARY,
            'mixedPerRound' => self::MIXED_PER_ROUND,
            'catImage'      => asset('images/games/spelling/3d-smart-cat.png'),
            'backgrounds'   => $this->backgroundImages(),
        ]);
    }

    /** Mark a finished round, persist it, and go to the results page. */
    public function finish(Request $request): RedirectResponse
    {
        // A student always belongs to a tutor — the owning tutor of the record.
        $tutorId = auth()->user()->tutor_id;
        abort_if($tutorId === null, 404);

        $validated = $request->validate([
            'level'              => ['required', 'string', 'in:' . implode(',', $this->playableLevels())],
            'items'              => ['required', 'array', 'min:1', 'max:200'],
            'items.*.shown'      => ['required', 'string', 'max:100'],
            'items.*.response'   => ['nullable', 'string', 'max:100'],
        ]);

        // Look the correct spelling up server-side by the misspelled prompt, then
        // recompute correctness + score (never trust a client-submitted total).
        $pairs   = collect($this->wordsForLevel($validated['level']));
        $results = [];
        $correct = 0;

        foreach ($validated['items'] as $item) {
            $pair = $pairs->firstWhere('shown', $item['shown']);
            if ($pair === null) {
                continue; // unknown prompt for this level — ignore it
            }

            $answer    = $pair['answer'];
            $response  = trim($item['response'] ?? '');
            $isCorrect = $response !== '' && mb_strtolower($response) === mb_strtolower($answer);

            $results[] = [
                'shown'      => $item['shown'],
                'answer'     => $answer,
                'response'   => $response,
                'is_correct' => $isCorrect,
            ];

            $correct += $isCorrect ? 1 : 0;
        }

        abort_if(count($results) === 0, 422);

        $total = count($results);

        $attempt = SpellingAttempt::create([
            'student_id'      => auth()->id(),
            'tutor_id'        => $tutorId,
            'level'           => $validated['level'],
            'total_questions' => $total,
            'correct_count'   => $correct,
            'score_percent'   => (int) round($correct / $total * 100),
            'results'         => $results,
        ]);

        return redirect()->route('student.games.spelling.show', $attempt)
            ->with('status', 'Round complete! Write your reflection below to finish.');
    }

    /** My Progress — the student's past rounds. */
    public function progress(): View
    {
        $attempts = SpellingAttempt::where('student_id', auth()->id())
            ->latest('id')
            ->paginate(15);

        return view('student.games.spelling.progress', compact('attempts'));
    }

    /** Results / detail for one round (student writes their reflection here). */
    public function show(SpellingAttempt $attempt): View
    {
        $this->ensureOwned($attempt);
        $attempt->load('feedbackBy');

        return view('student.games.spelling.show', compact('attempt'));
    }

    /** Save the student's mandatory learning reflection. */
    public function reflection(Request $request, SpellingAttempt $attempt): RedirectResponse
    {
        $this->ensureOwned($attempt);

        // Must write SOMETHING (no minimum length) — reject blank/whitespace-only.
        $request->merge(['reflection' => trim((string) $request->input('reflection'))]);
        $validated = $request->validate([
            'reflection' => ['required', 'string', 'max:5000'],
        ], [
            'reflection.required' => 'Please write your reflection to continue.',
        ]);

        $attempt->update(['reflection' => $validated['reflection']]);

        return redirect()->route('student.games.spelling.show', $attempt)
            ->with('status', 'Reflection saved.');
    }

    /**
     * Answer/shown pairs for a level. The virtual mixed level merges every
     * Primary level's words; a normal level returns its own configured list.
     *
     * @return list<array{answer: string, shown: string}>
     */
    private function wordsForLevel(string $level): array
    {
        $wordLevels = config('spelling-words.levels', []);

        if ($level === self::MIXED_PRIMARY) {
            return collect($wordLevels)
                ->filter(fn ($words, $label) => str_starts_with($label, 'Primary'))
                ->flatten(1)
                ->all();
        }

        return $wordLevels[$level] ?? [];
    }

    /** All playable level labels, including the virtual mixed level. */
    private function playableLevels(): array
    {
        $labels = collect(config('spelling-words.levels', []))->filter()->keys();

        if ($this->wordsForLevel(self::MIXED_PRIMARY) !== []) {
            $labels->push(self::MIXED_PRIMARY);
        }

        return $labels->all();
    }

    /** Guard: the attempt must belong to the acting student (404, not 403). */
    private function ensureOwned(SpellingAttempt $attempt): void
    {
        abort_unless($attempt->student_id === auth()->id(), 404);
    }

    /**
     * Soft background images for the play area, from public/images/games/spelling/backgrounds.
     * Returns web paths; empty array → the view falls back to a gradient.
     *
     * @return list<string>
     */
    private function backgroundImages(): array
    {
        $dir = public_path('images/games/spelling/backgrounds');
        if (! File::isDirectory($dir)) {
            return [];
        }

        return collect(File::files($dir))
            ->filter(fn ($f) => in_array(strtolower($f->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'avif'], true))
            ->map(fn ($f) => asset('images/games/spelling/backgrounds/' . $f->getFilename()))
            ->values()
            ->all();
    }
}
