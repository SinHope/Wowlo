<?php

use App\Models\Attendance;
use App\Models\Bill;
use App\Models\BillLine;
use App\Models\TuitionFee;

/**
 * Attendance Tracking (docs/attendance-tracking.md): tutor picks a student,
 * ticks attended days, Saves or pushes them into a pre-filled bill; students get
 * a read-only view of their own days. Cross-tenant cases also live in
 * MultiTutorTest; bill↔attendance linking also in BillingTest.
 */

// ---- Picker -----------------------------------------------------------------

it('lists only the tutor\'s own students in the picker', function () {
    $tutorA = tutor();
    student(['tutor_id' => $tutorA->id, 'name' => 'Alice Mine']);
    student(['tutor_id' => tutor()->id, 'name' => 'Bob Theirs']);

    $this->actingAs($tutorA)
        ->get(route('tutor.attendance.index'))
        ->assertOk()
        ->assertSee('Alice Mine')
        ->assertDontSee('Bob Theirs');
});

it('shows the attendance sidebar tab to tutors and students', function () {
    $tutor = tutor();

    $this->actingAs($tutor)->get(route('dashboard'))->assertSee('Attendance');
    $this->actingAs(student(['tutor_id' => $tutor->id]))->get(route('dashboard'))->assertSee('Attendance');
});

// ---- Viewing a student ------------------------------------------------------

it('opens a student\'s page with only that student\'s days and their derived states', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);
    $other = student(['tutor_id' => $tutor->id]);
    attDay($alice, attDate(5));
    attDay($other, attDate(6));

    $rows = attRows($this->actingAs($tutor)->get(route('tutor.attendance.show', $alice))->assertOk());

    expect($rows)->toBe([attDate(5) => 'attended']);
});

it('falls back to safe defaults for a junk view state in the query string', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);

    $config = $this->actingAs($tutor)
        ->get(route('tutor.attendance.show', [$alice, 'view' => 'evil', 'month' => 'nope', 'from' => '2026-13-45', 'to' => 'x']))
        ->assertOk()
        ->viewData('config');

    expect($config['view'])->toBe('calendar')
        ->and($config['month'])->toBe(now()->format('Y-m'))
        ->and($config['from'])->toBeNull()
        ->and($config['to'])->toBeNull();
});

it('404s a non-numeric student id', function () {
    $this->actingAs(tutor())->get('/tutor/attendance/abc')->assertNotFound();
});

// ---- Save -------------------------------------------------------------------

it('saves ticked days stamped with the server-side tutor and student, and is idempotent', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);

    $payload = ['added' => [attDate(3), attDate(2), attDate(3)]];   // duplicate in payload

    $this->actingAs($tutor)->put(route('tutor.attendance.update', $alice), $payload)->assertSessionHasErrors('added.2');
    expect(Attendance::count())->toBe(0);   // `distinct` rule rejects it outright

    $this->actingAs($tutor)->put(route('tutor.attendance.update', $alice), ['added' => [attDate(3), attDate(2)]])
        ->assertRedirect()
        ->assertSessionHas('status', 'Attendance saved.');
    // Saving the same ticks again must not duplicate or error.
    $this->actingAs($tutor)->put(route('tutor.attendance.update', $alice), ['added' => [attDate(3)]])->assertRedirect();

    $rows = Attendance::where('student_id', $alice->id)->get();
    expect($rows)->toHaveCount(2)
        ->and($rows->pluck('tutor_id')->unique()->all())->toBe([$tutor->id]);
});

it('removes unticked days that are not billed', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);
    attDay($alice, attDate(4));
    attDay($alice, attDate(3));

    $this->actingAs($tutor)->put(route('tutor.attendance.update', $alice), ['removed' => [attDate(4)]])->assertRedirect();

    expect(Attendance::where('student_id', $alice->id)->pluck('lesson_date')->map->format('Y-m-d')->all())
        ->toBe([attDate(3)]);
});

it('accepts today and a future date on save, but not more than a year ahead or malformed dates', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);
    $put = fn (array $data) => $this->actingAs($tutor)->put(route('tutor.attendance.update', $alice), $data);

    $put(['added' => [now()->toDateString(), now()->addDay()->toDateString()]])->assertSessionDoesntHaveErrors();
    expect(Attendance::count())->toBe(2);

    $put(['added' => [now()->addYear()->addDays(2)->toDateString()]])->assertSessionHasErrors('added.0');
    $put(['added' => ['2026-2-3']])->assertSessionHasErrors('added.0');
    $put(['added' => ['not-a-date']])->assertSessionHasErrors('added.0');
    $put(['added' => ['1999-12-31']])->assertSessionHasErrors('added.0');
    expect(Attendance::count())->toBe(2);
});

