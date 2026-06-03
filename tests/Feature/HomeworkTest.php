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

it('lets a student mark their homework done and undone', function () {
    $hw = makeHomework();

    $this->actingAs($hw->student)
        ->patch(route('student.homework.toggle', $hw))
        ->assertRedirect();

    expect($hw->fresh()->status)->toBe('done')
        ->and($hw->fresh()->completed_at)->not->toBeNull();

    $this->actingAs($hw->student)->patch(route('student.homework.toggle', $hw));
    expect($hw->fresh()->status)->toBe('pending')
        ->and($hw->fresh()->completed_at)->toBeNull();
});

it('forbids a student from toggling someone else\'s homework', function () {
    $hw = makeHomework();
    $intruder = student();

    $this->actingAs($intruder)
        ->patch(route('student.homework.toggle', $hw))
        ->assertForbidden();

    expect($hw->fresh()->status)->toBe('pending');
});
