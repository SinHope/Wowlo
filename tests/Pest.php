<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

// End-to-end browser tests (Pest browser plugin + Playwright). Run separately:
//   ./vendor/bin/pest tests/Browser
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function tutor(array $attrs = []): \App\Models\User
{
    return \App\Models\User::factory()->create(array_merge(['role' => 'tutor'], $attrs));
}

function superAdmin(array $attrs = []): \App\Models\User
{
    return \App\Models\User::factory()->create(array_merge(['role' => 'super_admin'], $attrs));
}

/**
 * A student. Pass ['tutor_id' => $tutor->id] to place them on a tutor's roster
 * (required for any tutor action that touches the student — tenancy).
 */
function student(array $attrs = []): \App\Models\User
{
    return \App\Models\User::factory()->create(array_merge(['role' => 'student'], $attrs));
}

// ---- Attendance Tracking helpers (shared by AttendanceTest, MultiTutorTest, BillingTest, LedgerPaidBillsTest) ----

function attDate(int $daysAgo): string
{
    return now()->subDays($daysAgo)->toDateString();
}

/** A saved attendance day, owned by $student's tutor. */
function attDay(\App\Models\User $student, string $date, ?int $billId = null): \App\Models\Attendance
{
    return \App\Models\Attendance::forceCreate([
        'tutor_id' => $student->tutor_id,
        'student_id' => $student->id,
        'lesson_date' => $date,
        'bill_id' => $billId,
    ]);
}

/** A bill straight into the DB (charges_total is what the ledger reads). */
function attBill(\App\Models\User $student, float $total, string $month = '2026-06-01'): \App\Models\Bill
{
    return \App\Models\Bill::create([
        'student_id' => $student->id,
        'tutor_id' => $student->tutor_id,
        'billing_month' => $month,
        'lessons_subtotal' => $total,
        'charges_total' => $total,
        'grand_total' => $total,
        'currency' => 'SGD',
    ]);
}

function attPay(\App\Models\User $student, float $amount): \App\Models\Payment
{
    return \App\Models\Payment::create([
        'student_id' => $student->id,
        'amount_paid' => $amount,
        'payment_date' => now()->toDateString(),
        'payment_method' => 'cash',
    ]);
}

function attRows($response): array
{
    return collect($response->viewData('config')['rows'])->pluck('status', 'date')->all();
}

function attBillingPayload(array $overrides = []): array
{
    return array_merge([
        'window_from' => now()->startOfMonth()->subMonth()->toDateString(),
        'window_to' => now()->toDateString(),
        'view' => 'calendar',
        'month' => now()->format('Y-m'),
    ], $overrides);
}
