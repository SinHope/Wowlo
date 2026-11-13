<?php

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

it('treats a brand-new user as needing onboarding', function () {
    expect(tutor()->needsOnboarding())->toBeTrue();
});

it('marks onboarding complete and stops needing it', function () {
    $user = tutor();
    expect($user->onboarded_at)->toBeNull();

    actingAs($user)->post(route('onboarding.complete'))->assertNoContent();

    expect($user->fresh()->needsOnboarding())->toBeFalse();
});

it('is idempotent — a second call keeps the original timestamp', function () {
    $user = tutor();

    actingAs($user)->post(route('onboarding.complete'))->assertNoContent();
    $first = $user->fresh()->onboarded_at;

    actingAs($user)->post(route('onboarding.complete'))->assertNoContent();
    expect($user->fresh()->onboarded_at->equalTo($first))->toBeTrue();
});

it('re-shows the tour for everyone via the Attendance-step reset migration, touching nothing else', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $tutor->forceFill(['onboarded_at' => now()])->save();
    $student->forceFill(['onboarded_at' => now()])->save();
    $nameBefore = $student->fresh()->name;

    (require database_path('migrations/2026_11_14_000010_reset_onboarding_for_attendance_step.php'))->up();

    expect($tutor->fresh()->needsOnboarding())->toBeTrue()
        ->and($student->fresh()->needsOnboarding())->toBeTrue()
        ->and($student->fresh()->name)->toBe($nameBefore)
        ->and($student->fresh()->tutor_id)->toBe($tutor->id);
});

it('requires authentication', function () {
    post(route('onboarding.complete'))->assertRedirect(route('login'));
});

it('includes an Attendance card in the tutor, super_admin and student tours', function () {
    $tutorTour = 'send those days straight into a WhatsApp bill';
    $studentTour = 'marked as attended, on a calendar or in a list';

    actingAs(tutor())->get(route('dashboard'))->assertOk()->assertSee($tutorTour)->assertDontSee($studentTour);
    actingAs(superAdmin())->get(route('dashboard'))->assertOk()->assertSee($tutorTour);
    actingAs(student(['tutor_id' => tutor()->id]))->get(route('dashboard'))->assertOk()->assertSee($studentTour)->assertDontSee($tutorTour);
});

it('lists the Attendance step for both audiences on the public how-to-use page', function () {
    $this->get(route('how-to-use'))
        ->assertOk()
        ->assertSee('send those days straight into a WhatsApp bill')
        ->assertSee('marked as attended, on a calendar or in a list');
});
