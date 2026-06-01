<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExamPaperRequest;
use App\Models\ExamPaper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExamPaperController extends Controller
{
    public function index(): View
    {
        $papers  = ExamPaper::orderByDesc('year')->orderByDesc('id')->get();
        $grouped = ExamPaper::groupForDisplay($papers);

        return view('tutor.exam-papers.index', [
            'grouped' => $grouped,
            'total'   => $papers->whereNotNull('level')->count(),
        ]);
    }

    public function create(): View
    {
        return view('tutor.exam-papers.create', [
            'levels'   => config('wowlo.levels'),
            'subjects' => config('wowlo.subjects'),
        ]);
    }

    public function store(ExamPaperRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $file = $request->file('file');

        $data['file_path'] = $file->store('exam-papers', 'r2');
        $data['original_filename'] = $file->getClientOriginalName();
        $data['tutor_id'] = $request->user()->id;
        unset($data['file']);

        ExamPaper::create($data);

        return redirect()->route('tutor.exam-papers.index')
            ->with('status', 'Exam paper uploaded.');
    }

    public function destroy(ExamPaper $examPaper): RedirectResponse
    {
        Storage::disk('r2')->delete($examPaper->file_path);
        $examPaper->delete();

        return redirect()->route('tutor.exam-papers.index')
            ->with('status', 'Exam paper deleted.');
    }

    public function download(ExamPaper $examPaper): StreamedResponse
    {
        return Storage::disk('r2')->download($examPaper->file_path, $examPaper->original_filename);
    }
}
