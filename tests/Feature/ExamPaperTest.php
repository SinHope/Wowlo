<?php

use App\Models\ExamPaper;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function makePaper(array $attrs = []): ExamPaper
{
    return ExamPaper::create(array_merge([
        'tutor_id'          => tutor()->id,
        'level'             => 'Primary 6',
        'title'             => '2023 PSLE Maths Paper 1',
        'subject'           => 'Mathematics',
        'year'              => 2023,
        'file_path'         => 'exam-papers/fake.pdf',
        'original_filename' => 'maths_paper1_2023.pdf',
    ], $attrs));
}

it('forbids a student from the tutor exam paper area', function () {
    $this->actingAs(student())
        ->get(route('tutor.exam-papers.index'))
        ->assertForbidden();
});

it('forbids a tutor from the student exam paper area', function () {
    $this->actingAs(tutor())
        ->get(route('student.exam-papers.index'))
        ->assertForbidden();
});

it('lets a tutor upload an exam paper to R2', function () {
    Storage::fake('r2');
    $tutor = tutor();

    $this->actingAs($tutor)->post(route('tutor.exam-papers.store'), [
        'title'   => '2022 PSLE Science',
        'level'   => 'Primary 6',
        'subject' => 'Science',
        'year'    => 2022,
        'file'    => UploadedFile::fake()->create('science_2022.pdf', 500, 'application/pdf'),
    ])->assertRedirect(route('tutor.exam-papers.index'));

    $paper = ExamPaper::firstWhere('title', '2022 PSLE Science');
    expect($paper)->not->toBeNull()
        ->and($paper->level)->toBe('Primary 6')
        ->and($paper->subject)->toBe('Science')
        ->and($paper->year)->toBe(2022)
        ->and($paper->tutor_id)->toBe($tutor->id)
        ->and($paper->original_filename)->toBe('science_2022.pdf');
    Storage::disk('r2')->assertExists($paper->file_path);
});

it('validates required fields on upload', function () {
    Storage::fake('r2');

    $this->actingAs(tutor())->post(route('tutor.exam-papers.store'), [
        'title' => 'Missing level, year and file',
        'subject' => 'Mathematics',
    ])->assertSessionHasErrors(['level', 'year', 'file']);
});

it('rejects a level not in the canonical list', function () {
    Storage::fake('r2');

    $this->actingAs(tutor())->post(route('tutor.exam-papers.store'), [
        'title'   => 'Bad level',
        'level'   => 'Junior College 1',
        'subject' => 'Mathematics',
        'year'    => 2024,
        'file'    => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('level');

    expect(ExamPaper::count())->toBe(0);
});

it('rejects a subject not in the canonical list', function () {
    Storage::fake('r2');

    $this->actingAs(tutor())->post(route('tutor.exam-papers.store'), [
        'title'   => 'Bad subject',
        'level'   => 'Primary 6',
        'subject' => 'Underwater Basket Weaving',
        'year'    => 2024,
        'file'    => UploadedFile::fake()->create('x.pdf', 100, 'application/pdf'),
    ])->assertSessionHasErrors('subject');

    expect(ExamPaper::count())->toBe(0);
});

it('filters exam papers by level', function () {
    makePaper(['level' => 'Primary 6', 'title' => 'P6 Paper']);
    makePaper(['level' => 'Secondary 4', 'title' => 'Sec 4 Paper']);

    $this->actingAs(student())
        ->get(route('student.exam-papers.index', ['level' => 'Primary 6']))
        ->assertOk()
        ->assertSee('P6 Paper')
        ->assertDontSee('Sec 4 Paper');
});

it('lets any student browse all exam papers', function () {
    makePaper(['title' => 'Maths Paper 2023', 'subject' => 'Mathematics', 'year' => 2023]);
    makePaper(['title' => 'English Paper 2022', 'subject' => 'English', 'year' => 2022]);

    $this->actingAs(student())
        ->get(route('student.exam-papers.index'))
        ->assertOk()
        ->assertSee('Maths Paper 2023')
        ->assertSee('English Paper 2022');
});

it('filters exam papers by subject', function () {
    makePaper(['subject' => 'Mathematics', 'title' => 'Maths Paper']);
    makePaper(['subject' => 'English',     'title' => 'English Paper']);

    $this->actingAs(student())
        ->get(route('student.exam-papers.index', ['subject' => 'Mathematics']))
        ->assertOk()
        ->assertSee('Maths Paper')
        ->assertDontSee('English Paper');
});

it('filters exam papers by year', function () {
    makePaper(['year' => 2023, 'title' => 'Paper 2023']);
    makePaper(['year' => 2021, 'title' => 'Paper 2021']);

    $this->actingAs(student())
        ->get(route('student.exam-papers.index', ['year' => 2023]))
        ->assertOk()
        ->assertSee('Paper 2023')
        ->assertDontSee('Paper 2021');
});

it('lets a student download an exam paper', function () {
    Storage::fake('r2');
    Storage::disk('r2')->put('exam-papers/fake.pdf', 'fake content');

    $paper = makePaper(['file_path' => 'exam-papers/fake.pdf']);

    $this->actingAs(student())
        ->get(route('student.exam-papers.download', $paper))
        ->assertOk();
});

it('lets a tutor delete an exam paper and removes the file from R2', function () {
    Storage::fake('r2');
    Storage::disk('r2')->put('exam-papers/delete-me.pdf', 'content');

    $paper = makePaper(['file_path' => 'exam-papers/delete-me.pdf']);

    $this->actingAs(tutor())
        ->delete(route('tutor.exam-papers.destroy', $paper))
        ->assertRedirect(route('tutor.exam-papers.index'));

    expect(ExamPaper::find($paper->id))->toBeNull();
    Storage::disk('r2')->assertMissing('exam-papers/delete-me.pdf');
});
