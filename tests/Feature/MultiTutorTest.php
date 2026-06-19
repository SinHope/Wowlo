<?php

use App\Models\Bill;
use App\Models\ExamPaper;
use App\Models\Homework;
use App\Models\Quiz;
use App\Models\QuizAnswer;
use App\Models\QuizAssignment;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Multi-tutor tenancy (Slice 10.5). Proves that one tutor can never see or
 * touch another tutor's students/content (incl. direct-ID IDOR attempts),
 * that only the super_admin manages tutor accounts, and that exam papers are
 * a shared, moderated library. tutor()/superAdmin()/student() live in Pest.php.
 */

// ---- Roster isolation -------------------------------------------------------

it('shows a tutor only their own students', function () {
    $tutorA = tutor();
    $tutorB = tutor();
    student(['tutor_id' => $tutorA->id, 'name' => 'Alice Mine']);
    student(['tutor_id' => $tutorB->id, 'name' => 'Bob Theirs']);

    $this->actingAs($tutorA)
        ->get(route('tutor.students.index'))
        ->assertOk()
        ->assertSee('Alice Mine')
        ->assertDontSee('Bob Theirs');
});

it('404s when a tutor edits or deletes another tutor\'s student', function () {
    $tutorA = tutor();
    $studentB = student(['tutor_id' => tutor()->id]);

    $this->actingAs($tutorA)->get(route('tutor.students.edit', $studentB))->assertNotFound();
    $this->actingAs($tutorA)->delete(route('tutor.students.destroy', $studentB))->assertNotFound();
    expect(User::find($studentB->id))->not->toBeNull();
});

it('stamps the creating tutor as owner and ignores a client-supplied tutor_id', function () {
    $tutorA = tutor();
    $tutorB = tutor();

    $this->actingAs($tutorA)->post(route('tutor.students.store'), [
        'name' => 'New Kid',
        'email' => 'newkid@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone_1' => '90001234',
        'tutor_id' => $tutorB->id, // attempted hijack — must be ignored
    ])->assertRedirect(route('tutor.students.index'));

    expect(User::firstWhere('email', 'newkid@example.com')->tutor_id)->toBe($tutorA->id);
});

// ---- Content isolation (IDOR) ----------------------------------------------

it('404s when a tutor opens another tutor\'s homework, quiz, bill, or finance', function () {
    $tutorA = tutor();
    $tutorB = tutor();
    $studentB = student(['tutor_id' => $tutorB->id]);

    $hwB = Homework::create([
        'tutor_id' => $tutorB->id, 'student_id' => $studentB->id,
        'title' => 'B HW', 'subject' => 'Maths', 'description' => '...',
        'due_date' => now()->addWeek()->toDateString(),
    ]);
    $quizB = Quiz::create([
        'tutor_id' => $tutorB->id, 'title' => 'B Quiz',
        'level' => 'Primary 4', 'subject' => 'Science', 'exam_type' => 'WA1',
    ]);
    $billB = Bill::create([
        'student_id' => $studentB->id, 'tutor_id' => $tutorB->id,
        'billing_month' => now()->startOfMonth()->toDateString(),
        'lessons_subtotal' => 100, 'additional_total' => 0, 'charges_total' => 100,
        'outstanding_before' => 0, 'grand_total' => 100,
    ]);

    $this->actingAs($tutorA)->get(route('tutor.homework.edit', $hwB))->assertNotFound();
    $this->actingAs($tutorA)->delete(route('tutor.homework.destroy', $hwB))->assertNotFound();
    $this->actingAs($tutorA)->get(route('tutor.quizzes.show', $quizB))->assertNotFound();
    $this->actingAs($tutorA)->delete(route('tutor.quizzes.destroy', $quizB))->assertNotFound();
    $this->actingAs($tutorA)->get(route('tutor.billing.show', $billB))->assertNotFound();
    $this->actingAs($tutorA)->get(route('tutor.finance.show', $studentB))->assertNotFound();
});

