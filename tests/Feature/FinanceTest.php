<?php

use App\Models\Bill;
use App\Models\Payment;
use App\Models\User;
use App\Services\Ledger;

/**
 * Finance Part A: fee setup, payments, outstanding calculation, and the
 * parent fee-unlock gate. tutor() / student() helpers live in tests/Pest.php.
 */

function billStudent(User $student, float $charges, ?User $tutor = null): Bill
{
    return Bill::create([
        'student_id' => $student->id,
        'tutor_id' => ($tutor ?? tutor())->id,
        'billing_month' => now()->startOfMonth()->toDateString(),
        'lessons_subtotal' => $charges,
        'additional_total' => 0,
        'charges_total' => $charges,
        'outstanding_before' => 0,
        'grand_total' => $charges,
    ]);
}

function payStudent(User $student, float $amount): Payment
{
    return Payment::create([
        'student_id' => $student->id,
        'amount_paid' => $amount,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'paynow',
    ]);
}

// ---- Authorization ----------------------------------------------------------

it('forbids a student from the tutor finance area', function () {
    $this->actingAs(student())
        ->get(route('tutor.finance.index'))
        ->assertForbidden();
});

it('404s when the tutor opens finance for a non-student', function () {
    $tutor = tutor();
    $anotherTutor = tutor();

    $this->actingAs($tutor)
        ->get(route('tutor.finance.show', $anotherTutor))
        ->assertNotFound();
});

// ---- Fee setup --------------------------------------------------------------

it('lets a tutor set and update a single fee rate per student', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);

    $this->actingAs($tutor)
        ->put(route('tutor.finance.fee.save', $student), ['fee_rate_per_hour' => 50])
        ->assertRedirect();

    expect($student->tuitionFee->fee_rate_per_hour)->toEqual('50.00');

    // Saving again updates the same row (does not create a second).
    $this->actingAs($tutor)
        ->put(route('tutor.finance.fee.save', $student), ['fee_rate_per_hour' => 60]);

    expect($student->fresh()->tuitionFee->fee_rate_per_hour)->toEqual('60.00')
        ->and(\App\Models\TuitionFee::where('student_id', $student->id)->count())->toBe(1);
});

// ---- Outstanding calculation ------------------------------------------------

it('computes outstanding as total billed minus total paid', function () {
    $student = student();
    billStudent($student, 200.00);   // billed 200
    payStudent($student, 120.00);    // paid 120

    expect(Ledger::outstanding($student))->toBe(80.00);
});

it('sums multiple payments correctly', function () {
    $student = student();
    billStudent($student, 300.00);
    payStudent($student, 100.00);
    payStudent($student, 50.50);

    expect(Ledger::totalPaid($student))->toBe(150.50)
        ->and(Ledger::outstanding($student))->toBe(149.50);
});

it('shows a credit (negative outstanding) when prepaid', function () {
    $student = student();
    billStudent($student, 100.00);
    payStudent($student, 150.00);

    expect(Ledger::outstanding($student))->toBe(-50.00);
});

it('lowers outstanding when the tutor records a payment', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    billStudent($student, 100.00);

    expect(Ledger::outstanding($student))->toBe(100.00);

    $this->actingAs($tutor)->post(route('tutor.finance.payments.store', $student), [
        'amount_paid' => 40,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
    ])->assertRedirect();

    expect(Ledger::outstanding($student->fresh()))->toBe(60.00);
});

// ---- Fee-unlock gate --------------------------------------------------------

it('redirects a locked student from the fee page to the unlock screen', function () {
    $this->actingAs(student())
        ->get(route('student.fees.index'))
        ->assertRedirect(route('student.fees.unlock'));
});

it('keeps the fee page locked on a wrong password', function () {
    config(['wowlo.fee_view_password' => 'parent-secret']);

    $this->actingAs(student())
        ->post(route('student.fees.unlock.attempt'), ['password' => 'wrong'])
        ->assertSessionHasErrors('password');

    $this->actingAs(student())
        ->get(route('student.fees.index'))
        ->assertRedirect(route('student.fees.unlock'));
});

it('unlocks the fee page with the correct password', function () {
    config(['wowlo.fee_view_password' => 'parent-secret']);
    $student = student();

    $this->actingAs($student)
        ->post(route('student.fees.unlock.attempt'), ['password' => 'parent-secret'])
        ->assertRedirect(route('student.fees.index'));

    $this->actingAs($student)
        ->get(route('student.fees.index'))
        ->assertOk();
});
