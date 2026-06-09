<?php

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

/**
 * Slice 12: short-answer questions + manual grading.
 * See docs/short-answer-quizzes.md §9 for the required coverage.
 */

// A quiz with one auto-marked MCQ (2 marks) and one short answer (3 marks).
function makeMixedQuiz(User $tutor): Quiz
{
    $quiz = Quiz::create([
        'tutor_id' => $tutor->id, 'title' => 'Mixed Quiz', 'level' => 'Primary 4',
        'subject' => 'Science', 'exam_type' => 'WA1',
    ]);

    $quiz->questions()->create([
        'question_text' => 'Which gas?', 'question_type' => 'mcq',
        'option_a' => 'CO2', 'option_b' => 'He', 'option_c' => 'Ne', 'option_d' => 'Ar',
        'correct_answer' => 'A', 'marks' => 2, 'order' => 0,
    ]);
    $quiz->questions()->create([
        'question_text' => 'Explain photosynthesis.', 'question_type' => 'short_answer',
        'correct_answer' => null, 'marks' => 3, 'order' => 1,
    ]);

    return $quiz;
}

function assignQuiz(Quiz $quiz, User $student): void
{
    $quiz->assignments()->create(['student_id' => $student->id, 'assigned_at' => now()]);
}

// ---- Creation --------------------------------------------------------------

it('lets a tutor create a quiz mixing MCQ and short-answer questions', function () {
    $tutor = tutor();

    $this->actingAs($tutor)->post(route('tutor.quizzes.store'), [
        'title' => 'Mix', 'level' => 'Primary 4', 'subject' => 'Science', 'exam_type' => 'WA1',
        'questions' => [
            [
                'question_type' => 'mcq', 'question_text' => 'Q1',
                'option_a' => 'a', 'option_b' => 'b', 'option_c' => 'c', 'option_d' => 'd',
                'correct_answer' => 'A', 'marks' => 2,
            ],
            [
                'question_type' => 'short_answer', 'question_text' => 'Explain.', 'marks' => 5,
            ],
        ],
    ])->assertRedirect();

    $quiz = Quiz::with('questions')->firstWhere('title', 'Mix');
    $short = $quiz->questions->firstWhere('question_type', 'short_answer');

    expect($quiz)->not->toBeNull();

    // Regression: after saving, land on the quiz's show page (not bounced to create).
    $this->actingAs($tutor)->post(route('tutor.quizzes.store'), [
        'title' => 'Mix Redirect', 'level' => 'Primary 4', 'subject' => 'Science', 'exam_type' => 'WA1',
        'questions' => [
            ['question_type' => 'short_answer', 'question_text' => 'Explain.', 'marks' => 2],
            [
                'question_type' => 'mcq', 'question_text' => 'Q2',
                'option_a' => 'a', 'option_b' => 'b', 'option_c' => 'c', 'option_d' => 'd',
                'correct_answer' => 'A', 'marks' => 1,
            ],
        ],
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('tutor.quizzes.show', Quiz::firstWhere('title', 'Mix Redirect')));

    expect($quiz->questions)->toHaveCount(2)
        ->and($short->marks)->toBe(5)
        ->and($short->correct_answer)->toBeNull()
        ->and($short->option_a)->toBeNull()
        ->and($quiz->hasShortAnswers())->toBeTrue();
});

it('still requires options + a correct answer for MCQ questions', function () {
    $this->actingAs(tutor())->post(route('tutor.quizzes.store'), [
        'title' => 'Bad', 'level' => 'Primary 4', 'subject' => 'Science', 'exam_type' => 'WA1',
        'questions' => [
            ['question_type' => 'mcq', 'question_text' => 'Q1', 'marks' => 2], // no options/correct
        ],
    ])->assertSessionHasErrors(['questions.0.option_a', 'questions.0.correct_answer']);

    expect(Quiz::count())->toBe(0);
});

// ---- Submission (awaiting marking) -----------------------------------------

it('marks a short-answer submission as awaiting and leaks no result', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $quiz = makeMixedQuiz($tutor);
    assignQuiz($quiz, $student);
    [$mcq, $short] = $quiz->questions->all();

    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$mcq->id => 'A', $short->id => 'Plants use sunlight to make food.'],
    ])->assertRedirect(route('student.quizzes.result', $quiz));

    $attempt = QuizAttempt::firstWhere('quiz_id', $quiz->id);

    expect($attempt->completed_at)->not->toBeNull()
        ->and($attempt->graded_at)->toBeNull()         // awaiting the tutor
        ->and($attempt->isGraded())->toBeFalse()
        ->and($attempt->needsMarking())->toBeTrue()
        ->and($attempt->obtained_marks)->toBe(2.0);     // MCQ auto only, not revealed yet

    $shortAns = $attempt->answers->firstWhere('question_id', $short->id);
    expect($shortAns->student_answer)->toBe('Plants use sunlight to make food.')
        ->and($shortAns->grade)->toBeNull()
        ->and($shortAns->marks_awarded)->toBe(0.0);

    // The result page shows the awaiting state, not a score or corrections.
    $this->actingAs($student)->get(route('student.quizzes.result', $quiz))
        ->assertOk()
        ->assertSee('Awaiting your tutor') // apostrophe is HTML-escaped in the page
        ->assertDontSee('Save Corrections');
});

