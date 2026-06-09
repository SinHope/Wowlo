<?php

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;

/**
 * Slice 8 Part B: student takes assigned quizzes, auto-marking, isolation, corrections.
 */

function makeQuiz(User $tutor): Quiz
{
    $quiz = Quiz::create([
        'tutor_id' => $tutor->id, 'title' => 'P4 Science Quiz', 'level' => 'Primary 4',
        'subject' => 'Science', 'exam_type' => 'WA1',
    ]);

    $quiz->questions()->create([
        'question_text' => 'Q1', 'option_a' => 'a', 'option_b' => 'b',
        'option_c' => 'c', 'option_d' => 'd', 'correct_answer' => 'A', 'marks' => 2, 'order' => 0,
    ]);
    $quiz->questions()->create([
        'question_text' => 'Q2', 'option_a' => 'a', 'option_b' => 'b',
        'option_c' => 'c', 'option_d' => 'd', 'correct_answer' => 'C', 'marks' => 3, 'order' => 1,
    ]);

    return $quiz;
}

function assignTo(Quiz $quiz, User $student): void
{
    $quiz->assignments()->create(['student_id' => $student->id, 'assigned_at' => now()]);
}

it('forbids a tutor from the student quiz area', function () {
    $this->actingAs(tutor())
        ->get(route('student.quizzes.index'))
        ->assertForbidden();
});

it('only lists quizzes assigned to the student', function () {
    $tutor = tutor();
    $student = student();
    $mine = makeQuiz($tutor);
    $other = makeQuiz($tutor);
    assignTo($mine, $student);

    $this->actingAs($student)
        ->get(route('student.quizzes.index'))
        ->assertOk()
        ->assertSee('P4 Science Quiz');

    expect($mine->assignments()->where('student_id', $student->id)->exists())->toBeTrue()
        ->and($other->assignments()->where('student_id', $student->id)->exists())->toBeFalse();
});

it('forbids taking a quiz that is not assigned', function () {
    $quiz = makeQuiz(tutor());

    $this->actingAs(student())
        ->get(route('student.quizzes.show', $quiz))
        ->assertForbidden();
});

it('auto-marks a submission correctly', function () {
    $tutor = tutor();
    $student = student();
    $quiz = makeQuiz($tutor);
    assignTo($quiz, $student);

    [$q1, $q2] = $quiz->questions->all();

    $this->actingAs($student)
        ->post(route('student.quizzes.submit', $quiz), [
            'answers' => [$q1->id => 'A', $q2->id => 'B'], // Q1 right (2), Q2 wrong (0)
        ])
        ->assertRedirect(route('student.quizzes.result', $quiz));

    $attempt = QuizAttempt::where('quiz_id', $quiz->id)->where('student_id', $student->id)->first();

    expect($attempt->total_marks)->toBe(5)
        ->and($attempt->obtained_marks)->toBe(2.0) // marks are fractional-capable now
        ->and($attempt->completed_at)->not->toBeNull()
        ->and($attempt->answers)->toHaveCount(2);

    $a1 = $attempt->answers->firstWhere('question_id', $q1->id);
    expect($a1->is_correct)->toBeTrue()->and($a1->marks_awarded)->toBe(2.0);
});

it('awards full marks for all-correct', function () {
    $tutor = tutor();
    $student = student();
    $quiz = makeQuiz($tutor);
    assignTo($quiz, $student);
    [$q1, $q2] = $quiz->questions->all();

    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$q1->id => 'A', $q2->id => 'C'],
    ]);

    $attempt = QuizAttempt::firstWhere('quiz_id', $quiz->id);
    expect($attempt->obtained_marks)->toBe(5.0);
});

it('treats an unanswered question as wrong', function () {
    $tutor = tutor();
    $student = student();
    $quiz = makeQuiz($tutor);
    assignTo($quiz, $student);
    [$q1, $q2] = $quiz->questions->all();

    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$q1->id => 'A'], // Q2 omitted
    ]);

    $attempt = QuizAttempt::firstWhere('quiz_id', $quiz->id);
    expect($attempt->obtained_marks)->toBe(2.0);
    $a2 = $attempt->answers->firstWhere('question_id', $q2->id);
    expect($a2->student_answer)->toBeNull()->and($a2->is_correct)->toBeFalse();
});

it('redirects to results and blocks a re-submit once completed', function () {
    $tutor = tutor();
    $student = student();
    $quiz = makeQuiz($tutor);
    assignTo($quiz, $student);
    [$q1, $q2] = $quiz->questions->all();

    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$q1->id => 'A', $q2->id => 'C'],
    ]);

    // Taking the quiz again redirects to the result.
    $this->actingAs($student)
        ->get(route('student.quizzes.show', $quiz))
        ->assertRedirect(route('student.quizzes.result', $quiz));

    // A second submit attempt does not change the score.
    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$q1->id => 'B', $q2->id => 'B'],
    ])->assertRedirect(route('student.quizzes.result', $quiz));

    expect(QuizAttempt::where('quiz_id', $quiz->id)->count())->toBe(1)
        ->and(QuizAttempt::firstWhere('quiz_id', $quiz->id)->obtained_marks)->toBe(5.0);
});

it('cannot view another student\'s result', function () {
    $tutor = tutor();
    $a = student();
    $b = student();
    $quiz = makeQuiz($tutor);
    assignTo($quiz, $a);
    assignTo($quiz, $b);
    [$q1, $q2] = $quiz->questions->all();

    $this->actingAs($a)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$q1->id => 'A', $q2->id => 'C'],
    ]);

    // Student B has no attempt → result page bounces them to take the quiz.
    $this->actingAs($b)
        ->get(route('student.quizzes.result', $quiz))
        ->assertRedirect(route('student.quizzes.show', $quiz));
});

it('lets a student save corrections for wrong answers', function () {
    $tutor = tutor();
    $student = student();
    $quiz = makeQuiz($tutor);
    assignTo($quiz, $student);
    [$q1, $q2] = $quiz->questions->all();

    $this->actingAs($student)->post(route('student.quizzes.submit', $quiz), [
        'answers' => [$q1->id => 'A', $q2->id => 'B'], // Q2 wrong
    ]);

    $attempt = QuizAttempt::firstWhere('quiz_id', $quiz->id);
    $wrong = $attempt->answers->firstWhere('question_id', $q2->id);

    $this->actingAs($student)->post(route('student.quizzes.corrections', $quiz), [
        'corrections' => [$wrong->id => 'The answer is C because c is correct.'],
    ])->assertRedirect();

    expect($wrong->fresh()->correction)->toBe('The answer is C because c is correct.');
});
