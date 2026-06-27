<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\MultiplicationAttempt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

/**
 * Multiplication Rabbit — student side (see docs/multiplication-game.md).
 *
 * The student picks a level (a digit-count difficulty band) + how many
 * questions, the client generates the factor pairs within the level's ranges,
 * and the SERVER marks the responses (a * b) — validating every submitted
 * factor against config/multiplication-levels.php and recomputing the score
 * (never trusting a client total). Unlike Spelling Meow there is no secret to
 * hide: a multiplication question IS its own answer, so the range validation +
 * server-side mark are what keep the stored score trustworthy. A student can
 * only ever see/touch their own attempts (student_id); anything else 404s.
 */
class MultiplicationController extends Controller
{
    /** The play screen — level select + the Alpine-driven game. */
    public function play(): View
    {
        $levelDefs = config('multiplication-levels.levels', []);
        $mixed     = config('multiplication-levels.mixed_level', 'Mixed');

        // Real (generated) levels, in config order, each with its factor ranges.
        $levels = collect($levelDefs)
            ->map(fn (array $ranges, string $label) => [
                'label'   => $label,
                'enabled' => true,
                'ranges'  => $ranges,
            ])
            ->values();

        return view('student.games.multiplication.play', [
            'levels'         => $levels,
            'rangesByLevel'  => $levelDefs,
            'mixedLevel'     => $mixed,
            'mixedEnabled'   => $levelDefs !== [],
            'realLevels'     => array_keys($levelDefs),
            'questionCounts' => config('multiplication-levels.question_counts', [10, 15, 20]),
            'defaultCount'   => (int) config('multiplication-levels.default_count', 10),
            'rabbitImage'    => $this->rabbitImage(),
            'backgrounds'    => $this->backgroundImages(),
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
            'items'              => ['required', 'array', 'min:1', 'max:20'],
            'items.*.a'          => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.b'          => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.response'   => ['nullable', 'integer', 'min:0', 'max:9999999'],
        ]);

        // Recompute correctness + score server-side. Each factor pair must fall
        // within the chosen level's allowed digit ranges (rejects tampered or
        // out-of-range factors); the product is the answer.
        $results = [];
        $correct = 0;

        foreach ($validated['items'] as $item) {
            $a = (int) $item['a'];
            $b = (int) $item['b'];

            if (! $this->pairAllowed($validated['level'], $a, $b)) {
                continue; // out-of-range for this level — ignore it
            }

            $answer    = $a * $b;
            $response  = array_key_exists('response', $item) && $item['response'] !== null
                ? (int) $item['response']
                : null;
            $isCorrect = $response !== null && $response === $answer;

            $results[] = [
                'a'          => $a,
                'b'          => $b,
                'answer'     => $answer,
                'response'   => $response,
                'is_correct' => $isCorrect,
            ];

            $correct += $isCorrect ? 1 : 0;
        }

        abort_if(count($results) === 0, 422);

        $total = count($results);

        $attempt = MultiplicationAttempt::create([
            'student_id'      => auth()->id(),
            'tutor_id'        => $tutorId,
            'level'           => $validated['level'],
            'total_questions' => $total,
            'correct_count'   => $correct,
            'score_percent'   => (int) round($correct / $total * 100),
            'results'         => $results,
        ]);

        return redirect()->route('student.games.multiplication.show', $attempt)
            ->with('status', 'Round complete! Write your reflection below to finish.');
    }

    /** My Progress — the student's past rounds. */
    public function progress(): View
    {
        $attempts = MultiplicationAttempt::where('student_id', auth()->id())
            ->latest('id')
            ->paginate(15);

        return view('student.games.multiplication.progress', compact('attempts'));
    }

    /** Results / detail for one round (student writes their reflection here). */
    public function show(MultiplicationAttempt $attempt): View
    {
        $this->ensureOwned($attempt);
        $attempt->load('feedbackBy');

        return view('student.games.multiplication.show', compact('attempt'));
    }

    /** Save the student's mandatory learning reflection. */
    public function reflection(Request $request, MultiplicationAttempt $attempt): RedirectResponse
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

        return redirect()->route('student.games.multiplication.show', $attempt)
            ->with('status', 'Reflection saved.');
    }

    /** All playable level labels, including the virtual mixed level. */
    private function playableLevels(): array
    {
        $labels = array_keys(config('multiplication-levels.levels', []));

        if ($labels !== []) {
            $labels[] = config('multiplication-levels.mixed_level', 'Mixed');
        }

        return $labels;
    }

    /**
     * Is this factor pair valid for the level? A normal level has one allowed
     * (a-range, b-range) combo; the mixed level allows any real level's combo.
     */
    private function pairAllowed(string $level, int $a, int $b): bool
    {
        foreach ($this->rangesFor($level) as $combo) {
            [$ra, $rb] = [$combo['a'], $combo['b']];
            if ($a >= $ra[0] && $a <= $ra[1] && $b >= $rb[0] && $b <= $rb[1]) {
                return true;
            }
        }

        return false;
    }

    /**
     * The allowed (a-range, b-range) combos for a level. One for a real level;
     * every real level's combo for the virtual mixed level.
     *
     * @return list<array{a: array{int, int}, b: array{int, int}}>
     */
    private function rangesFor(string $level): array
    {
        $defs = config('multiplication-levels.levels', []);

        if ($level === config('multiplication-levels.mixed_level', 'Mixed')) {
            return array_values($defs);
        }

        return isset($defs[$level]) ? [$defs[$level]] : [];
    }

    /** Guard: the attempt must belong to the acting student (404, not 403). */
    private function ensureOwned(MultiplicationAttempt $attempt): void
    {
        abort_unless($attempt->student_id === auth()->id(), 404);
    }

    /** The rabbit mascot, or null if it hasn't been added yet (view falls back to an icon). */
    private function rabbitImage(): ?string
    {
        $path = 'images/games/multiplication/3d-rabbit.png';

        return File::exists(public_path($path)) ? asset($path) : null;
    }

    /**
     * Soft background images for the play area, from
     * public/images/games/multiplication/backgrounds. Empty → gradient fallback.
     *
     * @return list<string>
     */
    private function backgroundImages(): array
    {
        $dir = public_path('images/games/multiplication/backgrounds');
        if (! File::isDirectory($dir)) {
            return [];
        }

        return collect(File::files($dir))
            ->filter(fn ($f) => in_array(strtolower($f->getExtension()), ['jpg', 'jpeg', 'png', 'webp', 'avif'], true))
            ->map(fn ($f) => asset('images/games/multiplication/backgrounds/' . $f->getFilename()))
            ->values()
            ->all();
    }
}
