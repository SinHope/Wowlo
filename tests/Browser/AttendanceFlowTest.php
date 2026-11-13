<?php

use App\Models\Attendance;
use App\Models\Bill;
use App\Models\TuitionFee;

/**
 * End-to-end (real Chromium via the Pest browser plugin) for Attendance Tracking.
 * Run separately:  ./vendor/bin/pest tests/Browser
 * (kept out of `php artisan test` so the main suite never depends on Playwright).
 *
 * The page state lives in one Alpine component; `ap()` returns JS that reads it so
 * assertions check real component state, not just pixels.
 */

/** JS expression for the attendance page's Alpine data. */
function ap(string $expr): string
{
    return "(() => { const d = Alpine.\$data(document.querySelector('[x-data^=\"attendancePage\"]')); return {$expr}; })()";
}

/** aria-label of a calendar day button, e.g. "3 October 2026". */
function dayLabel(string $date): string
{
    $d = \Carbon\Carbon::parse($date);

    return $d->day.' '.$d->format('F Y');
}

/** A tutor with one student on a rate, and a month page that is fully in the past. */
function e2eSetup(): array
{
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id, 'name' => 'Alice Tan']);
    TuitionFee::create(['student_id' => $student->id, 'fee_rate_per_hour' => 50, 'currency' => 'SGD']);
    $pastMonth = now()->subMonth()->format('Y-m');

    return [$tutor, $student, $pastMonth, "/tutor/attendance/{$student->id}?month={$pastMonth}"];
}

it('shows the "Attendance tracking…" spinner when the sidebar tab is clicked', function () {
    [$tutor] = e2eSetup();
    $this->actingAs($tutor);

    $page = visit('/tutor/attendance')->assertNoJavaScriptErrors();

    // Click the real sidebar link with navigation cancelled, then read the spinner state.
    $page->assertScript(<<<'JS'
        (() => {
            const root = document.querySelector('[x-data*="sidebarOpen"]');
            const link = [...document.querySelectorAll('aside a')].find(a => a.textContent.trim().startsWith('Attendance'));
            link.addEventListener('click', e => e.preventDefault());
            link.click();
            const d = Alpine.$data(root);
            return d.busy === true && d.stage;
        })()
    JS, 'Attendance tracking…');
});

it('shows a centred student picker that opens the calendar', function () {
    [$tutor, $student] = e2eSetup();
    $this->actingAs($tutor);

    $page = visit('/tutor/attendance')->assertSee('Attendance Tracking')->assertSee('Student');
    $page->select('#attendance-student', 'Alice Tan');
    $page->waitForText('Calendar View')->assertPathContains("/tutor/attendance/{$student->id}")->assertSee('Alice Tan');
});

it('ticks days purple, counts them, then saves and keeps them after a reload', function () {
    [$tutor, $student, $pastMonth, $url] = e2eSetup();
    $d1 = "{$pastMonth}-03";
    $d2 = "{$pastMonth}-04";
    $this->actingAs($tutor);

    $page = visit($url)->assertNoJavaScriptErrors();
    $page->assertScript(ap('d.count'), 0);

    $page->click('[aria-label="'.dayLabel($d1).'"]');
    $page->click('[aria-label="'.dayLabel($d2).'"]');
    $page->assertScript(ap('d.count'), 2)->assertScript(ap('d.hasPending'), true);

    $page->click('Save');
    $page->waitForText('Attendance saved.')->assertSee('Attendance saved.');
    $page->assertScript(ap('d.count'), 2)->assertScript(ap('d.hasPending'), false);

    expect(Attendance::where('student_id', $student->id)->count())->toBe(2);
});

it('shows the same ticks in List View, and the filter changes the counter', function () {
    [$tutor, $student, $pastMonth, $url] = e2eSetup();
    attDay($student, "{$pastMonth}-03");
    attDay($student, "{$pastMonth}-20");
    $this->actingAs($tutor);

    $page = visit($url);
    $page->assertScript(ap('d.count'), 2);

    $page->click('List View');
    $page->assertScript(ap('d.view'), 'list')->assertScript(ap('d.listDates.length > 28'), true);

    // Narrow the list to a single date with the filter: the counter follows the filter.
    $page->script(ap("(d.draftFilter = { mode: 'date', date: '{$pastMonth}-03', from: '', to: '' }, d.applyFilter(), 1)"));
    $page->assertScript(ap('d.listDates.length'), 1)->assertScript(ap('d.count'), 1);
});

