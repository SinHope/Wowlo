<?php

use App\Models\MultiplicationAttempt;

/**
 * Multiplication Rabbit (Slice 17, see docs/multiplication-game.md). Covers the
 * server-side marking + factor range-validation (never trusts the client), the
 * mandatory reflection, the tutor feedback loop, and tenant isolation between
 * students/tutors.
 */

// ---- Play screen ------------------------------------------------------------

it('shows the levels and the mixed level on the play screen', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);

    $this->actingAs($studentUser)
        ->get(route('student.games.multiplication.play'))
        ->assertOk()
        ->assertSee('1 by 1 digit')
        ->assertSee('3 by 3 digits')
        ->assertSee('Mixed');
});

// ---- Marking (server-authoritative) ----------------------------------------

it('marks a finished round server-side and stores the result', function () {
    $tutorUser   = tutor();
    $studentUser = student(['tutor_id' => $tutorUser->id]);

    $this->actingAs($studentUser)->post(route('student.games.multiplication.finish'), [
        'level' => '2 by 2 digits',
        'items' => [
            ['a' => 12, 'b' => 11, 'response' => 132],  // correct (132)
            ['a' => 20, 'b' => 20, 'response' => 399],  // wrong (actual 400)
        ],
    ])->assertRedirect();

    $attempt = MultiplicationAttempt::first();
    expect($attempt->student_id)->toBe($studentUser->id)
        ->and($attempt->tutor_id)->toBe($tutorUser->id)   // owner stamped server-side
        ->and($attempt->total_questions)->toBe(2)
        ->and($attempt->correct_count)->toBe(1)
        ->and($attempt->score_percent)->toBe(50);
});

it('does not trust a client total — a blank answer is wrong', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);

    $this->actingAs($studentUser)->post(route('student.games.multiplication.finish'), [
        'level' => '1 by 1 digit',
        'items' => [
            ['a' => 7, 'b' => 1, 'response' => 7],   // correct — ×1 is allowed
            ['a' => 6, 'b' => 6, 'response' => null], // left blank → wrong
        ],
    ]);

    $attempt = MultiplicationAttempt::first();
    expect($attempt->total_questions)->toBe(2)
        ->and($attempt->correct_count)->toBe(1);
});

it('ignores factors outside the chosen level\'s range (no cheating with easy numbers)', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);

    $this->actingAs($studentUser)->post(route('student.games.multiplication.finish'), [
        'level' => '1 by 1 digit',
        'items' => [
            ['a' => 3, 'b' => 4, 'response' => 12],     // in range for 1×1
            ['a' => 50, 'b' => 50, 'response' => 2500], // out of range — dropped
        ],
    ]);

    // Only the legit, in-range question counts.
    expect(MultiplicationAttempt::first()->total_questions)->toBe(1);
});

it('marks a Mixed round using factors from across the levels', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);

    $this->actingAs($studentUser)->post(route('student.games.multiplication.finish'), [
        'level' => 'Mixed',
        'items' => [
            ['a' => 4, 'b' => 5, 'response' => 20],        // 1×1
            ['a' => 123, 'b' => 456, 'response' => 56088], // 3×3
        ],
    ])->assertRedirect();

    $attempt = MultiplicationAttempt::first();
    expect($attempt->level)->toBe('Mixed')
        ->and($attempt->total_questions)->toBe(2)
        ->and($attempt->correct_count)->toBe(2)
        ->and($attempt->score_percent)->toBe(100);
});

// ---- Reflection (required) --------------------------------------------------

it('requires a reflection', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);
    $attempt = makeMultAttempt(['student_id' => $studentUser->id, 'tutor_id' => $studentUser->tutor_id]);

    $this->actingAs($studentUser)
        ->patch(route('student.games.multiplication.reflection', $attempt), ['reflection' => ''])
        ->assertSessionHasErrors('reflection');

    $this->actingAs($studentUser)
        ->patch(route('student.games.multiplication.reflection', $attempt), ['reflection' => 'I will learn my 7 times table.'])
        ->assertRedirect();

    expect($attempt->fresh()->reflection)->toBe('I will learn my 7 times table.');
});

