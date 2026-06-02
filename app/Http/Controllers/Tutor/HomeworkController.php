<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\HomeworkRequest;
use App\Models\Homework;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HomeworkController extends Controller
{
    public function index(): View
    {
        $homework = Homework::with('student')
            ->latest('created_at')
            ->paginate(15);

        return view('tutor.homework.index', compact('homework'));
    }

    public function create(): View
    {
        return view('tutor.homework.create', [
            'students' => $this->students(),
        ]);
    }

    public function store(HomeworkRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['tutor_id'] = $request->user()->id;
        $data = array_merge($data, $this->handleUpload($request));

        $homework = Homework::create($data);

        // Best-effort push to the assigned student; never break the request.
        try {
            $homework->student?->notify(new \App\Notifications\NewHomeworkNotification($homework));
        } catch (\Throwable $e) {
            report($e);
        }

        return redirect()->route('tutor.homework.index')
            ->with('status', 'Homework assigned.');
    }

    public function edit(Homework $homework): View
    {
        return view('tutor.homework.edit', [
            'homework' => $homework,
            'students' => $this->students(),
        ]);
    }

    public function update(HomeworkRequest $request, Homework $homework): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('attachment')) {
            $this->deleteAttachment($homework);
            $data = array_merge($data, $this->handleUpload($request));
        } else {
            unset($data['attachment']);
        }

        $homework->update($data);

        return redirect()->route('tutor.homework.index')
            ->with('status', 'Homework updated.');
    }

    public function destroy(Homework $homework): RedirectResponse
    {
        $this->deleteAttachment($homework);
        $homework->delete();

        return redirect()->route('tutor.homework.index')
            ->with('status', 'Homework deleted.');
    }

    /**
     * Homework completion overview, optionally filtered to one student.
     */
    public function status(Request $request): View
    {
        $students = $this->students();
        $selectedId = $request->integer('student_id') ?: null;

        $homework = $selectedId
            ? Homework::where('student_id', $selectedId)->latest('due_date')->get()
            : collect();

        return view('tutor.homework.status', compact('students', 'selectedId', 'homework'));
    }

    /**
     * Stream an attachment from R2 (tutor can access any).
     */
    public function download(Homework $homework): StreamedResponse
    {
        abort_unless($homework->hasAttachment(), 404);

        return Storage::disk('r2')->download($homework->attachment_path, $homework->attachment_name);
    }

    /**
     * All student accounts for the assignee dropdown.
     */
    private function students()
    {
        return User::where('role', 'student')->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Store an uploaded file on R2 and return the columns to persist.
     */
    private function handleUpload(Request $request): array
    {
        if (! $request->hasFile('attachment')) {
            return [];
        }

        $file = $request->file('attachment');

        return [
            'attachment_path' => $file->store('homework', 'r2'),
            'attachment_name' => $file->getClientOriginalName(),
        ];
    }

    private function deleteAttachment(Homework $homework): void
    {
        if ($homework->hasAttachment()) {
            Storage::disk('r2')->delete($homework->attachment_path);
        }
    }
}
