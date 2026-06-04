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

it('requires authentication', function () {
    post(route('onboarding.complete'))->assertRedirect(route('login'));
});
