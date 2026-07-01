<?php

use App\Models\HangmanWheel;

/**
 * Hangman Wheel Panda (Slice 18, see docs/hangman-wheel-panda.md). Covers the
 * SERVER-AUTHORITATIVE gameplay (the secret word never reaches the browser
 * until the round ends; the server marks every guess and draws the panda), and
 * the wheel tenancy: custom wheels are tutor-owned, standard wheels are
 * super_admin-only and global. Cross-tenant access 404s.
 */

// Deterministic puzzle + part count so we can assert win/lose precisely. One
// category with one puzzle → "Surprise Me!" (the default) always picks it.
function hangmanConfig(string $word = 'cat', int $parts = 10): void
{
    config([
        'hangman-words.categories' => ['Test' => [$word]],
        'hangman-words.parts'      => array_fill(0, $parts, 'x'),
    ]);
}

// ---- Gameplay: the secret word is protected --------------------------------

it('starts a round without ever leaking the secret word', function () {
    hangmanConfig('elephant');
    $player = student(['tutor_id' => tutor()->id]);

    $state = $this->actingAs($player)->postJson(route('games.hangman.start'))->assertOk()->json();

    expect($state['status'])->toBe('playing')
        ->and($state['word'])->toBeNull()               // word hidden during play
        ->and($state['wrong'])->toBe(0)
        ->and($state['mask'])->toHaveCount(8)           // 'elephant' = 8 letters
        ->and(array_filter($state['mask']))->toBe([]);  // nothing revealed yet
});

// ---- Gameplay: server marks each guess -------------------------------------

it('reveals a correct letter and counts a wrong letter', function () {
    hangmanConfig('cat');
    $player = student(['tutor_id' => tutor()->id]);
    $this->actingAs($player)->postJson(route('games.hangman.start'));

    // correct letter
    $state = $this->actingAs($player)->postJson(route('games.hangman.guess'), ['letter' => 'C'])->json();
    expect($state['mask'][0])->toBe('C')->and($state['wrong'])->toBe(0);

    // wrong letter draws a panda part
    $state = $this->actingAs($player)->postJson(route('games.hangman.guess'), ['letter' => 'Z'])->json();
    expect($state['wrong'])->toBe(1)->and($state['guessed'])->toContain('Z');
});

it('wins when every letter is guessed and reveals the word', function () {
    hangmanConfig('cat');
    $player = student(['tutor_id' => tutor()->id]);
    $this->actingAs($player)->postJson(route('games.hangman.start'));

    $this->actingAs($player)->postJson(route('games.hangman.guess'), ['letter' => 'C']);
    $this->actingAs($player)->postJson(route('games.hangman.guess'), ['letter' => 'A']);
    $state = $this->actingAs($player)->postJson(route('games.hangman.guess'), ['letter' => 'T'])->json();

    expect($state['status'])->toBe('won')->and($state['word'])->toBe('CAT');
});

it('loses after the maximum wrong guesses, drawing the full panda', function () {
    hangmanConfig('cat', parts: 3); // 3 parts → 3 wrong guesses allowed
    $player = student(['tutor_id' => tutor()->id]);
    $this->actingAs($player)->postJson(route('games.hangman.start'));

    $this->actingAs($player)->postJson(route('games.hangman.guess'), ['letter' => 'Z']);
    $this->actingAs($player)->postJson(route('games.hangman.guess'), ['letter' => 'Q']);
    $state = $this->actingAs($player)->postJson(route('games.hangman.guess'), ['letter' => 'X'])->json();

    expect($state['wrong'])->toBe(3)
        ->and($state['status'])->toBe('lost')
        ->and($state['word'])->toBe('CAT'); // revealed only now the round is over
});

