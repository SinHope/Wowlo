<?php

use App\Models\Quiz;
use App\Models\QuizAssignment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Slice 8 Part A: tutor creates MCQ quizzes, assigns them to students.
 */

function quizPayload(array $overrides = []): array
{
    return array_merge([
        'title'     => 'P4 Science WA1 Chapter 3',
        'level'     => 'Primary 4',
        'subject'   => 'Science',
        'topic'     => 'Photosynthesis',
        'exam_type' => 'WA1',
        'questions' => [
            [
                'question_text'  => 'What do plants need to make food?',
                'option_a'       => 'Sunlight',
                'option_b'       => 'Darkness',
                'option_c'       => 'Cold',
                'option_d'       => 'Noise',
                'correct_answer' => 'A',
                'marks'          => 2,
            ],
            [
                'question_text'  => 'Which gas do plants absorb?',
                'option_a'       => 'Oxygen',
                'option_b'       => 'Carbon dioxide',
                'option_c'       => 'Helium',
                'option_d'       => 'Nitrogen',
                'correct_answer' => 'B',
                'marks'          => 3,
            ],
        ],
    ], $overrides);
}

it('forbids a student from the tutor quiz area', function () {
    $this->actingAs(student())
        ->get(route('tutor.quizzes.index'))
        ->assertForbidden();
});

it('lets a tutor create a quiz with questions', function () {
    $tutor = tutor();

    $this->actingAs($tutor)
        ->post(route('tutor.quizzes.store'), quizPayload())
        ->assertRedirect();

    $quiz = Quiz::with('questions')->firstWhere('title', 'P4 Science WA1 Chapter 3');

    expect($quiz)->not->toBeNull()
        ->and($quiz->tutor_id)->toBe($tutor->id)
        ->and($quiz->level)->toBe('Primary 4')
        ->and($quiz->exam_type)->toBe('WA1')
        ->and($quiz->questions)->toHaveCount(2)
        ->and($quiz->questions[0]->correct_answer)->toBe('A')
        ->and($quiz->questions[0]->order)->toBe(0)
        ->and($quiz->questions[1]->order)->toBe(1)
        ->and($quiz->totalMarks())->toBe(5);
});

it('requires at least one question', function () {
    $this->actingAs(tutor())
        ->post(route('tutor.quizzes.store'), quizPayload(['questions' => []]))
        ->assertSessionHasErrors('questions');

    expect(Quiz::count())->toBe(0);
});

it('rejects an invalid correct_answer', function () {
    $payload = quizPayload();
    $payload['questions'][0]['correct_answer'] = 'E';

    $this->actingAs(tutor())
        ->post(route('tutor.quizzes.store'), $payload)
        ->assertSessionHasErrors('questions.0.correct_answer');

    expect(Quiz::count())->toBe(0);
});

it('rejects an invalid exam type', function () {
    $this->actingAs(tutor())
        ->post(route('tutor.quizzes.store'), quizPayload(['exam_type' => 'FinalBoss']))
        ->assertSessionHasErrors('exam_type');
});

it('accepts the newly added exam types', function () {
    foreach (['Quiz', 'PeriodicAssessment', 'TopicEvaluation'] as $type) {
        $this->actingAs(tutor())
            ->post(route('tutor.quizzes.store'), quizPayload(['title' => "T-$type", 'exam_type' => $type]))
            ->assertRedirect();
        expect(Quiz::firstWhere('title', "T-$type")->exam_type)->toBe($type);
    }
});

it('stores a per-question diagram on R2 and links it', function () {
    Storage::fake('r2');
    $tutor = tutor();

    $payload = quizPayload();
    $payload['questions'][0]['image'] = UploadedFile::fake()->create('diagram.png', 200, 'image/png');

    $this->actingAs($tutor)
        ->post(route('tutor.quizzes.store'), $payload)
        ->assertRedirect();

    $question = Quiz::firstWhere('title', 'P4 Science WA1 Chapter 3')->questions()->first();

    expect($question->image_path)->not->toBeNull()
        ->and($question->image_name)->toBe('diagram.png')
        ->and($question->hasImage())->toBeTrue()
        ->and($question->imageIsPreviewable())->toBeTrue();
    Storage::disk('r2')->assertExists($question->image_path);
});

