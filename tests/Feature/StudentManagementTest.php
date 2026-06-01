<?php

/**
 * Authorization + data-isolation tests for the tutor-only Students area.
 * These are part of the "authorization" test scope (see v2.1 §5).
 * The tutor() / student() helpers live in tests/Pest.php.
 */

it('blocks guests from the students area', function () {
    $this->get(route('tutor.students.index'))->assertRedirect(route('login'));
});

it('forbids a student from accessing the tutor students area', function () {
    $this->actingAs(student())
        ->get(route('tutor.students.index'))
        ->assertForbidden(); // 403 from RoleMiddleware
});

it('forbids a student from creating accounts', function () {
    $this->actingAs(student())
        ->post(route('tutor.students.store'), [])
        ->assertForbidden();
});

it('allows a tutor to view the students list', function () {
    $this->actingAs(tutor())
        ->get(route('tutor.students.index'))
        ->assertOk();
});

it('lets a tutor create a student with at least one phone', function () {
    $this->actingAs(tutor())
        ->post(route('tutor.students.store'), [
            'name' => 'Amy Tan',
            'email' => 'amy@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone_1' => '90001111',
        ])
        ->assertRedirect(route('tutor.students.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'amy@example.com',
        'role' => 'student',
    ]);
});

it('rejects creating a student with no phone numbers', function () {
    $this->actingAs(tutor())
        ->post(route('tutor.students.store'), [
            'name' => 'No Phone',
            'email' => 'nophone@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
        ->assertSessionHasErrors('phone_1');

    $this->assertDatabaseMissing('users', ['email' => 'nophone@example.com']);
});

it('keeps the existing password when left blank on update', function () {
    $tutor = tutor();
    $student = student(['email' => 'keep@example.com']);
    $originalHash = $student->password;

    $this->actingAs($tutor)
        ->put(route('tutor.students.update', $student), [
            'name' => 'Updated Name',
            'email' => 'keep@example.com',
            'password' => '',
            'password_confirmation' => '',
            'phone_1' => '90002222',
        ])
        ->assertRedirect(route('tutor.students.index'));

    expect($student->fresh()->password)->toBe($originalHash)
        ->and($student->fresh()->name)->toBe('Updated Name');
});