it('stops a tutor assigning homework or a quiz to another tutor\'s student', function () {
    $tutorA = tutor();
    $studentB = student(['tutor_id' => tutor()->id]);

    // Homework to a non-owned student → 404 (passes validation, fails ownership).
    $this->actingAs($tutorA)->post(route('tutor.homework.store'), [
        'title' => 'X', 'subject' => 'Mathematics', 'description' => 'Y',
        'student_id' => $studentB->id,
        'start_date' => now()->toDateString(), 'due_date' => now()->addDay()->toDateString(),
    ])->assertNotFound();

    // Quiz assignment to a non-owned student → validation error, nothing written.
    $quizA = Quiz::create([
        'tutor_id' => $tutorA->id, 'title' => 'A Quiz',
        'level' => 'Primary 4', 'subject' => 'Science', 'exam_type' => 'WA1',
    ]);
    $this->actingAs($tutorA)
        ->post(route('tutor.quizzes.assign', $quizA), ['student_ids' => [$studentB->id]])
        ->assertSessionHasErrors('student_ids.0');
    expect(QuizAssignment::count())->toBe(0);
});

it('lets a tutor view a submitted attempt on their own quiz but 404s on another tutor\'s', function () {
    $tutorA = tutor();
    $tutorB = tutor();
    $studentB = student(['tutor_id' => $tutorB->id, 'name' => 'Bee Student']);

    $quizB = Quiz::create([
        'tutor_id' => $tutorB->id, 'title' => 'B Quiz',
        'level' => 'Primary 4', 'subject' => 'Science', 'exam_type' => 'WA1',
    ]);
    $attemptB = QuizAttempt::create([
        'quiz_id' => $quizB->id, 'student_id' => $studentB->id,
        'total_marks' => 2, 'obtained_marks' => 2, 'completed_at' => now(),
    ]);

    // The owning tutor can see the student's answers.
    $this->actingAs($tutorB)
        ->get(route('tutor.quizzes.attempts.show', [$quizB, $attemptB]))
        ->assertOk()
        ->assertSee('Bee Student');

    // A different tutor cannot — neither via the quiz nor a smuggled attempt ID.
    $this->actingAs($tutorA)
        ->get(route('tutor.quizzes.attempts.show', [$quizB, $attemptB]))
        ->assertNotFound();
});

it('404s when an attempt ID from a different quiz is smuggled into the URL', function () {
    $tutor = tutor();
    $studentMine = student(['tutor_id' => $tutor->id]);

    $quizOne = Quiz::create([
        'tutor_id' => $tutor->id, 'title' => 'Quiz One',
        'level' => 'Primary 4', 'subject' => 'Science', 'exam_type' => 'WA1',
    ]);
    $quizTwo = Quiz::create([
        'tutor_id' => $tutor->id, 'title' => 'Quiz Two',
        'level' => 'Primary 4', 'subject' => 'Science', 'exam_type' => 'WA1',
    ]);
    $attemptOnTwo = QuizAttempt::create([
        'quiz_id' => $quizTwo->id, 'student_id' => $studentMine->id,
        'total_marks' => 1, 'obtained_marks' => 1, 'completed_at' => now(),
    ]);

    // Same tutor owns both, but the attempt doesn't belong to quizOne → 404.
    $this->actingAs($tutor)
        ->get(route('tutor.quizzes.attempts.show', [$quizOne, $attemptOnTwo]))
        ->assertNotFound();
});

it('sends quiz feedback to the student as an inbox message', function () {
    Notification::fake();
    $tutor = tutor();
    $studentMine = student(['tutor_id' => $tutor->id, 'name' => 'Mine Student']);

    $quiz = Quiz::create([
        'tutor_id' => $tutor->id, 'title' => 'Fractions Quiz',
        'level' => 'Primary 4', 'subject' => 'Mathematics', 'exam_type' => 'WA1',
    ]);
    $attempt = QuizAttempt::create([
        'quiz_id' => $quiz->id, 'student_id' => $studentMine->id,
        'total_marks' => 5, 'obtained_marks' => 3, 'completed_at' => now(),
    ]);

    $this->actingAs($tutor)
        ->post(route('tutor.quizzes.attempts.feedback', [$quiz, $attempt]), [
            'feedback' => 'Good effort — revisit Q3.',
        ])
        ->assertRedirect();

    expect($attempt->fresh()->feedback)->toBe('Good effort — revisit Q3.');
    $this->assertDatabaseHas('messages', [
        'sender_id'   => $tutor->id,
        'receiver_id' => $studentMine->id,
        'subject'     => 'Feedback on your quiz: Fractions Quiz',
        'body'        => 'Good effort — revisit Q3.',
    ]);
});

