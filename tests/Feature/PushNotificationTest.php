<?php

use App\Models\Homework;
use App\Models\User;
use App\Notifications\NewHomeworkNotification;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Slice 9: PWA web-push — subscription endpoints + best-effort notifications.
 */

function subscriptionPayload(): array
{
    return [
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
        'keys' => ['p256dh' => 'BPa1...key', 'auth' => 'authsecret'],
        'contentEncoding' => 'aesgcm',
    ];
}

it('lets an authenticated user register a push subscription', function () {
    $student = student();

    $this->actingAs($student)
        ->postJson(route('push.subscribe'), subscriptionPayload())
        ->assertOk()
        ->assertJson(['ok' => true]);

    $this->assertDatabaseHas('push_subscriptions', [
        'subscribable_id' => $student->id,
        'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
    ]);
});

it('requires authentication to subscribe', function () {
    $this->post(route('push.subscribe'), subscriptionPayload())
        ->assertRedirect(route('login'));

    expect(\DB::table('push_subscriptions')->count())->toBe(0);
});

it('validates the subscription payload', function () {
    $this->actingAs(student())
        ->post(route('push.subscribe'), ['endpoint' => ''])
        ->assertSessionHasErrors(['endpoint', 'keys.p256dh', 'keys.auth']);
});

it('lets a user remove their push subscription', function () {
    $student = student();
    $this->actingAs($student)->postJson(route('push.subscribe'), subscriptionPayload());

    $this->actingAs($student)
        ->deleteJson(route('push.unsubscribe'), ['endpoint' => subscriptionPayload()['endpoint']])
        ->assertOk();

    $this->assertDatabaseMissing('push_subscriptions', [
        'endpoint' => subscriptionPayload()['endpoint'],
    ]);
});

it('notifies the assigned student when homework is created', function () {
    Notification::fake();
    Storage::fake('r2');

    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);

    $this->actingAs($tutor)->post(route('tutor.homework.store'), [
        'title' => 'Read chapter 4',
        'subject' => 'Science',
        'description' => 'Pages 40-50.',
        'student_id' => $student->id,
        'start_date' => now()->toDateString(),
        'due_date' => now()->addDays(3)->toDateString(),
    ]);

    Notification::assertSentTo($student, NewHomeworkNotification::class);
});

it('notifies the receiver when a message is sent', function () {
    Notification::fake();

    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);

    $this->actingAs($tutor)->post(route('tutor.messages.store'), [
        'receiver_id' => $student->id,
        'subject' => 'Well done',
        'body' => 'Great work this week!',
    ]);

    Notification::assertSentTo($student, NewMessageNotification::class);
});

it('does not notify anyone other than the assigned student', function () {
    Notification::fake();
    Storage::fake('r2');

    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $bystander = student();

    $this->actingAs($tutor)->post(route('tutor.homework.store'), [
        'title' => 'X', 'subject' => 'Science', 'description' => 'Y',
        'student_id' => $student->id,
        'start_date' => now()->toDateString(), 'due_date' => now()->addDay()->toDateString(),
    ]);

    Notification::assertNotSentTo($bystander, NewHomeworkNotification::class);
});

it('still creates the homework even if the push notification throws', function () {
    Storage::fake('r2');

    // Force the notification path to blow up; the request must still succeed.
    Notification::shouldReceive('send')->andThrow(new \RuntimeException('push down'));

    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);

    $this->actingAs($tutor)->post(route('tutor.homework.store'), [
        'title' => 'Resilient HW', 'subject' => 'Science', 'description' => 'Y',
        'student_id' => $student->id,
        'start_date' => now()->toDateString(), 'due_date' => now()->addDay()->toDateString(),
    ])->assertRedirect(route('tutor.homework.index'));

    $this->assertDatabaseHas('homeworks', ['title' => 'Resilient HW']);
});