it('solves with a correct whole-word guess, and a wrong word draws a part', function () {
    hangmanConfig('cat');
    $player = student(['tutor_id' => tutor()->id]);

    // wrong whole-word guess
    $this->actingAs($player)->postJson(route('games.hangman.start'));
    $state = $this->actingAs($player)->postJson(route('games.hangman.guess'), ['word' => 'dog'])->json();
    expect($state['wrong'])->toBe(1)->and($state['status'])->toBe('playing');

    // correct whole-word guess
    $state = $this->actingAs($player)->postJson(route('games.hangman.guess'), ['word' => 'cat'])->json();
    expect($state['status'])->toBe('won')->and($state['word'])->toBe('CAT');
});

// ---- Phrases (spaces + punctuation) ----------------------------------------

it('shows spaces for free in a phrase and only masks the letters', function () {
    hangmanConfig('ice cream');
    $player = student(['tutor_id' => tutor()->id]);

    $state = $this->actingAs($player)->postJson(route('games.hangman.start'))->json();

    // 9 chars: the space (index 3) is shown; the 8 letters are hidden (null).
    expect($state['mask'])->toHaveCount(9)
        ->and($state['mask'][3])->toBe(' ')
        ->and($state['mask'][0])->toBeNull()
        ->and(array_filter($state['mask'], fn ($c) => $c !== null))->toBe([3 => ' ']);
});

it('wins a phrase by guessing only its letters (spaces are free)', function () {
    hangmanConfig('ice cream');
    $player = student(['tutor_id' => tutor()->id]);
    $this->actingAs($player)->postJson(route('games.hangman.start'));

    $last = null;
    foreach (['I', 'C', 'E', 'R', 'A', 'M'] as $letter) {
        $last = $this->actingAs($player)->postJson(route('games.hangman.guess'), ['letter' => $letter])->json();
    }

    expect($last['status'])->toBe('won')
        ->and($last['word'])->toBe('ICE CREAM')
        ->and($last['wrong'])->toBe(0);
});

it('solves a phrase whether or not the spaces are typed', function () {
    hangmanConfig('ice cream');
    $player = student(['tutor_id' => tutor()->id]);

    // with spaces
    $this->actingAs($player)->postJson(route('games.hangman.start'));
    expect($this->actingAs($player)->postJson(route('games.hangman.guess'), ['word' => 'ice cream'])->json()['status'])->toBe('won');

    // without spaces
    $this->actingAs($player)->postJson(route('games.hangman.start'));
    expect($this->actingAs($player)->postJson(route('games.hangman.guess'), ['word' => 'icecream'])->json()['status'])->toBe('won');
});

// ---- Categories -------------------------------------------------------------

it('draws the puzzle from the chosen category and reports it', function () {
    config([
        'hangman-words.categories' => [
            'Animals' => ['ELEPHANT'],   // 8 letters
            'Food'    => ['PIZZA'],      // 5 letters
        ],
        'hangman-words.parts' => array_fill(0, 10, 'x'),
    ]);
    $player = student(['tutor_id' => tutor()->id]);

    $state = $this->actingAs($player)->postJson(route('games.hangman.start'), ['category' => 'Food'])->json();

    expect($state['category'])->toBe('Food')
        ->and($state['mask'])->toHaveCount(5); // must be PIZZA, not ELEPHANT
});

it('rejects a tampered/unknown category', function () {
    hangmanConfig('cat');
    $player = student(['tutor_id' => tutor()->id]);

    $this->actingAs($player)
        ->postJson(route('games.hangman.start'), ['category' => 'Secret Cheats'])
        ->assertStatus(422);
});

it('rejects a guess when no round is in progress', function () {
    $player = student(['tutor_id' => tutor()->id]);

    $this->actingAs($player)
        ->postJson(route('games.hangman.guess'), ['letter' => 'A'])
        ->assertStatus(422);
});

// ---- Wheel "smart effects" (server-authoritative) --------------------------

