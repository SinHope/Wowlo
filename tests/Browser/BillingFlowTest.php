<?php

use App\Models\Bill;
use App\Models\TuitionFee;

/**
 * End-to-end (real Chromium via the Pest browser plugin) for WhatsApp Billing:
 * the confirm pop-up, the "Date not allowed" pop-up, edit and delete.
 * Run separately:  php vendor/bin/pest tests/Browser --filter="bill"
 * One visit() per test (see docs/TESTING.md).
 */

/** A tutor with one student on a $50/hr rate. */
function billingE2e(): array
{
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id, 'name' => 'Alice Tan']);
    TuitionFee::create(['student_id' => $student->id, 'fee_rate_per_hour' => 50, 'currency' => 'SGD']);

    return [$tutor, $student];
}

/** An already-saved bill: one lesson of 2h at $50 = $100, no carried-over balance. */
function savedE2eBill($tutor, $student): Bill
{
    $bill = Bill::create([
        'student_id' => $student->id, 'tutor_id' => $tutor->id,
        'billing_month' => now()->startOfMonth()->toDateString(),
        'lessons_subtotal' => 100, 'additional_total' => 0, 'charges_total' => 100,
        'outstanding_before' => 0, 'grand_total' => 100, 'currency' => 'SGD',
    ]);
    $bill->lines()->create([
        'lesson_date' => now()->subDay()->toDateString(), 'hours' => 2, 'rate' => 50, 'amount' => 100,
    ]);

    return $bill;
}

it('bill: asks "Are you sure?" before saving, and Cancel saves nothing', function () {
    [$tutor, $student] = billingE2e();
    $this->actingAs($tutor);

    $page = visit('/tutor/billing/create')->assertNoJavaScriptErrors();
    $page->select('#student_id', (string) $student->id);
    $page->type('[name="lessons[0][lesson_date]"]', now()->subDay()->toDateString());
    $page->type('[name="lessons[0][hours]"]', '2');

    $page->click('button:has-text("Save & generate")')->assertSee('Have you checked that all entries are correct?');
    $page->click('[role="dialog"] button:has-text("Cancel")');

    expect(Bill::count())->toBe(0);
});

it('bill: Yes, save creates the bill with the server-computed total', function () {
    [$tutor, $student] = billingE2e();
    $this->actingAs($tutor);

    $page = visit('/tutor/billing/create');
    $page->select('#student_id', (string) $student->id);
    $page->type('[name="lessons[0][lesson_date]"]', now()->subDay()->toDateString());
    $page->type('[name="lessons[0][hours]"]', '2');

    $page->click('button:has-text("Save & generate")');
    $page->click('[role="dialog"] button[type="submit"]');   // "Yes, save"
    $page->waitForText('Bill saved.');

    expect(Bill::count())->toBe(1)
        ->and((float) Bill::first()->grand_total)->toBe(100.0);   // 2h × $50
});

it('bill: a lesson date later than today is blocked with a "Date not allowed" pop-up', function () {
    [$tutor, $student] = billingE2e();
    $this->actingAs($tutor);

    $page = visit('/tutor/billing/create');
    $page->select('#student_id', (string) $student->id);
    $page->type('[name="lessons[0][lesson_date]"]', now()->addDay()->toDateString());   // can still be picked…
    $page->type('[name="lessons[0][hours]"]', '2');

    // …but Save shows the warning instead of the confirm pop-up.
    $page->click('button:has-text("Save & generate")')->assertSee('Date not allowed');
    $page->click('[role="alertdialog"] button:has-text("OK, got it")');

    expect(Bill::count())->toBe(0);
});

it('bill: Edit changes the hours and re-saves the bill', function () {
    [$tutor, $student] = billingE2e();
    $bill = savedE2eBill($tutor, $student);
    $this->actingAs($tutor);

    $page = visit("/tutor/billing/{$bill->id}/edit")->assertNoJavaScriptErrors();
    $page->type('[name="lessons[0][hours]"]', '3');
    $page->click('button:has-text("Save changes")');
    $page->click('[role="dialog"] button[type="submit"]');
    $page->waitForText('Bill updated.');

    expect((float) $bill->fresh()->grand_total)->toBe(150.0);   // 3h × $50
});

it('bill: Delete asks first, then removes the bill', function () {
    [$tutor, $student] = billingE2e();
    $bill = savedE2eBill($tutor, $student);
    $this->actingAs($tutor);

    $page = visit("/tutor/billing/{$bill->id}");
    $page->click('button:text-is("Delete")')->assertSee('Delete this bill?');
    $page->click('[role="dialog"] button[type="submit"]');   // "Yes, delete"
    $page->waitForText('Bill deleted.');

    expect(Bill::count())->toBe(0);
});