it('404s and writes no message when a tutor feeds back on another tutor\'s attempt', function () {
    $tutorA = tutor();
    $tutorB = tutor();
    $studentB = student(['tutor_id' => $tutorB->id]);

    $quizB = Quiz::create([
        'tutor_id' => $tutorB->id, 'title' => 'B Quiz',
        'level' => 'Primary 4', 'subject' => 'Science', 'exam_type' => 'WA1',
    ]);
    $attemptB = QuizAttempt::create([
        'quiz_id' => $quizB->id, 'student_id' => $studentB->id,
        'total_marks' => 2, 'obtained_marks' => 2, 'completed_at' => now(),
    ]);

    $this->actingAs($tutorA)
        ->post(route('tutor.quizzes.attempts.feedback', [$quizB, $attemptB]), [
            'feedback' => 'Sneaky cross-tenant feedback',
        ])
        ->assertNotFound();

    expect($attemptB->fresh()->feedback)->toBeNull();
    expect(\App\Models\Message::count())->toBe(0);
});

// ---- Short-answer grading isolation (Slice 12) -----------------------------

it('404s when a tutor opens or grades another tutor\'s attempt', function () {
    $tutorA = tutor();
    $tutorB = tutor();
    $studentB = student(['tutor_id' => $tutorB->id]);

    $quizB = Quiz::create([
        'tutor_id' => $tutorB->id, 'title' => 'B Quiz',
        'level' => 'Primary 4', 'subject' => 'Science', 'exam_type' => 'WA1',
    ]);
    $questionB = $quizB->questions()->create([
        'question_text' => 'Explain.', 'question_type' => 'short_answer',
        'correct_answer' => null, 'marks' => 3, 'order' => 0,
    ]);
    $attemptB = QuizAttempt::create([
        'quiz_id' => $quizB->id, 'student_id' => $studentB->id,
        'total_marks' => 3, 'obtained_marks' => 0, 'completed_at' => now(),
    ]);
    $answerB = QuizAnswer::create([
        'attempt_id' => $attemptB->id, 'question_id' => $questionB->id,
        'student_answer' => 'mine', 'is_correct' => false, 'marks_awarded' => 0,
    ]);

    // The other tutor can neither view the grading page nor submit grades.
    $this->actingAs($tutorA)
        ->get(route('tutor.quizzes.attempts.grade', [$quizB, $attemptB]))
        ->assertNotFound();

    $this->actingAs($tutorA)
        ->post(route('tutor.quizzes.attempts.grade.save', [$quizB, $attemptB]), [
            'grades' => [$answerB->id => 'correct'], 'marks' => [$answerB->id => 3],
        ])
        ->assertNotFound();

    // Nothing was graded.
    expect($attemptB->fresh()->graded_at)->toBeNull()
        ->and($answerB->fresh()->grade)->toBeNull();
});

it('404s when a student opens another student\'s feedback image', function () {
    $tutor = tutor();
    $studentA = student(['tutor_id' => $tutor->id]);
    $studentB = student(['tutor_id' => $tutor->id]);

    $quiz = Quiz::create([
        'tutor_id' => $tutor->id, 'title' => 'Q',
        'level' => 'Primary 4', 'subject' => 'Science', 'exam_type' => 'WA1',
    ]);
    $question = $quiz->questions()->create([
        'question_text' => 'Explain.', 'question_type' => 'short_answer',
        'correct_answer' => null, 'marks' => 3, 'order' => 0,
    ]);
    $attemptA = QuizAttempt::create([
        'quiz_id' => $quiz->id, 'student_id' => $studentA->id,
        'total_marks' => 3, 'obtained_marks' => 3, 'completed_at' => now(), 'graded_at' => now(),
    ]);
    $answerA = QuizAnswer::create([
        'attempt_id' => $attemptA->id, 'question_id' => $question->id,
        'student_answer' => 'a', 'grade' => 'correct', 'marks_awarded' => 3,
        'tutor_feedback_image_path' => 'quiz-feedback/secret.png',
        'tutor_feedback_image_name' => 'secret.png',
    ]);

    // Student B (not the owner of the attempt) cannot reach A's feedback image.
    $this->actingAs($studentB)
        ->get(route('student.quizzes.answers.feedback-image', $answerA))
        ->assertNotFound();
});

// ---- Resources: answer sheets (Slice 13) -----------------------------------