it('cannot remove a billed day, and warns instead', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);
    $bill = attBill($alice, 100);
    attDay($alice, attDate(4), $bill->id);

    $this->actingAs($tutor)
        ->put(route('tutor.attendance.update', $alice), ['removed' => [attDate(4)]])
        ->assertSessionHas('warning');

    expect(Attendance::where('student_id', $alice->id)->count())->toBe(1);
});

it('cannot re-add over a billed day (the row stays linked to its bill)', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);
    $bill = attBill($alice, 100);
    attDay($alice, attDate(4), $bill->id);

    $this->actingAs($tutor)->put(route('tutor.attendance.update', $alice), ['added' => [attDate(4)]])->assertRedirect();

    $rows = Attendance::where('student_id', $alice->id)->get();
    expect($rows)->toHaveCount(1)->and($rows->first()->bill_id)->toBe($bill->id);
});

it('ignores a client-supplied tutor_id or student_id', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);
    $victim = student(['tutor_id' => tutor()->id]);

    $this->actingAs($tutor)->put(route('tutor.attendance.update', $alice), [
        'added' => [attDate(2)],
        'tutor_id' => 999,
        'student_id' => $victim->id,
    ])->assertRedirect();

    $row = Attendance::sole();
    expect($row->student_id)->toBe($alice->id)->and($row->tutor_id)->toBe($tutor->id);
    expect(Attendance::where('student_id', $victim->id)->count())->toBe(0);
});

it('returns to the same view state after saving', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);

    $this->actingAs($tutor)->put(route('tutor.attendance.update', $alice), [
        'added' => [attDate(2)], 'view' => 'list', 'month' => '2026-05', 'from' => '2026-05-01', 'to' => '2026-05-10',
    ])->assertRedirect(route('tutor.attendance.show', [
        'student' => $alice->id, 'view' => 'list', 'month' => '2026-05', 'from' => '2026-05-01', 'to' => '2026-05-10',
    ]));
});

// ---- Add to Billing ---------------------------------------------------------

it('saves, then redirects to the bill form with only the unbilled in-window days, sorted', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);
    $bill = attBill($alice, 100);
    attDay($alice, attDate(9), $bill->id);   // already billed → excluded
    attDay($alice, attDate(5));              // saved, unbilled → included

    $this->actingAs($tutor)->post(route('tutor.attendance.billing', $alice), attBillingPayload([
        'added' => [attDate(2), attDate(7)],
        'window_from' => attDate(30),
        'window_to' => attDate(0),
    ]))
        ->assertRedirect(route('tutor.billing.create'))
        ->assertSessionHas('attendance_prefill', fn (array $p) => $p['student_id'] === $alice->id
            && $p['dates'] === [attDate(7), attDate(5), attDate(2)]);

    expect(Attendance::where('student_id', $alice->id)->count())->toBe(4);   // the ticks were saved too
});

it('refuses to bill a later date: nothing is saved and there is no prefill', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);
    attDay($alice, attDate(3));
    $tomorrow = now()->addDay()->toDateString();

    $this->actingAs($tutor)->post(route('tutor.attendance.billing', $alice), attBillingPayload([
        'added' => [$tomorrow],
        'window_to' => now()->addDays(5)->toDateString(),
    ]))
        ->assertSessionHasErrors('billing')
        ->assertSessionMissing('attendance_prefill');

    expect(Attendance::where('student_id', $alice->id)->count())->toBe(1);   // tomorrow was NOT saved
});

it('refuses to bill when nothing unbilled is ticked in the window', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);
    $bill = attBill($alice, 100);
    attDay($alice, attDate(3), $bill->id);

    $this->actingAs($tutor)->post(route('tutor.attendance.billing', $alice), attBillingPayload())
        ->assertSessionHasErrors('billing')
        ->assertSessionMissing('attendance_prefill');
});

it('requires a sane billing window', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);
    $post = fn (array $data) => $this->actingAs($tutor)->post(route('tutor.attendance.billing', $alice), $data);

    $post([])->assertSessionHasErrors(['window_from', 'window_to']);
    $post(attBillingPayload(['window_from' => attDate(0), 'window_to' => attDate(5)]))->assertSessionHasErrors('window_to');
    $post(attBillingPayload(['window_from' => attDate(500), 'window_to' => attDate(0)]))->assertSessionHasErrors('window_to');
});

