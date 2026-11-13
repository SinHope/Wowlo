<?php

use App\Models\Bill;
use App\Models\Payment;
use App\Models\TuitionFee;
use App\Models\User;
use App\Services\Ledger;

/**
 * Finance Part B: the WhatsApp billing generator — per-lesson calculation,
 * persistence of lines/charges, grand total, and the outstanding snapshot.
 */

function studentWithRate(float $rate, ?User $tutor = null): User
{
    $student = student($tutor ? ['tutor_id' => $tutor->id] : []);
    TuitionFee::create([
        'student_id' => $student->id,
        'fee_rate_per_hour' => $rate,
        'currency' => 'SGD',
    ]);

    return $student;
}

it('forbids a student from the billing area', function () {
    $this->actingAs(student())
        ->get(route('tutor.billing.create'))
        ->assertForbidden();
});

it('computes each lesson as rate × hours with varying hours', function () {
    $tutor = tutor();
    $student = studentWithRate(50, $tutor);

    $this->actingAs($tutor)->post(route('tutor.billing.store'), [
        'student_id' => $student->id,
        'billing_month' => '2026-06',
        'lessons' => [
            ['lesson_date' => '2026-06-03', 'hours' => 1.5],
            ['lesson_date' => '2026-06-10', 'hours' => 2],
        ],
        'charges' => [
            ['description' => 'Assessment book', 'amount' => 12.50],
        ],
    ])->assertRedirect();

    $bill = Bill::with(['lines', 'charges'])->firstWhere('student_id', $student->id);

    expect($bill->lines)->toHaveCount(2)
        ->and($bill->lines[0]->amount)->toEqual('75.00')   // 50 × 1.5
        ->and($bill->lines[1]->amount)->toEqual('100.00')  // 50 × 2
        ->and($bill->lessons_subtotal)->toEqual('175.00')
        ->and($bill->additional_total)->toEqual('12.50')
        ->and($bill->charges_total)->toEqual('187.50')
        ->and($bill->grand_total)->toEqual('187.50')       // no prior outstanding
        ->and($bill->charges)->toHaveCount(1);
});

it('snapshots prior outstanding onto the new bill and folds it into the grand total', function () {
    $tutor = tutor();
    $student = studentWithRate(40, $tutor);

    // Establish a prior balance: billed 200, paid 50 → outstanding 150.
    Bill::create([
        'student_id' => $student->id, 'tutor_id' => $tutor->id,
        'billing_month' => '2026-05-01', 'lessons_subtotal' => 200, 'additional_total' => 0,
        'charges_total' => 200, 'outstanding_before' => 0, 'grand_total' => 200,
    ]);
    Payment::create([
        'student_id' => $student->id, 'amount_paid' => 50,
        'payment_date' => '2026-05-20', 'payment_method' => 'paynow',
    ]);

    expect(Ledger::outstanding($student))->toBe(150.00);

    $this->actingAs($tutor)->post(route('tutor.billing.store'), [
        'student_id' => $student->id,
        'billing_month' => '2026-06',
        'lessons' => [['lesson_date' => '2026-06-05', 'hours' => 2]], // 40 × 2 = 80
    ])->assertRedirect();

    $bill = Bill::where('student_id', $student->id)->latest('id')->first();

    expect($bill->charges_total)->toEqual('80.00')
        ->and($bill->outstanding_before)->toEqual('150.00')
        ->and($bill->grand_total)->toEqual('230.00');      // 80 + 150

    // Ledger now reflects both bills minus the payment: (200 + 80) − 50 = 230.
    expect(Ledger::outstanding($student->fresh()))->toBe(230.00);
});

it('rejects billing a student with no rate set', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]); // owned, but no TuitionFee

    $this->actingAs($tutor)->post(route('tutor.billing.store'), [
        'student_id' => $student->id,
        'billing_month' => '2026-06',
        'lessons' => [['lesson_date' => '2026-06-05', 'hours' => 1]],
    ])->assertSessionHasErrors('student_id');

    expect(Bill::where('student_id', $student->id)->count())->toBe(0);
});