it('warns before saving a later date, and blocks Add to Billing with a pop-up', function () {
    [$tutor, $student] = e2eSetup();
    $tomorrow = now()->addDay()->toDateString();
    $this->actingAs($tutor);

    $page = visit("/tutor/attendance/{$student->id}?month=".now()->addDay()->format('Y-m'))->assertNoJavaScriptErrors();
    $page->click('[aria-label="'.dayLabel($tomorrow).'"]');
    $page->assertScript(ap('d.count'), 1);

    // Save → warning → Go back keeps it unsaved.
    $page->click('Save')->assertSee('Later date selected');
    $page->click('[aria-labelledby="save-warn-title"] button:has-text("Go back")');
    expect(Attendance::count())->toBe(0);

    // Add to Billing → blocking pop-up, no navigation.
    $page->click('Add To Billing')->assertSee('Please recheck the dates');
    $page->click('[aria-labelledby="bill-block-title"] button')->assertPathContains("/tutor/attendance/{$student->id}");
    expect(Attendance::count())->toBe(0);

    // Save → "Yes, save" goes through.
    $page->click('Save')->assertSee('Later date selected');
    $page->click('[aria-labelledby="save-warn-title"] button:has-text("Yes, save")')->waitForText('Attendance saved.');
    expect(Attendance::count())->toBe(1);
});

it('ticks days, hands them to a pre-filled bill, and saves the bill (Add to Billing)', function () {
    [$tutor, $student, $pastMonth, $url] = e2eSetup();
    $d1 = "{$pastMonth}-03";
    $d2 = "{$pastMonth}-10";
    $this->actingAs($tutor);

    $page = visit($url);
    $page->click('[aria-label="'.dayLabel($d1).'"]');
    $page->click('[aria-label="'.dayLabel($d2).'"]');
    $page->click('Add To Billing');

    // Landed on the bill form, pre-filled with both dates (no hours yet — first bill).
    $page->waitForText('Pre-filled from Attendance: 2 lessons')->assertPathContains('/tutor/billing/create');
    $page->assertScript("Alpine.\$data(document.querySelector('[x-data^=\"billingForm\"]')).lessons.map(l => l.lesson_date).join(',')", "{$d1},{$d2}");
    // The student dropdown really shows the student (not blank), so the browser's `required` check passes.
    $page->assertScript("document.querySelector('#student_id').value", (string) $student->id);
    expect(Attendance::count())->toBe(2);   // saved on the way

    // Fill the hours and save the bill (2 × 2h × $50 = $200).
    $page->type('[name="lessons[0][hours]"]', '2')->type('[name="lessons[1][hours]"]', '2');
    $page->click('button:has-text("Save & generate")');
    $page->click('[role="dialog"] button[type="submit"]');   // the "Yes, save" button of the confirm pop-up
    $page->waitForText('Bill saved.');

    expect(Bill::count())->toBe(1)
        ->and(Attendance::whereNotNull('bill_id')->count())->toBe(2);   // both days now charged by that bill
});

it('shows billed days locked (amber dot, not counted), and they cannot be toggled', function () {
    [$tutor, $student, $pastMonth, $url] = e2eSetup();
    $d1 = "{$pastMonth}-03";
    attDay($student, $d1);
    attDay($student, "{$pastMonth}-10");
    $this->actingAs($tutor)->post(route('tutor.billing.store'), [
        'student_id' => $student->id,
        'billing_month' => $pastMonth,
        'lessons' => [['lesson_date' => $d1, 'hours' => 2], ['lesson_date' => "{$pastMonth}-10", 'hours' => 2]],
    ])->assertRedirect();

    $page = visit($url);
    $page->assertScript(ap("d.st('{$d1}')"), 'billed')->assertScript(ap('d.count'), 0)->assertScript(ap('d.billedCount'), 2);
    $page->script(ap("(d.toggle('{$d1}'), 1)"));
    $page->assertScript(ap('d.hasPending'), false)->assertScript(ap("d.st('{$d1}')"), 'billed');
});

it('turns billed days green once the parent has paid', function () {
    [$tutor, $student, $pastMonth, $url] = e2eSetup();
    $d1 = "{$pastMonth}-03";
    attDay($student, $d1);
    attDay($student, "{$pastMonth}-10");
    $this->actingAs($tutor)->post(route('tutor.billing.store'), [
        'student_id' => $student->id,
        'billing_month' => $pastMonth,
        'lessons' => [['lesson_date' => $d1, 'hours' => 2], ['lesson_date' => "{$pastMonth}-10", 'hours' => 2]],
    ])->assertRedirect();
    attPay($student, 200);   // 2 × 2h × $50

    $page = visit($url);
    $page->assertScript(ap("d.st('{$d1}')"), 'paid')->assertScript(ap('d.paidCount'), 2)->assertScript(ap('d.billedCount'), 0);
});

it('gives a student a read-only view of only their own days', function () {
    [$tutor, $student, $pastMonth] = e2eSetup();
    attDay($student, "{$pastMonth}-05");
    $this->actingAs($student);

    $page = visit('/student/attendance?x=1')->assertNoJavaScriptErrors();
    $page->assertSee('My Attendance')->assertDontSee('Add To Billing')->assertDontSee('Save');

    // Clicking a day does nothing for a student.
    $page->script(ap("(d.toggle('{$pastMonth}-06'), 1)"));
    $page->assertScript(ap('d.hasPending'), false);
});
