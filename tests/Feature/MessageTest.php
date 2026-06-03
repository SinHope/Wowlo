<?php

use App\Models\Message;

/**
 * Messages: authorization, sending, inbox isolation, and read tracking.
 * tutor() / student() helpers live in tests/Pest.php.
 */

function makeMessage(array $attrs = []): Message
{
    return Message::create(array_merge([
        'sender_id' => tutor()->id,
        'receiver_id' => student()->id,
        'subject' => 'Well done this week',
        'body' => 'Great progress on algebra. Keep it up!',
    ], $attrs));
}

it('forbids a student from the tutor messages area', function () {
    $this->actingAs(student())
        ->get(route('tutor.messages.index'))
        ->assertForbidden();
});

it('forbids a tutor from the student inbox', function () {
    $this->actingAs(tutor())
        ->get(route('student.messages.index'))
        ->assertForbidden();
});

it('lets a tutor send a message to a student', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);

    $this->actingAs($tutor)->post(route('tutor.messages.store'), [
        'receiver_id' => $student->id,
        'subject' => 'Homework reminder',
        'body' => 'Please finish the worksheet before Friday.',
    ])->assertRedirect(route('tutor.messages.index'));

    $message = Message::firstWhere('subject', 'Homework reminder');
    expect($message)->not->toBeNull()
        ->and($message->sender_id)->toBe($tutor->id)
        ->and($message->receiver_id)->toBe($student->id)
        ->and($message->is_read)->toBeFalse();
});

it('rejects a message addressed to a non-student', function () {
    $tutor = tutor();
    $anotherTutor = tutor();

    $this->actingAs($tutor)->post(route('tutor.messages.store'), [
        'receiver_id' => $anotherTutor->id, // not a student
        'subject' => 'Hi',
        'body' => 'Test',
    ])->assertSessionHasErrors('receiver_id');
});

it('only shows a student their own messages', function () {
    $mine = makeMessage();
    $theirs = makeMessage(); // addressed to a different freshly-created student

    $this->actingAs($mine->receiver)
        ->get(route('student.messages.show', $mine))
        ->assertOk();

    $this->actingAs($mine->receiver)
        ->get(route('student.messages.show', $theirs))
        ->assertForbidden();
});

it('marks a message as read when the student opens it', function () {
    $message = makeMessage();
    expect($message->is_read)->toBeFalse();

    $this->actingAs($message->receiver)
        ->get(route('student.messages.show', $message))
        ->assertOk();

    expect($message->fresh()->is_read)->toBeTrue();
});

it('does not mark a message read just because the tutor views it', function () {
    $message = makeMessage();

    $this->actingAs($message->sender)
        ->get(route('tutor.messages.show', $message))
        ->assertOk();

    expect($message->fresh()->is_read)->toBeFalse();
});
