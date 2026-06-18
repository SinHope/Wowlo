<?php

use App\Models\Homework;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Homework: authorization, data isolation, R2 upload, and mark-done.
 * tutor() / student() helpers live in tests/Pest.php.
 */

function makeHomework(array $attrs = []): Homework
{
    return Homework::create(array_merge([
        'tutor_id' => tutor()->id,
        'student_id' => student()->id,
        'title' => 'Algebra worksheet',
        'subject' => 'Maths',
        'description' => 'Do questions 1-10.',
        'due_date' => now()->addWeek()->toDateString(),
    ], $attrs));
}

it('forbids a student from the tutor homework area', function () {
    $this->actingAs(student())
        ->get(route('tutor.homework.index'))
        ->assertForbidden();
});

it('forbids a tutor from the student homework area', function () {
    $this->actingAs(tutor())
        ->get(route('student.homework.index'))
        ->assertForbidden();
});

it('lets a tutor assign homework with an R2 attachment', function () {
    Storage::fake('r2');
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);

    $this->actingAs($tutor)->post(route('tutor.homework.store'), [
        'title' => 'Science reading',
        'subject' => 'Science',
        'description' => 'Read chapter 3.',
        'student_id' => $student->id,
        'start_date' => now()->toDateString(),
        'due_date' => now()->addDays(5)->toDateString(),
        'attachment' => UploadedFile::fake()->create('worksheet.pdf', 200, 'application/pdf'),
    ])->assertRedirect(route('tutor.homework.index'));

    $hw = Homework::firstWhere('title', 'Science reading');
    expect($hw)->not->toBeNull()
        ->and($hw->student_id)->toBe($student->id)
        ->and($hw->attachment_path)->not->toBeNull();
    Storage::disk('r2')->assertExists($hw->attachment_path);
});

it('rejects homework assigned to a non-student', function () {
    $tutor = tutor();
    $anotherTutor = tutor();

    $this->actingAs($tutor)->post(route('tutor.homework.store'), [
        'title' => 'X',
        'subject' => 'Maths',
        'description' => 'Y',
        'student_id' => $anotherTutor->id, // not a student
        'due_date' => now()->addDay()->toDateString(),
    ])->assertSessionHasErrors('student_id');
});

it('only shows a student their own homework', function () {
    $mine = makeHomework();
    $theirs = makeHomework(); // belongs to a different (freshly created) student

    $this->actingAs($mine->student)
        ->get(route('student.homework.show', $mine))
        ->assertOk();

    $this->actingAs($mine->student)
        ->get(route('student.homework.show', $theirs))
        ->assertForbidden();
});

it('lets a student claim "done" (submitted) and withdraw it, but never self-confirm done', function () {
    $hw = makeHomework();

    // Claim → submitted (awaiting the tutor's check), NOT done.
    $this->actingAs($hw->student)
        ->patch(route('student.homework.submit', $hw))
        ->assertRedirect();
    expect($hw->fresh()->status)->toBe('submitted')
        ->and($hw->fresh()->completed_at)->toBeNull();

    // Withdraw → back to pending.
    $this->actingAs($hw->student)->patch(route('student.homework.submit', $hw));
    expect($hw->fresh()->status)->toBe('pending');
});

it('does not let a student reopen a tutor-confirmed done', function () {
    $hw = makeHomework(['status' => 'done', 'completed_at' => now()]);

    $this->actingAs($hw->student)->patch(route('student.homework.submit', $hw));

    expect($hw->fresh()->status)->toBe('done');
});

it('forbids a student from claiming someone else\'s homework', function () {
    $hw = makeHomework();
    $intruder = student();

    $this->actingAs($intruder)
        ->patch(route('student.homework.submit', $hw))
        ->assertForbidden();

    expect($hw->fresh()->status)->toBe('pending');
});

it('lets the owning tutor set the done / not_done verdict with feedback', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $hw = makeHomework(['tutor_id' => $tutor->id, 'student_id' => $student->id, 'status' => 'submitted']);

    $this->actingAs($tutor)
        ->patch(route('tutor.homework.verdict', $hw), ['status' => 'done', 'feedback' => 'Great work!'])
        ->assertRedirect(route('tutor.homework.status', ['student_id' => $student->id]));

    expect($hw->fresh()->status)->toBe('done')
        ->and($hw->fresh()->completed_at)->not->toBeNull()
        ->and($hw->fresh()->feedback)->toBe('Great work!');

    // not_done clears the completion timestamp.
    $this->actingAs($tutor)
        ->patch(route('tutor.homework.verdict', $hw), ['status' => 'not_done', 'feedback' => 'Q3 missing.']);
    expect($hw->fresh()->status)->toBe('not_done')
        ->and($hw->fresh()->completed_at)->toBeNull();
});

it('renders the tutor status page with the verdict UI for a student', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    makeHomework(['tutor_id' => $tutor->id, 'student_id' => $student->id, 'status' => 'submitted', 'feedback' => 'Check Q4.']);
    // An overdue one (pending + past due) to exercise the derived badge.
    makeHomework(['tutor_id' => $tutor->id, 'student_id' => $student->id, 'due_date' => now()->subWeek()->toDateString()]);

    $this->actingAs($tutor)
        ->get(route('tutor.homework.status', ['student_id' => $student->id]))
        ->assertOk()
        ->assertSee('Awaiting check')
        ->assertSee('Overdue')
        ->assertSee('Mark Done')
        ->assertSee('Check Q4.');
});

it('shows the tutor a "Homework to Check" count of submitted homework', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    makeHomework(['tutor_id' => $tutor->id, 'student_id' => $student->id, 'status' => 'submitted']);
    makeHomework(['tutor_id' => $tutor->id, 'student_id' => $student->id, 'status' => 'submitted']);
    makeHomework(['tutor_id' => $tutor->id, 'student_id' => $student->id, 'status' => 'pending']);
    // Another tutor's submitted homework must not leak into the count.
    $other = tutor();
    makeHomework(['tutor_id' => $other->id, 'student_id' => student(['tutor_id' => $other->id])->id, 'status' => 'submitted']);

    $this->actingAs($tutor)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewHas('homeworkToCheck', 2)
        ->assertSee('Homework to Check');
});

it('rejects an invalid verdict status', function () {
    $tutor = tutor();
    $hw = makeHomework(['tutor_id' => $tutor->id, 'student_id' => student(['tutor_id' => $tutor->id])->id]);

    $this->actingAs($tutor)
        ->patch(route('tutor.homework.verdict', $hw), ['status' => 'pending'])
        ->assertSessionHasErrors('status');
});

it('404s when a tutor sets a verdict on another tutor\'s homework', function () {
    $hw = makeHomework(); // belongs to a freshly created tutor
    $intruder = tutor();

    $this->actingAs($intruder)
        ->patch(route('tutor.homework.verdict', $hw), ['status' => 'done'])
        ->assertNotFound();

    expect($hw->fresh()->status)->toBe('pending');
});
