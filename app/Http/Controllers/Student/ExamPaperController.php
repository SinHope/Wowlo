<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamPaper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamPaperController extends Controller
{
    public function index(Request $request): View
    {
        $level   = $request->input('level');
        $subject = $request->input('subject');
        $year    = $request->integer('year') ?: null;

        // Shared library: students only ever see APPROVED papers.
        $papers = ExamPaper::approved()
            ->whereNotNull('level')
            ->when($level, fn ($q) => $q->where('level', $level))
            ->when($subject, fn ($q) => $q->where('subject', $subject))
            ->when($year, fn ($q) => $q->where('year', $year))
            ->orderByDesc('year')
            ->orderByDesc('id')
            ->get();

        $grouped = ExamPaper::groupForDisplay($papers);

        // Filter dropdowns: only levels/subjects/years that have approved papers.
        $levels = collect(config('wowlo.levels'))
            ->filter(fn ($l) => ExamPaper::approved()->where('level', $l)->exists())
            ->values();
        $subjects = collect(config('wowlo.subjects'))
            ->filter(fn ($s) => ExamPaper::approved()->where('subject', $s)->exists())
            ->values();
        $years = ExamPaper::approved()->whereNotNull('level')->distinct()->orderByDesc('year')->pluck('year');

        return view('student.exam-papers.index', [
            'grouped'  => $grouped,
            'total'    => $papers->count(),
            'levels'   => $levels,
            'subjects' => $subjects,
            'years'    => $years,
            'level'    => $level,
            'subject'  => $subject,
            'year'     => $year,
        ]);
    }

    public function download(ExamPaper $examPaper): StreamedResponse
    {
        // Students may only download approved papers from the shared library.
        abort_unless($examPaper->isApproved(), 404);

        return Storage::disk('r2')->download($examPaper->file_path, $examPaper->original_filename);
    }
}
