<?php

/**
 * Public registration is intentionally disabled for Wowlo — accounts are
 * created by the tutor only. These tests lock that decision in place.
 */

test('public registration page is not available', function () {
    $this->get('/register')->assertNotFound();
});

test('public registration submissions are rejected', function () {
    $this->post('/register', [
        'name' => 'Intruder',
        'email' => 'intruder@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertNotFound();

    $this->assertDatabaseMissing('users', ['email' => 'intruder@example.com']);
});
