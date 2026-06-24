<?php

use App\Models\SpellingAttempt;

/**
 * Spelling Meow (Slice 14, see docs/spelling-game.md). Covers the server-side
 * marking (never trusts the client), the mandatory reflection, the tutor
 * feedback loop, and tenant isolation between students/tutors.
 */

// ---- Play screen ------------------------------------------------------------

it('shows playable levels and disables the unbuilt ones', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);

    $this->actingAs($studentUser)
        ->get(route('student.games.spelling.play'))
        ->assertOk()
        ->assertSee('Primary 1')
        ->assertSee('Secondary 3')          // present...
        ->assertSee('Building in progress..'); // ...but flagged as unbuilt
});

it('never sends the correct spelling to the browser during play', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);
    $first = config('spelling-words.levels.Primary 1')[0];

    // The payload exposes the misspelled prompt + the length, never the answer.
    $this->actingAs($studentUser)
        ->get(route('student.games.spelling.play'))
        ->assertOk()
        ->assertSee($first['shown'])
        ->assertDontSee('"answer"', false); // no answer key reaches the browser
});

// ---- Marking (server-authoritative) ----------------------------------------

it('marks a finished round server-side and stores the result', function () {
    $tutorUser   = tutor();
    $studentUser = student(['tutor_id' => $tutorUser->id]);
    [$a, $b] = config('spelling-words.levels.Primary 1');

    $this->actingAs($studentUser)->post(route('student.games.spelling.finish'), [
        'level' => 'Primary 1',
        'items' => [
            ['shown' => $a['shown'], 'response' => $a['answer']],  // correct
            ['shown' => $b['shown'], 'response' => 'wrongword'],   // wrong
        ],
    ])->assertRedirect();

    $attempt = SpellingAttempt::first();
    expect($attempt->student_id)->toBe($studentUser->id)
        ->and($attempt->tutor_id)->toBe($tutorUser->id)   // owner stamped server-side
        ->and($attempt->total_questions)->toBe(2)
        ->and($attempt->correct_count)->toBe(1)
        ->and($attempt->score_percent)->toBe(50);
});

it('marks spelling case-insensitively', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);
    $first = config('spelling-words.levels.Primary 1')[0];

    $this->actingAs($studentUser)->post(route('student.games.spelling.finish'), [
        'level' => 'Primary 1',
        'items' => [['shown' => $first['shown'], 'response' => strtoupper($first['answer'])]],
    ]);

    expect(SpellingAttempt::first()->correct_count)->toBe(1);
});

it('ignores prompts that are not real words for the level (no cheating in extras)', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);
    $first = config('spelling-words.levels.Primary 1')[0];

    $this->actingAs($studentUser)->post(route('student.games.spelling.finish'), [
        'level' => 'Primary 1',
        'items' => [
            ['shown' => $first['shown'], 'response' => $first['answer']],
            ['shown' => 'not-a-real-prompt', 'response' => 'whatever'],
        ],
    ]);

    // Only the legit word counts.
    expect(SpellingAttempt::first()->total_questions)->toBe(1);
});

// ---- Mixed Primary ----------------------------------------------------------

it('offers the Mixed Primary level', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);

    $this->actingAs($studentUser)
        ->get(route('student.games.spelling.play'))
        ->assertOk()
        ->assertSee('Mixed Primary (Primary 1 - 6)');
});

it('marks a Mixed Primary round using words from across Primary 1-6', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);
    $p1 = config('spelling-words.levels.Primary 1')[0];
    $p6 = config('spelling-words.levels.Primary 6')[0];

    $this->actingAs($studentUser)->post(route('student.games.spelling.finish'), [
        'level' => 'Mixed Primary (Primary 1 - 6)',
        'items' => [
            ['shown' => $p1['shown'], 'response' => $p1['answer']],  // from P1
            ['shown' => $p6['shown'], 'response' => $p6['answer']],  // from P6
        ],
    ])->assertRedirect();

    $attempt = SpellingAttempt::first();
    expect($attempt->level)->toBe('Mixed Primary (Primary 1 - 6)')
        ->and($attempt->total_questions)->toBe(2)
        ->and($attempt->correct_count)->toBe(2)
        ->and($attempt->score_percent)->toBe(100);
});

// ---- Reflection (required) --------------------------------------------------

it('requires a reflection', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);
    $attempt = makeAttempt(['student_id' => $studentUser->id, 'tutor_id' => $studentUser->tutor_id]);

    $this->actingAs($studentUser)
        ->patch(route('student.games.spelling.reflection', $attempt), ['reflection' => ''])
        ->assertSessionHasErrors('reflection');

    $this->actingAs($studentUser)
        ->patch(route('student.games.spelling.reflection', $attempt), ['reflection' => 'I will slow down on tricky vowels.'])
        ->assertRedirect();

    expect($attempt->fresh()->reflection)->toBe('I will slow down on tricky vowels.');
});