it('requires at least one lesson line', function () {
    $tutor = tutor();
    $student = studentWithRate(50);

    $this->actingAs($tutor)->post(route('tutor.billing.store'), [
        'student_id' => $student->id,
        'billing_month' => '2026-06',
        'lessons' => [],
    ])->assertSessionHasErrors('lessons');
});

it('rejects a bill for a non-student', function () {
    $tutor = tutor();
    $anotherTutor = tutor();

    $this->actingAs($tutor)->post(route('tutor.billing.store'), [
        'student_id' => $anotherTutor->id,
        'billing_month' => '2026-06',
        'lessons' => [['lesson_date' => '2026-06-05', 'hours' => 1]],
    ])->assertSessionHasErrors('student_id');
});

it('puts the tutor\'s own payment instructions into the bill message', function () {
    $tutor = tutor(['payment_instructions' => 'PayNow: 9123 4567']);
    $student = studentWithRate(50, $tutor);

    $bill = Bill::create([
        'student_id' => $student->id, 'tutor_id' => $tutor->id,
        'billing_month' => '2026-06-01', 'lessons_subtotal' => 50, 'additional_total' => 0,
        'charges_total' => 50, 'outstanding_before' => 0, 'grand_total' => 50, 'currency' => 'SGD',
    ]);

    expect(App\Services\BillMessage::for($bill))->toContain('PayNow: 9123 4567');
});

it('omits the payment line when the tutor has no payment instructions', function () {
    $tutor = tutor(['payment_instructions' => null]);
    $student = studentWithRate(50, $tutor);

    $bill = Bill::create([
        'student_id' => $student->id, 'tutor_id' => $tutor->id,
        'billing_month' => '2026-06-01', 'lessons_subtotal' => 50, 'additional_total' => 0,
        'charges_total' => 50, 'outstanding_before' => 0, 'grand_total' => 50, 'currency' => 'SGD',
    ]);

    $message = App\Services\BillMessage::for($bill);
    expect($message)->not->toContain('PayNow')
        ->and($message)->toContain('Thank you!');
});

it('uses each tutor\'s own payment instructions, never another tutor\'s', function () {
    $tutorA = tutor(['payment_instructions' => 'Bank transfer A — 111']);
    tutor(['payment_instructions' => 'Bank transfer B — 222']); // a different tutor
    $studentA = studentWithRate(50, $tutorA);

    $billA = Bill::create([
        'student_id' => $studentA->id, 'tutor_id' => $tutorA->id,
        'billing_month' => '2026-06-01', 'lessons_subtotal' => 50, 'additional_total' => 0,
        'charges_total' => 50, 'outstanding_before' => 0, 'grand_total' => 50, 'currency' => 'SGD',
    ]);

    expect(App\Services\BillMessage::for($billA))
        ->toContain('Bank transfer A — 111')
        ->not->toContain('222');
});

it('drops empty additional-charge rows', function () {
    $tutor = tutor();
    $student = studentWithRate(50, $tutor);

    $this->actingAs($tutor)->post(route('tutor.billing.store'), [
        'student_id' => $student->id,
        'billing_month' => '2026-06',
        'lessons' => [['lesson_date' => '2026-06-05', 'hours' => 1]],
        'charges' => [
            ['description' => '', 'amount' => ''],          // empty → dropped
            ['description' => 'Notes', 'amount' => 5],
        ],
    ])->assertRedirect();

    $bill = Bill::with('charges')->firstWhere('student_id', $student->id);
    expect($bill->charges)->toHaveCount(1)
        ->and($bill->charges[0]->description)->toBe('Notes');
});

// ---- Edit / delete ----------------------------------------------------------

