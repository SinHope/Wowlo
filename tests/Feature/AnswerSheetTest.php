<?php

use App\Models\AnswerSheet;
use App\Models\Message;
use Illuminate\Support\Facades\Notification;

/**
 * Resources — answer sheets (Slice 13). Covers the build → send/submit → mark
 * flow and the server-side marking maths. Tenancy/IDOR isolation lives in
 * MultiTutorTest.php (the canonical place for cross-tenant proofs).
 */

// ---- Tutor builds + sends ---------------------------------------------------

it('lets a tutor build an OAS sheet, send it to a student, and notify them', function () {
    Notification::fake();
    $tutor = tutor();
    $studentMine = student(['tutor_id' => $tutor->id, 'name' => 'Sendee']);

    $this->actingAs($tutor)
        ->post(route('tutor.resources.store', 'mcq'), [
            'title'      => 'P5 Science SA2 — Booklet A',
            'subject'    => 'Science',
            'student_id' => $studentMine->id,
            'questions'  => [['marks' => 1], ['marks' => 2], ['marks' => 1]],
        ])
        ->assertRedirect(route('tutor.resources.index', 'mcq'));

    $sheet = AnswerSheet::firstWhere('title', 'P5 Science SA2 — Booklet A');
    expect($sheet)->not->toBeNull()
        ->and($sheet->type)->toBe('mcq')
        ->and($sheet->status)->toBe('sent')
        ->and($sheet->tutor_id)->toBe($tutor->id)
        ->and($sheet->student_id)->toBe($studentMine->id)
        ->and($sheet->total_marks)->toBe(4)        // server-computed: 1+2+1
        ->and($sheet->questions()->count())->toBe(3);

    $this->assertDatabaseHas('messages', [
        'sender_id'   => $tutor->id,
        'receiver_id' => $studentMine->id,
        'subject'     => 'New answer sheet to complete: P5 Science SA2 — Booklet A',
    ]);
});

it('saves the tutor sheet-level and per-question remarks on a short-answer sheet', function () {
    Notification::fake();
    $tutor = tutor();
    $studentMine = student(['tutor_id' => $tutor->id]);

    $this->actingAs($tutor)
        ->post(route('tutor.resources.store', 'short_answer'), [
            'title'      => 'P4 English — Comprehension',
            'subject'    => 'English',
            'student_id' => $studentMine->id,
            'remarks'    => 'Answer in full sentences.',
            'questions'  => [
                ['marks' => 2, 'remarks' => 'Use evidence from the passage.'],
                ['marks' => 1],
            ],
        ])
        ->assertRedirect(route('tutor.resources.index', 'short_answer'));

    $sheet = AnswerSheet::firstWhere('title', 'P4 English — Comprehension');
    expect($sheet->remarks)->toBe('Answer in full sentences.')
        ->and($sheet->questions()->orderBy('order')->first()->remarks)->toBe('Use evidence from the passage.')
        ->and($sheet->questions()->orderBy('order')->skip(1)->first()->remarks)->toBeNull();
});

it('shows the remarks fields on the short-answer create page but not the mcq one', function () {
    $tutor = tutor();

    $this->actingAs($tutor)
        ->get(route('tutor.resources.create', 'short_answer'))
        ->assertOk()
        ->assertSee('name="remarks"', false)        // sheet-level
        ->assertSee('[remarks]', false);            // per-question (Alpine :name)

    $this->actingAs($tutor)
        ->get(route('tutor.resources.create', 'mcq'))
        ->assertOk()
        ->assertDontSee('name="remarks"', false);
});

it('rejects a tutor sending a sheet to a non-owned student', function () {
    $tutorA = tutor();
    $studentB = student(['tutor_id' => tutor()->id]);

    $this->actingAs($tutorA)
        ->post(route('tutor.resources.store', 'mcq'), [
            'title'      => 'X',
            'subject'    => 'Science',
            'student_id' => $studentB->id,
            'questions'  => [['marks' => 1]],
        ])
        ->assertSessionHasErrors('student_id');

    expect(AnswerSheet::count())->toBe(0);
});

// ---- Student fills a sent sheet --------------------------------------------

it('lets the student fill and submit a sent sheet, notifying the tutor', function () {
    Notification::fake();
    $tutor = tutor();
    $studentMine = student(['tutor_id' => $tutor->id]);

    $sheet = AnswerSheet::create([
        'author_id' => $tutor->id, 'tutor_id' => $tutor->id, 'student_id' => $studentMine->id,
        'type' => 'mcq', 'title' => 'Sheet', 'subject' => 'Science', 'status' => 'sent', 'total_marks' => 2,
    ]);
    $q1 = $sheet->questions()->create(['order' => 0, 'num_options' => 4, 'marks' => 1]);
    $q2 = $sheet->questions()->create(['order' => 1, 'num_options' => 4, 'marks' => 1]);

    $this->actingAs($studentMine)
        ->post(route('student.resources.submit', $sheet), [
            'answers' => [$q1->id => 3, $q2->id => 1],
        ])
        ->assertRedirect(route('student.resources.index', 'mcq'));

    expect($sheet->fresh()->status)->toBe('submitted')
        ->and($q1->fresh()->choice)->toBe(3)
        ->and($q2->fresh()->choice)->toBe(1);

    $this->assertDatabaseHas('messages', [
        'sender_id'   => $studentMine->id,
        'receiver_id' => $tutor->id,
        'subject'     => 'Answer sheet submitted: Sheet',
    ]);
});

