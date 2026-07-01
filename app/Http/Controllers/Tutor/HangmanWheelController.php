<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Models\HangmanWheel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Hangman Wheel Panda — wheel management (see docs/hangman-wheel-panda.md).
 *
 * Tutors (and the super_admin acting as a tutor) author CUSTOM wheels owned by
 * themselves (tutor_id = their id) and visible only to their own students. The
 * super_admin may additionally author STANDARD wheels (tutor_id NULL) that
 * everyone can play with.
 *
 * Tenancy: a tutor can only ever see/edit/delete their own custom wheels; a
 * standard wheel can only be touched by the super_admin. Cross-tenant access
 * 404s (never 403 — don't leak IDs), per the project guardrails.
 */
class HangmanWheelController extends Controller
{
    /** Min/max slices on a wheel, and max characters per slice label. */
    private const MIN_SLICES = 2;
    private const MAX_SLICES = 12;
    private const MAX_SLICE_LEN = 60;

    /** Wheels this user manages: their own custom wheels (+ standard wheels for the admin). */
    public function index(): View
    {
        $user = auth()->user();

        $wheels = HangmanWheel::query()
            ->where(function ($q) use ($user) {
                $q->where('tutor_id', $user->id);          // own custom wheels
                if ($user->isSuperAdmin()) {
                    $q->orWhere('type', 'standard');        // + the global house wheels
                }
            })
            ->orderByRaw("CASE WHEN type = 'standard' THEN 0 ELSE 1 END")
            ->latest()
            ->get();

        return view('tutor.games.hangman.wheels.index', [
            'wheels'      => $wheels,
            'canStandard' => $user->isSuperAdmin(),
        ]);
    }

    public function create(): View
    {
        return view('tutor.games.hangman.wheels.create', [
            'canStandard'   => auth()->user()->isSuperAdmin(),
            'maxSlices'     => self::MAX_SLICES,
            'sliceLength'   => self::MAX_SLICE_LEN,
            'suggestions'   => $this->suggestions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        HangmanWheel::create([
            'name'       => $data['name'],
            'type'       => $data['type'],
            'created_by' => auth()->id(),
            // Standard wheels are global (no owning tutor); custom wheels are
            // owned by the acting user. tutor_id is set SERVER-SIDE only.
            'tutor_id'   => $data['type'] === 'standard' ? null : auth()->id(),
            'slices'     => $data['slices'],
        ]);

        return redirect()->route('tutor.games.hangman.wheels.index')
            ->with('status', 'Wheel created.');
    }

    public function edit(HangmanWheel $wheel): View
    {
        $this->ensureCanManage($wheel);

        return view('tutor.games.hangman.wheels.edit', [
            'wheel'         => $wheel,
            'canStandard'   => auth()->user()->isSuperAdmin(),
            'maxSlices'     => self::MAX_SLICES,
            'sliceLength'   => self::MAX_SLICE_LEN,
            'suggestions'   => $this->suggestions(),
        ]);
    }

    public function update(Request $request, HangmanWheel $wheel): RedirectResponse
    {
        $this->ensureCanManage($wheel);

        $data = $this->validated($request);

        // A standard wheel stays standard (and global); a custom wheel can only
        // be promoted to standard by the super_admin. tutor_id follows the type.
        $type = $wheel->isStandard() ? 'standard' : $data['type'];

        $wheel->update([
            'name'     => $data['name'],
            'type'     => $type,
            'tutor_id' => $type === 'standard' ? null : ($wheel->tutor_id ?? auth()->id()),
            'slices'   => $data['slices'],
        ]);

        return redirect()->route('tutor.games.hangman.wheels.index')
            ->with('status', 'Wheel updated.');
    }

    public function destroy(HangmanWheel $wheel): RedirectResponse
    {
        $this->ensureCanManage($wheel);

        $wheel->delete();

        return redirect()->route('tutor.games.hangman.wheels.index')
            ->with('status', 'Wheel deleted.');
    }

    /**
     * Validate the form and normalise slices (trim, drop blanks). The 'type' is
     * forced to 'custom' unless the acting user is the super_admin asking for a
     * standard wheel — a non-admin can never create a global wheel.
     */
    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'name'      => ['required', 'string', 'max:80'],
            'slices'    => ['required', 'array', 'min:' . self::MIN_SLICES, 'max:' . self::MAX_SLICES],
            'slices.*'  => ['nullable', 'string', 'max:' . self::MAX_SLICE_LEN],
            'is_standard' => ['nullable', 'boolean'],
        ]);

        $slices = collect($validated['slices'])
            ->map(fn ($s) => trim((string) $s))
            ->filter()
            ->values()
            ->all();

        if (count($slices) < self::MIN_SLICES) {
            abort(422);
        }

        $wantsStandard = $request->boolean('is_standard') && auth()->user()->isSuperAdmin();

        return [
            'name'   => $validated['name'],
            'type'   => $wantsStandard ? 'standard' : 'custom',
            'slices' => $slices,
        ];
    }

    /** Guard: only the owner (custom) or the super_admin (standard) may manage it. 404 otherwise. */
    private function ensureCanManage(HangmanWheel $wheel): void
    {
        if ($wheel->isStandard()) {
            abort_unless(auth()->user()->isSuperAdmin(), 404);
        } else {
            abort_unless($wheel->tutor_id === auth()->id(), 404);
        }
    }

    /** Example slice ideas shown under the builder (not enforced — just flavour). */
    private function suggestions(): array
    {
        return [
            '+1 Free Guess', '+2 Free Guesses', 'Reveal a Letter', 'Reveal a Vowel',
            'Spin Again', 'Skip a Turn', 'Lose a Guess', 'Mystery Letter',
            'Good Luck!', 'No Help', 'Double Trouble', 'Free Pass',
        ];
    }
}