function savedBill(User $tutor, User $student): Bill
{
    Payment::create([
        'student_id' => $student->id, 'amount_paid' => 20,
        'payment_date' => '2026-05-01', 'payment_method' => 'paynow',
    ]);

    test()->actingAs($tutor)->post(route('tutor.billing.store'), [
        'student_id' => $student->id,
        'billing_month' => '2026-06',
        'lessons' => [['lesson_date' => '2026-06-03', 'hours' => 2]],
        'charges' => [['description' => 'Book', 'amount' => 10]],
    ]);

    return Bill::latest('id')->first();
}

it('edits a bill: recomputes server-side, replaces lines, keeps student and outstanding snapshot', function () {
    $tutor = tutor();
    $student = studentWithRate(50, $tutor);
    $other = studentWithRate(50, $tutor);
    $bill = savedBill($tutor, $student);   // snapshot = -20 (credit)

    $this->actingAs($tutor)->get(route('tutor.billing.edit', $bill))->assertOk();

    $this->actingAs($tutor)->put(route('tutor.billing.update', $bill), [
        'student_id' => $other->id,          // must be ignored
        'billing_month' => '2026-07',
        'lessons' => [
            ['lesson_date' => '2026-07-01', 'hours' => 1],
            ['lesson_date' => '2026-07-08', 'hours' => 1.5],
        ],
        'charges' => [],
        'grand_total' => 1,                   // client math must be ignored
    ])->assertRedirect(route('tutor.billing.show', $bill));

    $bill->refresh();
    expect($bill->student_id)->toBe($student->id)
        ->and((float) $bill->lessons_subtotal)->toBe(125.0)
        ->and((float) $bill->additional_total)->toBe(0.0)
        ->and((float) $bill->outstanding_before)->toBe(-20.0)
        ->and((float) $bill->grand_total)->toBe(105.0)
        ->and($bill->billing_month->format('Y-m'))->toBe('2026-07')
        ->and($bill->lines()->count())->toBe(2)
        ->and($bill->charges()->count())->toBe(0);
});

it('deletes a bill with its lines and charges, and the student balance corrects itself', function () {
    $tutor = tutor();
    $student = studentWithRate(50, $tutor);
    $bill = savedBill($tutor, $student);   // billed 110, paid 20 => owes 90
    expect(Ledger::outstanding($student))->toBe(90.0);

    $this->actingAs($tutor)->delete(route('tutor.billing.destroy', $bill))
        ->assertRedirect(route('tutor.billing.index'));

    expect(Bill::find($bill->id))->toBeNull()
        ->and(\App\Models\BillLine::where('bill_id', $bill->id)->count())->toBe(0)
        ->and(\App\Models\BillCharge::where('bill_id', $bill->id)->count())->toBe(0)
        ->and(Ledger::outstanding($student))->toBe(-20.0);
});

// ---- No lesson dated later than today ---------------------------------------

it('rejects a lesson dated after today and saves nothing', function () {
    $tutor = tutor();
    $student = studentWithRate(50, $tutor);

    $this->actingAs($tutor)->post(route('tutor.billing.store'), [
        'student_id' => $student->id,
        'billing_month' => now()->format('Y-m'),
        'lessons' => [
            ['lesson_date' => now()->toDateString(), 'hours' => 1],
            ['lesson_date' => now()->addDay()->toDateString(), 'hours' => 1],
        ],
    ])->assertSessionHasErrors('lessons.1.lesson_date');

    expect(Bill::count())->toBe(0);
});

it('accepts a lesson dated today', function () {
    $tutor = tutor();
    $student = studentWithRate(50, $tutor);

    $this->actingAs($tutor)->post(route('tutor.billing.store'), [
        'student_id' => $student->id,
        'billing_month' => now()->format('Y-m'),
        'lessons' => [['lesson_date' => now()->toDateString(), 'hours' => 1]],
    ])->assertSessionHasNoErrors();

    expect(Bill::count())->toBe(1);
});

