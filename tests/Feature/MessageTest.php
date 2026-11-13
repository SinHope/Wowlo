<?php

use App\Models\Message;
use App\Notifications\NewMessageNotification;
use Illuminate\Support\Facades\Notification;

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

// ---- Student → tutor messages ----------------------------------------------

/** POST one "Message my tutor" form as $student. */
function sendToTutor($test, $student, array $attrs = [])
{
    return $test->actingAs($student)->post(route('student.messages.store'), array_merge([
        'subject' => 'Question about Q5',
        'body' => 'I tried the model method but got stuck on the second step.',
    ], $attrs));
}

it('lets a student message their own tutor, ignoring any injected receiver_id', function () {
    Notification::fake();
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $otherTutor = tutor();

    sendToTutor($this, $student, ['receiver_id' => $otherTutor->id])
        ->assertRedirect(route('student.messages.index', ['box' => 'sent']));

    $message = Message::firstWhere('subject', 'Question about Q5');
    expect($message->sender_id)->toBe($student->id)
        ->and($message->receiver_id)->toBe($tutor->id)
        ->and($message->is_read)->toBeFalse();

    Notification::assertSentTo($tutor, NewMessageNotification::class);
    Notification::assertNotSentTo($otherTutor, NewMessageNotification::class);
});

it('shows the student how many messages they have left this hour', function () {
    $student = student(['tutor_id' => tutor()->id]);

    $this->actingAs($student)->get(route('student.messages.create'))
        ->assertOk()
        ->assertSee('5 of 5 messages left this hour');

    sendToTutor($this, $student);
    sendToTutor($this, $student);

    $this->actingAs($student)->get(route('student.messages.create'))
        ->assertSee('3 of 5 messages left this hour');
    $this->actingAs($student)->get(route('student.messages.index'))
        ->assertSee('3 of 5 messages left this hour');
});

it('blocks the sixth message in an hour, then allows sending again after the hour', function () {
    $student = student(['tutor_id' => tutor()->id]);

    foreach (range(1, 5) as $i) {
        sendToTutor($this, $student, ['subject' => "Msg {$i}"])->assertSessionHasNoErrors();
    }

    sendToTutor($this, $student, ['subject' => 'Msg 6'])->assertSessionHasErrors('limit');
    expect(Message::where('sender_id', $student->id)->count())->toBe(5);

    $this->actingAs($student)->get(route('student.messages.create'))
        ->assertSee('0 of 5 messages left this hour')
        ->assertSee('You can send again at');

    $this->travel(61)->minutes();

    sendToTutor($this, $student, ['subject' => 'Msg 6'])->assertSessionHasNoErrors();
    expect(Message::where('sender_id', $student->id)->count())->toBe(6);
});

it('does not use up a message when the form fails validation', function () {
    $student = student(['tutor_id' => tutor()->id]);

    sendToTutor($this, $student, ['body' => ''])->assertSessionHasErrors('body');

    $this->actingAs($student)->get(route('student.messages.create'))
        ->assertSee('5 of 5 messages left this hour');
});

it('does not count automatic notices or other students toward the limit', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $classmate = student(['tutor_id' => $tutor->id]);

    // e.g. "Answer sheet submitted" notices are also sent as the student.
    foreach (range(1, 5) as $i) {
        Message::create(['sender_id' => $student->id, 'receiver_id' => $tutor->id, 'subject' => "Notice {$i}", 'body' => 'Auto']);
    }
    foreach (range(1, 5) as $i) {
        sendToTutor($this, $classmate, ['subject' => "Classmate {$i}"]);
    }

    sendToTutor($this, $student)->assertSessionHasNoErrors();
});

it('404s the compose form for a student with no tutor', function () {
    $student = student();

    $this->actingAs($student)->get(route('student.messages.create'))->assertNotFound();
    sendToTutor($this, $student)->assertNotFound();
});

it('forbids a tutor from the student compose routes', function () {
    $this->actingAs(tutor())->get(route('student.messages.create'))->assertForbidden();
});

it('lets a student read their own sent message without marking it read', function () {
    $student = student(['tutor_id' => tutor()->id]);
    sendToTutor($this, $student);
    $message = Message::firstWhere('sender_id', $student->id);

    $this->actingAs($student)->get(route('student.messages.show', $message))
        ->assertOk()
        ->assertSee('Not read yet');
    $this->actingAs($student)->get(route('student.messages.index', ['box' => 'sent']))
        ->assertSee('Question about Q5');

    expect($message->fresh()->is_read)->toBeFalse();
});

it('forbids a student from reading another student\'s sent message', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $classmate = student(['tutor_id' => $tutor->id]);
    sendToTutor($this, $classmate);
    $message = Message::firstWhere('sender_id', $classmate->id);

    $this->actingAs($student)->get(route('student.messages.show', $message))->assertForbidden();
});

it('prefills a student reply only from a message they received', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $mine = makeMessage(['sender_id' => $tutor->id, 'receiver_id' => $student->id, 'subject' => 'Fractions recap']);
    $theirs = makeMessage(['subject' => 'Private note for someone else']);

    $this->actingAs($student)->get(route('student.messages.show', $mine))
        ->assertSee(route('student.messages.create', ['reply_to' => $mine->id]), false);

    $this->actingAs($student)->get(route('student.messages.create', ['reply_to' => $mine->id]))
        ->assertSee('Re: Fractions recap');

    $this->actingAs($student)->get(route('student.messages.create', ['reply_to' => $theirs->id]))
        ->assertOk()
        ->assertDontSee('Private note for someone else');
});

it('lets a tutor see a student message in their inbox and reply to it', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    sendToTutor($this, $student);
    $message = Message::firstWhere('sender_id', $student->id);

    $this->actingAs($tutor)->get(route('tutor.messages.inbox'))->assertSee('Question about Q5');

    $this->actingAs($tutor)->get(route('tutor.messages.show', $message))
        ->assertOk()
        ->assertSee(route('tutor.messages.create', ['reply_to' => $message->id]), false);
    expect($message->fresh()->is_read)->toBeTrue();

    $this->actingAs($tutor)->get(route('tutor.messages.create', ['reply_to' => $message->id]))
        ->assertSee('Re: Question about Q5')
        ->assertSee('value="' . $student->id . '" selected', false);
});

it('does not stack "Re:" when replying to a reply', function () {
    $message = makeMessage(['subject' => 'Re: Question about Q5']);

    expect($message->replySubject())->toBe('Re: Question about Q5');
});
