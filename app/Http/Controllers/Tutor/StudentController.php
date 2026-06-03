<?php

namespace App\Http\Controllers\Tutor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StudentRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * List all students (tutor view).
     */
    public function index(): View
    {
        // Tenancy: only this teacher's own roster.
        $students = auth()->user()->students()
            ->orderBy('name')
            ->paginate(15);

        return view('tutor.students.index', compact('students'));
    }

    /**
     * Show the create-student form.
     */
    public function create(): View
    {
        return view('tutor.students.create');
    }

    /**
     * Store a new student account.
     */
    public function store(StudentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['role'] = 'student';
        // Tenancy: stamp ownership server-side — never from client input.
        $data['tutor_id'] = auth()->id();
        // 'password' is hashed automatically by the User model's 'hashed' cast.

        User::create($data);

        return redirect()->route('tutor.students.index')
            ->with('status', 'Student account created.');
    }

    /**
     * Show the edit-student form.
     */
    public function edit(User $student): View
    {
        $this->ensureOwned($student);

        return view('tutor.students.edit', compact('student'));
    }

    /**
     * Update a student account.
     */
    public function update(StudentRequest $request, User $student): RedirectResponse
    {
        $this->ensureOwned($student);

        $data = $request->validated();

        // Keep the existing password if the field was left blank.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $student->update($data);

        return redirect()->route('tutor.students.index')
            ->with('status', 'Student updated.');
    }

    /**
     * Delete a student account.
     */
    public function destroy(User $student): RedirectResponse
    {
        $this->ensureOwned($student);

        $student->delete();

        return redirect()->route('tutor.students.index')
            ->with('status', 'Student deleted.');
    }

    /**
     * Guard: the bound user must be a student owned by the acting teacher.
     * 404 (not 403) so we never reveal another tutor's student IDs exist.
     */
    private function ensureOwned(User $student): void
    {
        abort_unless($student->isStudent() && $student->tutor_id === auth()->id(), 404);
    }
}
