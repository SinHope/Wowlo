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
        $students = User::where('role', 'student')
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
        abort_unless($student->isStudent(), 404);

        return view('tutor.students.edit', compact('student'));
    }

    /**
     * Update a student account.
     */
    public function update(StudentRequest $request, User $student): RedirectResponse
    {
        abort_unless($student->isStudent(), 404);

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
        abort_unless($student->isStudent(), 404);

        $student->delete();

        return redirect()->route('tutor.students.index')
            ->with('status', 'Student deleted.');
    }
}