// ---- Grading ---------------------------------------------------------------

it('lets the owning tutor grade short answers and recomputes the score', function () {
    Notification::fake();
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id, 'name' => 'Sam']);
    $quiz = makeMixedQuiz($tutor);
    assignQuiz($quiz, $student);
    [$mcq, $short] = $quiz->questions->all();

    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$mcq->id => 'A', $short->id => 'Half right.'],
    ]);

    $attempt = QuizAttempt::firstWhere('quiz_id', $quiz->id);
    $shortAns = $attempt->answers->firstWhere('question_id', $short->id);

    // The grading page renders the student's submitted answer.
    $this->actingAs($tutor)->get(route('tutor.quizzes.attempts.grade', [$quiz, $attempt]))
        ->assertOk()
        ->assertSee('Half right.');

    $this->actingAs($tutor)->post(route('tutor.quizzes.attempts.grade.save', [$quiz, $attempt]), [
        'grades' => [$shortAns->id => 'partial'],
        'marks'  => [$shortAns->id => 2],
        'remarks' => 'Good start — add the role of chlorophyll.',
    ])->assertRedirect(route('tutor.quizzes.show', $quiz));

    $attempt->refresh();
    $shortAns->refresh();

    expect($shortAns->grade)->toBe('partial')
        ->and($shortAns->marks_awarded)->toBe(2.0)
        ->and($attempt->obtained_marks)->toBe(4.0)      // 2 MCQ + 2 short
        ->and($attempt->total_marks)->toBe(5)
        ->and($attempt->percentage())->toBe(80)
        ->and($attempt->graded_at)->not->toBeNull()
        ->and($attempt->isGraded())->toBeTrue()
        ->and($attempt->feedback)->toBe('Good start — add the role of chlorophyll.');

    // Student now sees the marks, %, and the manual grade.
    $this->actingAs($student)->get(route('student.quizzes.result', $quiz))
        ->assertOk()
        ->assertSee('4')
        ->assertSee('80%')
        ->assertSee('Partially correct');

    // Grading drops a feedback message into the student's inbox.
    $this->assertDatabaseHas('messages', [
        'sender_id' => $tutor->id, 'receiver_id' => $student->id,
        'subject' => 'Feedback on your quiz: Mixed Quiz',
    ]);
});