it('reveals a hidden letter via the reveal effect without drawing a part', function () {
    hangmanConfig('cat');
    $player = student(['tutor_id' => tutor()->id]);
    $this->actingAs($player)->postJson(route('games.hangman.start'));

    $state = $this->actingAs($player)->postJson(route('games.hangman.effect'), ['type' => 'reveal'])->json();

    expect($state['guessed'])->toHaveCount(1)
        ->and($state['wrong'])->toBe(0)
        ->and(array_filter($state['mask']))->toHaveCount(1); // one position now shown
});

it('draws a panda part via the lose_guess effect, and can end the round', function () {
    hangmanConfig('cat', parts: 2);
    $player = student(['tutor_id' => tutor()->id]);
    $this->actingAs($player)->postJson(route('games.hangman.start'));

    $this->actingAs($player)->postJson(route('games.hangman.effect'), ['type' => 'lose_guess']);
    $state = $this->actingAs($player)->postJson(route('games.hangman.effect'), ['type' => 'lose_guess'])->json();

    expect($state['wrong'])->toBe(2)->and($state['status'])->toBe('lost');
});

it('wins when the reveal effect uncovers the last letter', function () {
    hangmanConfig('ee'); // single distinct letter
    $player = student(['tutor_id' => tutor()->id]);
    $this->actingAs($player)->postJson(route('games.hangman.start'));

    $state = $this->actingAs($player)->postJson(route('games.hangman.effect'), ['type' => 'reveal'])->json();

    expect($state['status'])->toBe('won')->and($state['word'])->toBe('EE');
});

it('rejects an unknown effect type', function () {
    hangmanConfig('cat');
    $player = student(['tutor_id' => tutor()->id]);
    $this->actingAs($player)->postJson(route('games.hangman.start'));

    $this->actingAs($player)
        ->postJson(route('games.hangman.effect'), ['type' => 'win_instantly'])
        ->assertStatus(422);
});

// ---- Play screen: wheels available to the player ---------------------------

it('shows standard + own-tenant wheels on the play screen, not another tenant\'s', function () {
    $tutorA = tutor();
    $tutorB = tutor();
    $studentA = student(['tutor_id' => $tutorA->id]);

    HangmanWheel::create(['name' => 'House Wheel', 'type' => 'standard', 'created_by' => superAdmin()->id, 'tutor_id' => null, 'slices' => ['a', 'b']]);
    HangmanWheel::create(['name' => 'Tutor A Wheel', 'type' => 'custom', 'created_by' => $tutorA->id, 'tutor_id' => $tutorA->id, 'slices' => ['a', 'b']]);
    HangmanWheel::create(['name' => 'Tutor B Wheel', 'type' => 'custom', 'created_by' => $tutorB->id, 'tutor_id' => $tutorB->id, 'slices' => ['a', 'b']]);

    $this->actingAs($studentA)->get(route('games.hangman.play'))
        ->assertOk()
        ->assertSee('House Wheel')
        ->assertSee('Tutor A Wheel')
        ->assertDontSee('Tutor B Wheel');
});

// ---- Wheel authoring: tenancy + standard-is-admin-only ----------------------

it('stamps the creating tutor as owner of a custom wheel', function () {
    $tutorA = tutor();

    $this->actingAs($tutorA)->post(route('tutor.games.hangman.wheels.store'), [
        'name'   => 'My Wheel',
        'slices' => ['+1 Guess', 'Spin Again'],
    ])->assertRedirect();

    $wheel = HangmanWheel::firstWhere('name', 'My Wheel');
    expect($wheel->type)->toBe('custom')
        ->and($wheel->tutor_id)->toBe($tutorA->id)
        ->and($wheel->slices)->toBe(['+1 Guess', 'Spin Again']);
});

it('ignores is_standard from a non-admin tutor (no global wheels for tutors)', function () {
    $tutorA = tutor();

    $this->actingAs($tutorA)->post(route('tutor.games.hangman.wheels.store'), [
        'name'        => 'Sneaky Global',
        'slices'      => ['a', 'b'],
        'is_standard' => 1, // attempted — must be ignored
    ])->assertRedirect();

    $wheel = HangmanWheel::firstWhere('name', 'Sneaky Global');
    expect($wheel->type)->toBe('custom')->and($wheel->tutor_id)->toBe($tutorA->id);
});