it('rejects changing a lesson to a future date when editing a bill', function () {
    $tutor = tutor();
    $student = studentWithRate(50, $tutor);
    $bill = savedBill($tutor, $student);

    $this->actingAs($tutor)->put(route('tutor.billing.update', $bill), [
        'billing_month' => '2026-06',
        'lessons' => [['lesson_date' => now()->addWeek()->toDateString(), 'hours' => 1]],
    ])->assertSessionHasErrors('lessons.0.lesson_date');

    expect($bill->lines()->count())->toBe(1)
        ->and($bill->lines()->first()->lesson_date->toDateString())->toBe('2026-06-03');
});

// ---- Attendance linking (docs/attendance-tracking.md D11) -------------------------

it('links matching unbilled attendance days when a bill is stored, and only those', function () {
    $tutor = tutor();
    $student = studentWithRate(50, $tutor);
    attDay($student, '2026-06-03');
    attDay($student, '2026-06-10');
    attDay($student, '2026-06-20');   // not on this bill

    $this->actingAs($tutor)->post(route('tutor.billing.store'), [
        'student_id' => $student->id,
        'billing_month' => '2026-06',
        'lessons' => [
            ['lesson_date' => '2026-06-03', 'hours' => 1],
            ['lesson_date' => '2026-06-10', 'hours' => 1],
            ['lesson_date' => '2026-06-15', 'hours' => 1],   // no attendance row for this one — fine
        ],
    ])->assertRedirect();

    $bill = Bill::sole();
    $byDate = \App\Models\Attendance::where('student_id', $student->id)->get()
        ->mapWithKeys(fn ($a) => [$a->lesson_date->toDateString() => $a->bill_id]);

    expect($byDate['2026-06-03'])->toBe($bill->id)
        ->and($byDate['2026-06-10'])->toBe($bill->id)
        ->and($byDate['2026-06-20'])->toBeNull()
        ->and($byDate)->toHaveCount(3);   // a manual bill line does not invent attendance
});

it('does not steal an attendance day that another bill already charged', function () {
    $tutor = tutor();
    $student = studentWithRate(50, $tutor);
    $first = savedBill($tutor, $student);   // bill on 2026-06-03
    attDay($student, '2026-06-03', $first->id);

    $this->actingAs($tutor)->post(route('tutor.billing.store'), [
        'student_id' => $student->id,
        'billing_month' => '2026-06',
        'lessons' => [['lesson_date' => '2026-06-03', 'hours' => 1]],
    ])->assertRedirect();

    expect(\App\Models\Attendance::sole()->bill_id)->toBe($first->id);
});

it('re-syncs attendance links when a bill is edited', function () {
    $tutor = tutor();
    $student = studentWithRate(50, $tutor);
    $bill = savedBill($tutor, $student);   // line on 2026-06-03
    attDay($student, '2026-06-03', $bill->id);
    attDay($student, '2026-06-17');

    $this->actingAs($tutor)->put(route('tutor.billing.update', $bill), [
        'billing_month' => '2026-06',
        'lessons' => [['lesson_date' => '2026-06-17', 'hours' => 1]],   // 06-03 dropped, 06-17 added
    ])->assertRedirect();

    $byDate = \App\Models\Attendance::where('student_id', $student->id)->get()
        ->mapWithKeys(fn ($a) => [$a->lesson_date->toDateString() => $a->bill_id]);

    expect($byDate['2026-06-03'])->toBeNull()
        ->and($byDate['2026-06-17'])->toBe($bill->id);
});

it('returns attendance days to unbilled when their bill is deleted', function () {
    $tutor = tutor();
    $student = studentWithRate(50, $tutor);
    $bill = savedBill($tutor, $student);
    attDay($student, '2026-06-03', $bill->id);

    $this->actingAs($tutor)->delete(route('tutor.billing.destroy', $bill))->assertRedirect();

    expect(\App\Models\Attendance::sole()->bill_id)->toBeNull();
});
