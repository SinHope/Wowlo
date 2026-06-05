<?php

use function Pest\Laravel\post;

it('stores emails lowercased on create', function () {
    $user = tutor(['email' => 'MixedCase@Example.COM']);

    expect($user->fresh()->email)->toBe('mixedcase@example.com');
});

it('logs in regardless of email case', function () {
    // Stored lowercase (factory default password is "password").
    tutor(['email' => 'keean.leegt@gmail.com']);

    post('/login', [
        'email' => 'Keean.Leegt@Gmail.com',   // different case
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
});

it('rejects a duplicate email that differs only by case', function () {
    $tutor = superAdmin();
    student(['email' => 'existing@example.com', 'tutor_id' => $tutor->id]);

    // Super-admin tries to create a tutor with the same email in a different case.
    $this->actingAs($tutor)->post(route('admin.tutors.store'), [
        'name' => 'Dupe',
        'email' => 'EXISTING@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone_1' => '90000000',
    ])->assertSessionHasErrors('email');
});