it('rejects an MCQ choice outside the option range', function () {
    $tutor = tutor();
    $studentMine = student(['tutor_id' => $tutor->id]);
    $sheet = AnswerSheet::create([
        'author_id' => $tutor->id, 'tutor_id' => $tutor->id, 'student_id' => $studentMine->id,
        'type' => 'mcq', 'title' => 'Sheet', 'subject' => 'Science', 'status' => 'sent', 'total_marks' => 1,
    ]);
    $q1 = $sheet->questions()->create(['order' => 0, 'num_options' => 4, 'marks' => 1]);

    $this->actingAs($studentMine)
        ->post(route('student.resources.submit', $sheet), ['answers' => [$q1->id => 9]])
        ->assertSessionHasErrors("answers.{$q1->id}");

    expect($sheet->fresh()->status)->toBe('sent');
});

// ---- Student builds + submits their own ------------------------------------

it('lets a student build and submit their own short-answer sheet to their tutor', function () {
    Notification::fake();
    $tutor = tutor();
    $studentMine = student(['tutor_id' => $tutor->id]);

    $this->actingAs($studentMine)
        ->post(route('student.resources.store', 'short_answer'), [
            'title'     => 'My answers',
            'subject'   => 'English',
            'questions' => [['answer_text' => 'Photosynthesis'], ['answer_text' => 'Mitochondria']],
        ])
        ->assertRedirect(route('student.resources.index', 'short_answer'));

    $sheet = AnswerSheet::firstWhere('title', 'My answers');
    expect($sheet->type)->toBe('short_answer')
        ->and($sheet->status)->toBe('submitted')
        ->and($sheet->tutor_id)->toBe($tutor->id)       // resolves to the student's tutor
        ->and($sheet->student_id)->toBe($studentMine->id)
        ->and($sheet->total_marks)->toBe(2)             // 1 mark per row
        ->and($sheet->questions()->first()->answer_text)->toBe('Photosynthesis');

    $this->assertDatabaseHas('messages', [
        'receiver_id' => $tutor->id,
        'subject'     => 'Answer sheet submitted: My answers',
    ]);
});

// ---- Tutor marks (server-side maths) ---------------------------------------

it('lets the tutor decide the marks and recomputes the total when marking', function () {
    Notification::fake();
    $tutor = tutor();
    $studentMine = student(['tutor_id' => $tutor->id]);

    // A student-built sheet defaults to 1 mark per row; the tutor re-weights it.
    $sheet = AnswerSheet::create([
        'author_id' => $studentMine->id, 'tutor_id' => $tutor->id, 'student_id' => $studentMine->id,
        'type' => 'mcq', 'title' => 'Sheet', 'subject' => 'Science', 'status' => 'submitted',
        'submitted_at' => now(), 'total_marks' => 2,
    ]);
    $q1 = $sheet->questions()->create(['order' => 0, 'num_options' => 4, 'marks' => 1, 'choice' => 1]);
    $q2 = $sheet->questions()->create(['order' => 1, 'num_options' => 4, 'marks' => 1, 'choice' => 3]);

    $this->actingAs($tutor)
        ->post(route('tutor.resources.mark.save', $sheet), [
            'qmarks' => [$q1->id => 3, $q2->id => 2],   // tutor sets the marks → total 5
            'grades' => [$q1->id => 'correct', $q2->id => 'partial'],
            'marks'  => [$q1->id => 3, $q2->id => 1],   // 3 + 1 = 4 obtained
            'feedback' => 'Nice work.',
        ])
        ->assertRedirect(route('tutor.resources.show', $sheet));

    $sheet->refresh();
    expect($sheet->status)->toBe('marked')
        ->and($sheet->total_marks)->toBe(5)             // recomputed from qmarks (3+2)
        ->and((float) $sheet->obtained_marks)->toBe(4.0)
        ->and($sheet->marked_at)->not->toBeNull()
        ->and($q1->fresh()->marks)->toBe(3)             // tutor-chosen question total persisted
        ->and($q2->fresh()->marks_awarded)->toBe(1.0);

    $this->assertDatabaseHas('messages', [
        'receiver_id' => $studentMine->id,
        'subject'     => 'Your answer sheet is marked: Sheet',
    ]);
});

it('rejects awarded marks above the tutor-chosen question total', function () {
    $tutor = tutor();
    $studentMine = student(['tutor_id' => $tutor->id]);
    $sheet = AnswerSheet::create([
        'author_id' => $tutor->id, 'tutor_id' => $tutor->id, 'student_id' => $studentMine->id,
        'type' => 'mcq', 'title' => 'Sheet', 'subject' => 'Science', 'status' => 'submitted', 'total_marks' => 1,
    ]);
    $q1 = $sheet->questions()->create(['order' => 0, 'num_options' => 4, 'marks' => 1, 'choice' => 1]);

    $this->actingAs($tutor)
        ->post(route('tutor.resources.mark.save', $sheet), [
            'qmarks' => [$q1->id => 2],
            'grades' => [$q1->id => 'correct'],
            'marks'  => [$q1->id => 5],   // > the chosen total of 2
        ])
        ->assertSessionHasErrors("marks.{$q1->id}");

    expect($sheet->fresh()->status)->toBe('submitted');
});

it('cannot mark a sheet the student has not submitted yet', function () {
    $tutor = tutor();
    $studentMine = student(['tutor_id' => $tutor->id]);
    $sheet = AnswerSheet::create([
        'author_id' => $tutor->id, 'tutor_id' => $tutor->id, 'student_id' => $studentMine->id,
        'type' => 'mcq', 'title' => 'Sheet', 'subject' => 'Science', 'status' => 'sent', 'total_marks' => 1,
    ]);
    $sheet->questions()->create(['order' => 0, 'num_options' => 4, 'marks' => 1]);

    $this->actingAs($tutor)->get(route('tutor.resources.mark', $sheet))->assertNotFound();
});
