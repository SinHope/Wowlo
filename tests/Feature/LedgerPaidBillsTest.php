<?php

use App\Services\Ledger;

/**
 * Ledger::paidBillIds — "is this bill paid?" is DERIVED (bills have no paid
 * flag): payments are applied to the oldest bills first. Drives the green
 * "Paid" attendance days. Helpers attBill()/attPay() live in AttendanceTest.php.
 */
function ledgerStudent()
{
    return student(['tutor_id' => tutor()->id]);
}

it('marks nothing paid when there are no payments', function () {
    $s = ledgerStudent();
    attBill($s, 120);

    expect(Ledger::paidBillIds($s))->toBe([]);
});

it('marks nothing paid for a student with no bills', function () {
    expect(Ledger::paidBillIds(ledgerStudent()))->toBe([]);
});

it('leaves a partly paid bill unpaid', function () {
    $s = ledgerStudent();
    attBill($s, 120);
    attPay($s, 119.99);

    expect(Ledger::paidBillIds($s))->toBe([]);
});

it('marks a bill paid on an exact payment', function () {
    $s = ledgerStudent();
    $bill = attBill($s, 120);
    attPay($s, 120);

    expect(Ledger::paidBillIds($s))->toBe([$bill->id]);
});

it('treats overpayment (credit) as paying every bill', function () {
    $s = ledgerStudent();
    $a = attBill($s, 100, '2026-05-01');
    $b = attBill($s, 100, '2026-06-01');
    attPay($s, 500);

    expect(Ledger::paidBillIds($s))->toBe([$a->id, $b->id]);
});

it('pays the oldest bills first (FIFO by billing month, then id) and stops at the first unpaid one', function () {
    $s = ledgerStudent();
    $dec = attBill($s, 120, '2026-12-01');   // created first but billed for a LATER month
    $sep = attBill($s, 120, '2026-09-01');
    $oct = attBill($s, 120, '2026-10-01');
    $nov = attBill($s, 120, '2026-11-01');
    attPay($s, 300);                         // covers Sep + Oct ($240), $60 short of Nov

    expect(Ledger::paidBillIds($s))->toBe([$sep->id, $oct->id])
        ->and(Ledger::paidBillIds($s))->not->toContain($nov->id, $dec->id);
});

it('breaks a same-month tie by id', function () {
    $s = ledgerStudent();
    $first = attBill($s, 50, '2026-06-01');
    $second = attBill($s, 50, '2026-06-01');
    attPay($s, 50);

    expect(Ledger::paidBillIds($s))->toBe([$first->id])->and(Ledger::paidBillIds($s))->not->toContain($second->id);
});

it('counts a zero-total bill as paid, and does not let it block later bills', function () {
    $s = ledgerStudent();
    $zero = attBill($s, 0, '2026-05-01');
    $real = attBill($s, 80, '2026-06-01');
    attPay($s, 80);

    expect(Ledger::paidBillIds($s))->toBe([$zero->id, $real->id]);
});

it('tolerates a half-cent rounding difference', function () {
    $s = ledgerStudent();
    $bill = attBill($s, 33.33);
    attPay($s, 33.33);

    expect(Ledger::paidBillIds($s))->toBe([$bill->id]);
});

it('only looks at the given student\'s bills and payments', function () {
    $a = ledgerStudent();
    $b = ledgerStudent();
    attBill($a, 100);
    attPay($b, 100);

    expect(Ledger::paidBillIds($a))->toBe([]);
});