it('rejects a disallowed attachment type', function () {
    Storage::fake('r2');

    $payload = quizPayload();
    $payload['questions'][0]['image'] = UploadedFile::fake()->create('virus.exe', 50);

    $this->actingAs(tutor())
        ->post(route('tutor.quizzes.store'), $payload)
        ->assertSessionHasErrors('questions.0.image');

    expect(Quiz::count())->toBe(0);
});

it('deletes question attachments from R2 when the quiz is deleted', function () {
    Storage::fake('r2');
    $tutor = tutor();

    $payload = quizPayload();
    $payload['questions'][0]['image'] = UploadedFile::fake()->create('diagram.png', 200, 'image/png');
    $this->actingAs($tutor)->post(route('tutor.quizzes.store'), $payload);

    $quiz = Quiz::firstWhere('title', 'P4 Science WA1 Chapter 3');
    $key = $quiz->questions()->first()->image_path;
    Storage::disk('r2')->assertExists($key);

    $this->actingAs($tutor)->delete(route('tutor.quizzes.destroy', $quiz));

    Storage::disk('r2')->assertMissing($key);
});

it('lets a tutor assign a quiz to students and re-sync the set', function () {
    $tutor = tutor();
    $a = student();
    $b = student();

    $quiz = Quiz::create([
        'tutor_id' => $tutor->id, 'title' => 'Q', 'level' => 'Primary 4',
        'subject' => 'Science', 'exam_type' => 'WA1',
    ]);

    // Assign to both.
    $this->actingAs($tutor)
        ->post(route('tutor.quizzes.assign', $quiz), ['student_ids' => [$a->id, $b->id]])
        ->assertRedirect();
    expect(QuizAssignment::where('quiz_id', $quiz->id)->count())->toBe(2);

    // Re-sync to just one — the other should be removed.
    $this->actingAs($tutor)
        ->post(route('tutor.quizzes.assign', $quiz), ['student_ids' => [$a->id]]);
    expect(QuizAssignment::where('quiz_id', $quiz->id)->pluck('student_id')->all())->toBe([$a->id]);

    // Empty set clears all assignments.
    $this->actingAs($tutor)
        ->post(route('tutor.quizzes.assign', $quiz), ['student_ids' => []]);
    expect(QuizAssignment::where('quiz_id', $quiz->id)->count())->toBe(0);
});

it('does not duplicate an assignment when assigned twice', function () {
    $tutor = tutor();
    $s = student();
    $quiz = Quiz::create([
        'tutor_id' => $tutor->id, 'title' => 'Q', 'level' => 'Primary 4',
        'subject' => 'Science', 'exam_type' => 'WA1',
    ]);

    $this->actingAs($tutor)->post(route('tutor.quizzes.assign', $quiz), ['student_ids' => [$s->id]]);
    $this->actingAs($tutor)->post(route('tutor.quizzes.assign', $quiz), ['student_ids' => [$s->id]]);

    expect(QuizAssignment::where('quiz_id', $quiz->id)->count())->toBe(1);
});

it('cascade-deletes questions and assignments when a quiz is deleted', function () {
    $tutor = tutor();
    $s = student();

    $this->actingAs($tutor)->post(route('tutor.quizzes.store'), quizPayload());
    $quiz = Quiz::firstWhere('title', 'P4 Science WA1 Chapter 3');
    $this->actingAs($tutor)->post(route('tutor.quizzes.assign', $quiz), ['student_ids' => [$s->id]]);

    $this->actingAs($tutor)->delete(route('tutor.quizzes.destroy', $quiz))->assertRedirect();

    expect(Quiz::count())->toBe(0)
        ->and(\App\Models\QuizQuestion::count())->toBe(0)
        ->and(QuizAssignment::count())->toBe(0);
});