it('rejects marks above the question total or a missing grade', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $quiz = makeMixedQuiz($tutor);
    assignQuiz($quiz, $student);
    [$mcq, $short] = $quiz->questions->all();

    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$mcq->id => 'A', $short->id => 'x'],
    ]);
    $attempt = QuizAttempt::firstWhere('quiz_id', $quiz->id);
    $shortAns = $attempt->answers->firstWhere('question_id', $short->id);

    // 4 > the 3-mark max.
    $this->actingAs($tutor)->post(route('tutor.quizzes.attempts.grade.save', [$quiz, $attempt]), [
        'grades' => [$shortAns->id => 'correct'], 'marks' => [$shortAns->id => 4],
    ])->assertSessionHasErrors("marks.{$shortAns->id}");

    // No grade chosen.
    $this->actingAs($tutor)->post(route('tutor.quizzes.attempts.grade.save', [$quiz, $attempt]), [
        'marks' => [$shortAns->id => 1],
    ])->assertSessionHasErrors("grades.{$shortAns->id}");

    expect($attempt->fresh()->graded_at)->toBeNull();
});

it('stores a per-question feedback image on R2 when grading', function () {
    Storage::fake('r2');
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $quiz = makeMixedQuiz($tutor);
    assignQuiz($quiz, $student);
    [$mcq, $short] = $quiz->questions->all();

    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$mcq->id => 'A', $short->id => 'answer'],
    ]);
    $attempt = QuizAttempt::firstWhere('quiz_id', $quiz->id);
    $shortAns = $attempt->answers->firstWhere('question_id', $short->id);

    $this->actingAs($tutor)->post(route('tutor.quizzes.attempts.grade.save', [$quiz, $attempt]), [
        'grades' => [$shortAns->id => 'correct'], 'marks' => [$shortAns->id => 3],
        'feedback_image' => [$shortAns->id => UploadedFile::fake()->create('working.png', 100, 'image/png')],
    ])->assertRedirect();

    $shortAns->refresh();
    expect($shortAns->tutor_feedback_image_path)->not->toBeNull()
        ->and($shortAns->tutor_feedback_image_name)->toBe('working.png');
    Storage::disk('r2')->assertExists($shortAns->tutor_feedback_image_path);

    // The owning tutor can stream it.
    $this->actingAs($tutor)->get(route('tutor.quizzes.answers.feedback-image', $shortAns))->assertOk();
});

// ---- Corrections -----------------------------------------------------------

it('records a correction on a partial answer without changing the marks', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $quiz = makeMixedQuiz($tutor);
    assignQuiz($quiz, $student);
    [$mcq, $short] = $quiz->questions->all();

    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$mcq->id => 'A', $short->id => 'partial answer'],
    ]);
    $attempt = QuizAttempt::firstWhere('quiz_id', $quiz->id);
    $shortAns = $attempt->answers->firstWhere('question_id', $short->id);

    $this->actingAs($tutor)->post(route('tutor.quizzes.attempts.grade.save', [$quiz, $attempt]), [
        'grades' => [$shortAns->id => 'partial'], 'marks' => [$shortAns->id => 1],
    ]);

    expect($shortAns->fresh()->needsCorrection())->toBeTrue();

    $this->actingAs($student)->post(route('student.quizzes.corrections', $quiz), [
        'corrections' => [$shortAns->id => 'The full answer mentions chlorophyll and CO2.'],
    ])->assertRedirect();

    $shortAns->refresh();
    expect($shortAns->correction)->toBe('The full answer mentions chlorophyll and CO2.')
        ->and($shortAns->marks_awarded)->toBe(1.0)      // unchanged — corrections never re-grade
        ->and($attempt->fresh()->obtained_marks)->toBe(3.0);
});

// ---- Regression + helpers --------------------------------------------------

it('still auto-grades an MCQ-only quiz instantly (graded_at set on submit)', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);

    $quiz = Quiz::create([
        'tutor_id' => $tutor->id, 'title' => 'MCQ only', 'level' => 'Primary 4',
        'subject' => 'Science', 'exam_type' => 'WA1',
    ]);
    $q = $quiz->questions()->create([
        'question_text' => 'Q', 'question_type' => 'mcq',
        'option_a' => 'a', 'option_b' => 'b', 'option_c' => 'c', 'option_d' => 'd',
        'correct_answer' => 'A', 'marks' => 1, 'order' => 0,
    ]);
    assignQuiz($quiz, $student);

    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$q->id => 'A'],
    ]);

    $attempt = QuizAttempt::firstWhere('quiz_id', $quiz->id);
    expect($attempt->graded_at)->not->toBeNull()
        ->and($attempt->needsMarking())->toBeFalse()
        ->and($attempt->isGraded())->toBeTrue();
});