it('rejects a whitespace-only reflection but accepts any non-empty text (no minimum)', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);
    $attempt = makeAttempt(['student_id' => $studentUser->id, 'tutor_id' => $studentUser->tutor_id]);

    $this->actingAs($studentUser)
        ->patch(route('student.games.spelling.reflection', $attempt), ['reflection' => '   '])
        ->assertSessionHasErrors('reflection');

    // A single character is enough — there is no minimum length.
    $this->actingAs($studentUser)
        ->patch(route('student.games.spelling.reflection', $attempt), ['reflection' => 'x'])
        ->assertRedirect();

    expect($attempt->fresh()->reflection)->toBe('x');
});

it('shows the blocking reflection gate — with the marks visible — until a reflection exists', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);
    $attempt = makeAttempt(['student_id' => $studentUser->id, 'tutor_id' => $studentUser->tutor_id, 'reflection' => null]);

    // Gate is shown, AND the score is visible before reflecting (50% from makeAttempt).
    $this->actingAs($studentUser)
        ->get(route('student.games.spelling.show', $attempt))
        ->assertSee('Round complete!')
        ->assertSee('50%')
        ->assertSee('Your words');

    $attempt->update(['reflection' => 'Done!']);

    $this->actingAs($studentUser)
        ->get(route('student.games.spelling.show', $attempt))
        ->assertDontSee('Round complete!');
});

// ---- Tutor feedback ---------------------------------------------------------

it('lets the owning tutor leave feedback', function () {
    $tutorUser   = tutor();
    $studentUser = student(['tutor_id' => $tutorUser->id]);
    $attempt = makeAttempt(['student_id' => $studentUser->id, 'tutor_id' => $tutorUser->id]);

    $this->actingAs($tutorUser)
        ->post(route('tutor.games.spelling.feedback', $attempt), ['feedback' => 'Great improvement!'])
        ->assertRedirect();

    expect($attempt->fresh()->feedback)->toBe('Great improvement!')
        ->and($attempt->fresh()->feedback_by)->toBe($tutorUser->id);
});

// ---- Tenant isolation -------------------------------------------------------

it('shows a tutor only their own students\' spelling rounds', function () {
    $tutorA = tutor();
    $tutorB = tutor();
    $studentA = student(['tutor_id' => $tutorA->id, 'name' => 'Mine Student']);
    $studentB = student(['tutor_id' => $tutorB->id, 'name' => 'Their Student']);
    makeAttempt(['student_id' => $studentA->id, 'tutor_id' => $tutorA->id]);
    makeAttempt(['student_id' => $studentB->id, 'tutor_id' => $tutorB->id]);

    $this->actingAs($tutorA)
        ->get(route('tutor.games.spelling.index'))
        ->assertOk()
        ->assertSee('Mine Student')
        ->assertDontSee('Their Student');
});

it('404s on cross-tenant spelling access (IDOR)', function () {
    $tutorA   = tutor();
    $tutorB   = tutor();
    $studentA = student(['tutor_id' => $tutorA->id]);
    $studentB = student(['tutor_id' => $tutorB->id]);
    $attemptB = makeAttempt(['student_id' => $studentB->id, 'tutor_id' => $tutorB->id]);

    // Another tutor can't view or comment on it.
    $this->actingAs($tutorA)->get(route('tutor.games.spelling.show', $attemptB))->assertNotFound();
    $this->actingAs($tutorA)->post(route('tutor.games.spelling.feedback', $attemptB), ['feedback' => 'x'])->assertNotFound();

    // Another student can't view it or write a reflection on it.
    $this->actingAs($studentA)->get(route('student.games.spelling.show', $attemptB))->assertNotFound();
    $this->actingAs($studentA)->patch(route('student.games.spelling.reflection', $attemptB), ['reflection' => 'sneaky'])->assertNotFound();
});

/** A persisted attempt with sane defaults, overridable per test. */
function makeAttempt(array $attrs = []): SpellingAttempt
{
    return SpellingAttempt::create(array_merge([
        'level'           => 'Primary 1',
        'total_questions' => 2,
        'correct_count'   => 1,
        'score_percent'   => 50,
        'results'         => [
            ['shown' => 'freind', 'answer' => 'friend', 'response' => 'friend', 'is_correct' => true],
            ['shown' => 'shcool', 'answer' => 'school', 'response' => 'skool', 'is_correct' => false],
        ],
    ], $attrs));
}