it('rejects a whitespace-only reflection but accepts any non-empty text (no minimum)', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);
    $attempt = makeMultAttempt(['student_id' => $studentUser->id, 'tutor_id' => $studentUser->tutor_id]);

    $this->actingAs($studentUser)
        ->patch(route('student.games.multiplication.reflection', $attempt), ['reflection' => '   '])
        ->assertSessionHasErrors('reflection');

    $this->actingAs($studentUser)
        ->patch(route('student.games.multiplication.reflection', $attempt), ['reflection' => 'x'])
        ->assertRedirect();

    expect($attempt->fresh()->reflection)->toBe('x');
});

it('shows the blocking reflection gate — with the marks visible — until a reflection exists', function () {
    $studentUser = student(['tutor_id' => tutor()->id]);
    $attempt = makeMultAttempt(['student_id' => $studentUser->id, 'tutor_id' => $studentUser->tutor_id, 'reflection' => null]);

    // Gate is shown, AND the score is visible before reflecting (50% from makeMultAttempt).
    $this->actingAs($studentUser)
        ->get(route('student.games.multiplication.show', $attempt))
        ->assertSee('Round complete!')
        ->assertSee('50%')
        ->assertSee('Your questions');

    $attempt->update(['reflection' => 'Done!']);

    $this->actingAs($studentUser)
        ->get(route('student.games.multiplication.show', $attempt))
        ->assertDontSee('Round complete!');
});

// ---- Tutor feedback ---------------------------------------------------------

it('lets the owning tutor leave feedback', function () {
    $tutorUser   = tutor();
    $studentUser = student(['tutor_id' => $tutorUser->id]);
    $attempt = makeMultAttempt(['student_id' => $studentUser->id, 'tutor_id' => $tutorUser->id]);

    $this->actingAs($tutorUser)
        ->post(route('tutor.games.multiplication.feedback', $attempt), ['feedback' => 'Great improvement!'])
        ->assertRedirect();

    expect($attempt->fresh()->feedback)->toBe('Great improvement!')
        ->and($attempt->fresh()->feedback_by)->toBe($tutorUser->id);
});

// ---- Tenant isolation -------------------------------------------------------

it('shows a tutor only their own students\' multiplication rounds', function () {
    $tutorA = tutor();
    $tutorB = tutor();
    $studentA = student(['tutor_id' => $tutorA->id, 'name' => 'Mine Student']);
    $studentB = student(['tutor_id' => $tutorB->id, 'name' => 'Their Student']);
    makeMultAttempt(['student_id' => $studentA->id, 'tutor_id' => $tutorA->id]);
    makeMultAttempt(['student_id' => $studentB->id, 'tutor_id' => $tutorB->id]);

    $this->actingAs($tutorA)
        ->get(route('tutor.games.multiplication.index'))
        ->assertOk()
        ->assertSee('Mine Student')
        ->assertDontSee('Their Student');
});

it('404s on cross-tenant multiplication access (IDOR)', function () {
    $tutorA   = tutor();
    $tutorB   = tutor();
    $studentA = student(['tutor_id' => $tutorA->id]);
    $studentB = student(['tutor_id' => $tutorB->id]);
    $attemptB = makeMultAttempt(['student_id' => $studentB->id, 'tutor_id' => $tutorB->id]);

    // Another tutor can't view or comment on it.
    $this->actingAs($tutorA)->get(route('tutor.games.multiplication.show', $attemptB))->assertNotFound();
    $this->actingAs($tutorA)->post(route('tutor.games.multiplication.feedback', $attemptB), ['feedback' => 'x'])->assertNotFound();

    // Another student can't view it or write a reflection on it.
    $this->actingAs($studentA)->get(route('student.games.multiplication.show', $attemptB))->assertNotFound();
    $this->actingAs($studentA)->patch(route('student.games.multiplication.reflection', $attemptB), ['reflection' => 'sneaky'])->assertNotFound();
});

/** A persisted attempt with sane defaults, overridable per test. */
function makeMultAttempt(array $attrs = []): MultiplicationAttempt
{
    return MultiplicationAttempt::create(array_merge([
        'level'           => '2 by 2 digits',
        'total_questions' => 2,
        'correct_count'   => 1,
        'score_percent'   => 50,
        'results'         => [
            ['a' => 12, 'b' => 11, 'answer' => 132, 'response' => 132, 'is_correct' => true],
            ['a' => 20, 'b' => 20, 'answer' => 400, 'response' => 399, 'is_correct' => false],
        ],
    ], $attrs));
}