it('pre-fills the bill form with the dates and the student\'s last billed hours', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);
    TuitionFee::create(['student_id' => $alice->id, 'fee_rate_per_hour' => 50, 'currency' => 'SGD']);
    $old = attBill($alice, 75);
    BillLine::create(['bill_id' => $old->id, 'lesson_date' => '2026-01-05', 'hours' => 1.5, 'rate' => 50, 'amount' => 75]);

    $prefill = $this->actingAs($tutor)
        ->withSession(['attendance_prefill' => ['student_id' => $alice->id, 'dates' => [attDate(3), attDate(1)], 'billing_month' => now()->format('Y-m')]])
        ->get(route('tutor.billing.create'))
        ->assertOk()
        ->assertSee('Pre-filled from Attendance: 2 lessons')
        ->viewData('prefill');

    expect($prefill['studentId'])->toBe($alice->id)
        ->and(collect($prefill['lessons'])->pluck('lesson_date')->all())->toBe([attDate(3), attDate(1)])
        ->and(collect($prefill['lessons'])->pluck('hours')->unique()->all())->toBe([1.5]);
});

it('leaves hours blank when the student has no earlier bill', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);

    $prefill = $this->actingAs($tutor)
        ->withSession(['attendance_prefill' => ['student_id' => $alice->id, 'dates' => [attDate(1)], 'billing_month' => now()->format('Y-m')]])
        ->get(route('tutor.billing.create'))
        ->viewData('prefill');

    expect($prefill['lessons'][0]['hours'])->toBe('');
});

it('shows a plain bill form when there is no prefill', function () {
    $this->actingAs(tutor())->get(route('tutor.billing.create'))
        ->assertOk()
        ->assertDontSee('Pre-filled from Attendance')
        ->assertViewHas('prefill', null);
});

// ---- Derived day states -----------------------------------------------------

it('walks a day through attended → billed → paid → attended again', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);
    TuitionFee::create(['student_id' => $alice->id, 'fee_rate_per_hour' => 50, 'currency' => 'SGD']);
    $date = attDate(3);
    $state = fn () => attRows($this->actingAs($tutor)->get(route('tutor.attendance.show', $alice)))[$date];

    attDay($alice, $date);
    expect($state())->toBe('attended');

    $this->actingAs($tutor)->post(route('tutor.billing.store'), [
        'student_id' => $alice->id,
        'billing_month' => now()->format('Y-m'),
        'lessons' => [['lesson_date' => $date, 'hours' => 2]],   // 2h × $50 = $100
    ])->assertRedirect();
    $bill = Bill::sole();
    expect($state())->toBe('billed');

    attPay($alice, 100);
    expect($state())->toBe('paid');

    $this->actingAs($tutor)->delete(route('tutor.billing.destroy', $bill))->assertRedirect();
    expect($state())->toBe('attended');
});

// ---- Student view -----------------------------------------------------------

it('shows a student only their own days, and never their billed or paid state', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);
    $bob = student(['tutor_id' => $tutor->id]);
    $paidBill = attBill($alice, 100);
    attPay($alice, 100);
    attDay($alice, attDate(5), $paidBill->id);   // paid
    attDay($alice, attDate(4));                  // attended
    attDay($bob, attDate(6));                    // someone else's

    $response = $this->actingAs($alice)->get(route('student.attendance.index'))->assertOk();
    $config = $response->viewData('config');

    expect($config['readonly'])->toBeTrue()
        ->and(collect($config['rows'])->pluck('date')->sort()->values()->all())->toBe([attDate(5), attDate(4)])
        ->and(collect($config['rows'])->pluck('status')->unique()->all())->toBe(['attended']);
    $response->assertDontSee('Add To Billing')->assertDontSee('Unsaved changes');
});

// ---- Roles ------------------------------------------------------------------

it('blocks students from the tutor attendance routes and tutors from the student route', function () {
    $tutor = tutor();
    $alice = student(['tutor_id' => $tutor->id]);

    $this->actingAs($alice)->get(route('tutor.attendance.index'))->assertForbidden();
    $this->actingAs($alice)->get(route('tutor.attendance.show', $alice))->assertForbidden();
    $this->actingAs($alice)->put(route('tutor.attendance.update', $alice), ['added' => [attDate(1)]])->assertForbidden();
    $this->actingAs($alice)->post(route('tutor.attendance.billing', $alice), attBillingPayload())->assertForbidden();
    $this->actingAs($tutor)->get(route('student.attendance.index'))->assertForbidden();

    expect(Attendance::count())->toBe(0);
});

it('redirects guests to the login page', function () {
    $this->get(route('tutor.attendance.index'))->assertRedirect(route('login'));
    $this->get(route('student.attendance.index'))->assertRedirect(route('login'));
});

it('lets the super_admin track attendance for their own roster', function () {
    $admin = superAdmin();
    $alice = student(['tutor_id' => $admin->id]);

    $this->actingAs($admin)->put(route('tutor.attendance.update', $alice), ['added' => [attDate(1)]])->assertRedirect();

    expect(Attendance::sole()->tutor_id)->toBe($admin->id);
});