it('404s when a tutor opens, marks, or deletes another tutor\'s answer sheet', function () {
    $tutorA = tutor();
    $tutorB = tutor();
    $studentB = student(['tutor_id' => $tutorB->id]);

    $sheetB = \App\Models\AnswerSheet::create([
        'author_id' => $tutorB->id, 'tutor_id' => $tutorB->id, 'student_id' => $studentB->id,
        'type' => 'mcq', 'title' => 'B Sheet', 'subject' => 'Science', 'status' => 'submitted', 'total_marks' => 1,
    ]);
    $sheetB->questions()->create(['order' => 0, 'num_options' => 4, 'marks' => 1, 'choice' => 1]);

    $this->actingAs($tutorA)->get(route('tutor.resources.show', $sheetB))->assertNotFound();
    $this->actingAs($tutorA)->get(route('tutor.resources.mark', $sheetB))->assertNotFound();
    $this->actingAs($tutorA)->delete(route('tutor.resources.destroy', $sheetB))->assertNotFound();
    $this->actingAs($tutorA)
        ->post(route('tutor.resources.mark.save', $sheetB), ['grades' => [], 'marks' => []])
        ->assertNotFound();

    expect(\App\Models\AnswerSheet::find($sheetB->id))->not->toBeNull()
        ->and($sheetB->fresh()->status)->toBe('submitted');
});

it('shows a tutor only their own answer sheets', function () {
    $tutorA = tutor();
    $studentA = student(['tutor_id' => $tutorA->id]);
    $studentB = student(['tutor_id' => tutor()->id]);

    \App\Models\AnswerSheet::create([
        'author_id' => $tutorA->id, 'tutor_id' => $tutorA->id, 'student_id' => $studentA->id,
        'type' => 'mcq', 'title' => 'Mine Sheet', 'subject' => 'Science', 'status' => 'sent', 'total_marks' => 0,
    ]);
    \App\Models\AnswerSheet::create([
        'author_id' => $studentB->tutor_id, 'tutor_id' => $studentB->tutor_id, 'student_id' => $studentB->id,
        'type' => 'mcq', 'title' => 'Theirs Sheet', 'subject' => 'Science', 'status' => 'sent', 'total_marks' => 0,
    ]);

    $this->actingAs($tutorA)->get(route('tutor.resources.index', 'mcq'))
        ->assertOk()->assertSee('Mine Sheet')->assertDontSee('Theirs Sheet');
});

it('404s when a student opens or submits another student\'s sheet', function () {
    $tutor = tutor();
    $studentA = student(['tutor_id' => $tutor->id]);
    $studentB = student(['tutor_id' => $tutor->id]);

    $sheetA = \App\Models\AnswerSheet::create([
        'author_id' => $tutor->id, 'tutor_id' => $tutor->id, 'student_id' => $studentA->id,
        'type' => 'mcq', 'title' => 'A Sheet', 'subject' => 'Science', 'status' => 'sent', 'total_marks' => 1,
    ]);
    $qA = $sheetA->questions()->create(['order' => 0, 'num_options' => 4, 'marks' => 1]);

    // Student B cannot view or submit student A's sheet.
    $this->actingAs($studentB)->get(route('student.resources.show', $sheetA))->assertNotFound();
    $this->actingAs($studentB)
        ->post(route('student.resources.submit', $sheetA), ['answers' => [$qA->id => 1]])
        ->assertNotFound();

    expect($sheetA->fresh()->status)->toBe('sent');
});

// ---- Admin: tutor account management ---------------------------------------

it('lets only the super_admin reach tutor management', function () {
    $this->actingAs(tutor())->get(route('admin.tutors.index'))->assertForbidden();
    $this->actingAs(student())->get(route('admin.tutors.index'))->assertForbidden();
    $this->actingAs(superAdmin())->get(route('admin.tutors.index'))->assertOk();
});