it('lets the super_admin create a global standard wheel', function () {
    $admin = superAdmin();

    $this->actingAs($admin)->post(route('tutor.games.hangman.wheels.store'), [
        'name'        => 'Global Wheel',
        'slices'      => ['a', 'b'],
        'is_standard' => 1,
    ])->assertRedirect();

    $wheel = HangmanWheel::firstWhere('name', 'Global Wheel');
    expect($wheel->type)->toBe('standard')->and($wheel->tutor_id)->toBeNull();
});

it('drops blank slices and rejects a wheel with fewer than two real slices', function () {
    $tutorA = tutor();

    $this->actingAs($tutorA)->post(route('tutor.games.hangman.wheels.store'), [
        'name'   => 'Too Few',
        'slices' => ['Only one', '  ', ''],
    ])->assertStatus(422);

    expect(HangmanWheel::where('name', 'Too Few')->exists())->toBeFalse();
});

// ---- Wheel tenancy: cross-tenant 404 ---------------------------------------

it('404s when a tutor edits or deletes another tutor\'s wheel', function () {
    $tutorA = tutor();
    $tutorB = tutor();
    $wheelB = HangmanWheel::create(['name' => 'B', 'type' => 'custom', 'created_by' => $tutorB->id, 'tutor_id' => $tutorB->id, 'slices' => ['a', 'b']]);

    $this->actingAs($tutorA)->get(route('tutor.games.hangman.wheels.edit', $wheelB))->assertNotFound();
    $this->actingAs($tutorA)->put(route('tutor.games.hangman.wheels.update', $wheelB), ['name' => 'x', 'slices' => ['a', 'b']])->assertNotFound();
    $this->actingAs($tutorA)->delete(route('tutor.games.hangman.wheels.destroy', $wheelB))->assertNotFound();

    expect(HangmanWheel::find($wheelB->id))->not->toBeNull();
});

it('404s when a non-admin tutor edits a standard wheel', function () {
    $tutorA = tutor();
    $standard = HangmanWheel::create(['name' => 'Std', 'type' => 'standard', 'created_by' => superAdmin()->id, 'tutor_id' => null, 'slices' => ['a', 'b']]);

    $this->actingAs($tutorA)->get(route('tutor.games.hangman.wheels.edit', $standard))->assertNotFound();
    $this->actingAs($tutorA)->delete(route('tutor.games.hangman.wheels.destroy', $standard))->assertNotFound();
});

it('renders the wheel index and builder for a tutor', function () {
    $tutorA = tutor();
    HangmanWheel::create(['name' => 'Render Me', 'type' => 'custom', 'created_by' => $tutorA->id, 'tutor_id' => $tutorA->id, 'slices' => ['a', 'b']]);

    $this->actingAs($tutorA)->get(route('tutor.games.hangman.wheels.index'))->assertOk()->assertSee('Render Me');
    $this->actingAs($tutorA)->get(route('tutor.games.hangman.wheels.create'))->assertOk()->assertSee('Wheel slices');
});

it('renders the edit page for the wheel\'s owner', function () {
    $tutorA = tutor();
    $wheel = HangmanWheel::create(['name' => 'Editable', 'type' => 'custom', 'created_by' => $tutorA->id, 'tutor_id' => $tutorA->id, 'slices' => ['a', 'b']]);

    $this->actingAs($tutorA)->get(route('tutor.games.hangman.wheels.edit', $wheel))->assertOk()->assertSee('Editable');
});

it('blocks students from the wheel-management area entirely', function () {
    $player = student(['tutor_id' => tutor()->id]);

    $this->actingAs($player)->get(route('tutor.games.hangman.wheels.index'))->assertForbidden();
    $this->actingAs($player)->get(route('tutor.games.hangman.wheels.create'))->assertForbidden();
});