it('computes percentage with a divide-by-zero guard', function () {
    expect((new QuizAttempt(['total_marks' => 10, 'obtained_marks' => 8]))->percentage())->toBe(80)
        ->and((new QuizAttempt(['total_marks' => 0, 'obtained_marks' => 0]))->percentage())->toBe(0);
});

// ---- Half-marks ------------------------------------------------------------

it('awards half-marks on a short answer and recomputes the total', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $quiz = makeMixedQuiz($tutor);
    assignQuiz($quiz, $student);
    [$mcq, $short] = $quiz->questions->all();

    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$mcq->id => 'A', $short->id => 'partial'],
    ]);
    $attempt = QuizAttempt::firstWhere('quiz_id', $quiz->id);
    $shortAns = $attempt->answers->firstWhere('question_id', $short->id);

    $this->actingAs($tutor)->post(route('tutor.quizzes.attempts.grade.save', [$quiz, $attempt]), [
        'grades' => [$shortAns->id => 'partial'], 'marks' => [$shortAns->id => 1.5],
    ])->assertRedirect();

    expect($shortAns->fresh()->marks_awarded)->toBe(1.5)
        ->and($attempt->fresh()->obtained_marks)->toBe(3.5)   // 2 (MCQ) + 1.5 (short)
        ->and($attempt->fresh()->percentage())->toBe(70);     // 3.5 / 5
});

it('rejects marks that are not a whole number or a half', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $quiz = makeMixedQuiz($tutor);
    assignQuiz($quiz, $student);
    [$mcq, $short] = $quiz->questions->all();

    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$mcq->id => 'A', $short->id => 'x'],
    ]);
    $attempt = QuizAttempt::firstWhere('quiz_id', $quiz->id);
    $shortAns = $attempt->answers->firstWhere('question_id', $short->id);

    $this->actingAs($tutor)->post(route('tutor.quizzes.attempts.grade.save', [$quiz, $attempt]), [
        'grades' => [$shortAns->id => 'partial'], 'marks' => [$shortAns->id => 0.25],
    ])->assertSessionHasErrors("marks.{$shortAns->id}");
});

// ---- Pending-quiz visibility (dashboards + tutor index) --------------------

it('shows the tutor how many quizzes await marking', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $quiz = makeMixedQuiz($tutor);
    assignQuiz($quiz, $student);
    [$mcq, $short] = $quiz->questions->all();

    // Nothing submitted yet → nothing to mark.
    $this->actingAs($tutor)->get(route('dashboard'))->assertViewHas('quizzesToMark', 0);

    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$mcq->id => 'A', $short->id => 'ans'],
    ]);

    // Now one awaits marking — on the dashboard and the quiz list.
    $this->actingAs($tutor)->get(route('dashboard'))->assertOk()->assertViewHas('quizzesToMark', 1);
    $this->actingAs($tutor)->get(route('tutor.quizzes.index'))->assertOk()->assertSee('pending for marking');
});

it('shows the student their pending (unsubmitted) quizzes', function () {
    $tutor = tutor();
    $student = student(['tutor_id' => $tutor->id]);
    $quiz = makeMixedQuiz($tutor);
    assignQuiz($quiz, $student);

    $this->actingAs($student)->get(route('dashboard'))->assertOk()->assertViewHas('pendingQuizCount', 1);

    [$mcq, $short] = $quiz->questions->all();
    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$mcq->id => 'A', $short->id => 'ans'],
    ]);

    // Submitted → no longer counted as pending (even though marking isn't done).
    $this->actingAs($student)->get(route('dashboard'))->assertViewHas('pendingQuizCount', 0);
});