it('lets the super_admin create a tutor account', function () {
    $this->actingAs(superAdmin())->post(route('admin.tutors.store'), [
        'name' => 'Friend Tutor',
        'email' => 'friend@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect(route('admin.tutors.index'));

    $this->assertDatabaseHas('users', [
        'email' => 'friend@example.com', 'role' => 'tutor', 'tutor_id' => null,
    ]);
});

it('blocks deleting a tutor who still has students, but allows it once empty', function () {
    $admin = superAdmin();

    $withRoster = tutor();
    student(['tutor_id' => $withRoster->id]);
    $this->actingAs($admin)
        ->delete(route('admin.tutors.destroy', $withRoster))
        ->assertSessionHasErrors('tutor');
    expect(User::find($withRoster->id))->not->toBeNull();

    $empty = tutor();
    $this->actingAs($admin)
        ->delete(route('admin.tutors.destroy', $empty))
        ->assertRedirect(route('admin.tutors.index'));
    expect(User::find($empty->id))->toBeNull();
});

// ---- Exam papers: shared, moderated library --------------------------------

it('queues a regular tutor\'s upload as pending', function () {
    Storage::fake('r2');

    $this->actingAs(tutor())->post(route('tutor.exam-papers.store'), [
        'title' => 'Tutor Submission', 'level' => 'Primary 6',
        'subject' => 'Mathematics', 'year' => 2024,
        'file' => UploadedFile::fake()->create('p.pdf', 100, 'application/pdf'),
    ])->assertSessionHas('status', 'Sent to super admin for approval.');

    expect(ExamPaper::firstWhere('title', 'Tutor Submission')->status)->toBe('pending');
});

it('publishes a super_admin upload immediately', function () {
    Storage::fake('r2');

    $this->actingAs(superAdmin())->post(route('tutor.exam-papers.store'), [
        'title' => 'Admin Upload', 'level' => 'Primary 6',
        'subject' => 'Mathematics', 'year' => 2024,
        'file' => UploadedFile::fake()->create('p.pdf', 100, 'application/pdf'),
    ])->assertSessionHas('status', 'Exam paper uploaded.');

    expect(ExamPaper::firstWhere('title', 'Admin Upload')->status)->toBe('approved');
});

it('shows students only approved papers', function () {
    ExamPaper::create([
        'tutor_id' => tutor()->id, 'level' => 'Primary 6', 'title' => 'Approved Paper',
        'subject' => 'Mathematics', 'year' => 2023, 'file_path' => 'x.pdf',
        'original_filename' => 'a.pdf', 'status' => 'approved',
    ]);
    ExamPaper::create([
        'tutor_id' => tutor()->id, 'level' => 'Primary 6', 'title' => 'Pending Paper',
        'subject' => 'Mathematics', 'year' => 2023, 'file_path' => 'y.pdf',
        'original_filename' => 'p.pdf', 'status' => 'pending',
    ]);

    $this->actingAs(student())
        ->get(route('student.exam-papers.index'))
        ->assertOk()
        ->assertSee('Approved Paper')
        ->assertDontSee('Pending Paper');
});

it('approves a submission and notifies the uploader', function () {
    Notification::fake();
    $admin = superAdmin();
    $uploader = tutor();

    $paper = ExamPaper::create([
        'tutor_id' => $uploader->id, 'level' => 'Primary 6', 'title' => 'Pending One',
        'subject' => 'Mathematics', 'year' => 2024, 'file_path' => 'z.pdf',
        'original_filename' => 'z.pdf', 'status' => 'pending',
    ]);

    $this->actingAs($admin)->post(route('tutor.exam-papers.approve', $paper))->assertRedirect();

    expect($paper->fresh()->status)->toBe('approved')
        ->and($paper->fresh()->approved_by)->toBe($admin->id);
    $this->assertDatabaseHas('messages', [
        'sender_id' => $admin->id, 'receiver_id' => $uploader->id, 'subject' => 'Exam paper approved',
    ]);
});

it('rejects a submission, deletes the file, and notifies the uploader', function () {
    Notification::fake();
    Storage::fake('r2');
    Storage::disk('r2')->put('exam-papers/reject-me.pdf', 'content');

    $admin = superAdmin();
    $uploader = tutor();
    $paper = ExamPaper::create([
        'tutor_id' => $uploader->id, 'level' => 'Primary 6', 'title' => 'Reject One',
        'subject' => 'Mathematics', 'year' => 2024, 'file_path' => 'exam-papers/reject-me.pdf',
        'original_filename' => 'r.pdf', 'status' => 'pending',
    ]);

    $this->actingAs($admin)->post(route('tutor.exam-papers.reject', $paper))->assertRedirect();

    expect(ExamPaper::find($paper->id))->toBeNull();
    Storage::disk('r2')->assertMissing('exam-papers/reject-me.pdf');
    $this->assertDatabaseHas('messages', [
        'receiver_id' => $uploader->id, 'subject' => 'Exam paper not approved',
    ]);
});

it('forbids a regular tutor from approving submissions', function () {
    $paper = ExamPaper::create([
        'tutor_id' => tutor()->id, 'level' => 'Primary 6', 'title' => 'Pending Two',
        'subject' => 'Mathematics', 'year' => 2024, 'file_path' => 'z.pdf',
        'original_filename' => 'z.pdf', 'status' => 'pending',
    ]);

    $this->actingAs(tutor())->post(route('tutor.exam-papers.approve', $paper))->assertForbidden();
    expect($paper->fresh()->status)->toBe('pending');
});
