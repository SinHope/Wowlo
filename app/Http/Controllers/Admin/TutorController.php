<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TutorRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Super-admin-only management of tutor accounts. There is no public tutor
 * sign-up yet (Phase 2) — the super_admin provisions every tutor here.
 */
class TutorController extends Controller
{
    public function index(): View
    {
        $tutors = User::where('role', 'tutor')
            ->withCount('students')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.tutors.index', compact('tutors'));
    }

    public function create(): View
    {
        return view('admin.tutors.create');
    }

    public function store(TutorRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['role'] = 'tutor';
        $data['tutor_id'] = null;   // tutors have no owning tutor

        User::create($data);

        return redirect()->route('admin.tutors.index')
            ->with('status', 'Tutor account created.');
    }

    public function edit(User $tutor): View
    {
        abort_unless($tutor->isTutor(), 404);

        return view('admin.tutors.edit', compact('tutor'));
    }

    public function update(TutorRequest $request, User $tutor): RedirectResponse
    {
        abort_unless($tutor->isTutor(), 404);

        $data = $request->validated();

        // Keep the existing password if the field was left blank.
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $tutor->update($data);

        return redirect()->route('admin.tutors.index')
            ->with('status', 'Tutor updated.');
    }

    public function destroy(User $tutor): RedirectResponse
    {
        abort_unless($tutor->isTutor(), 404);

        // Safety: never wipe a roster by accident. The DB also enforces this
        // (restrictOnDelete on users.tutor_id), but we surface a friendly error.
        if ($tutor->students()->exists()) {
            return back()->withErrors([
                'tutor' => 'This tutor still has students. Remove or reassign their students before deleting the account.',
            ]);
        }

        $tutor->delete();

        return redirect()->route('admin.tutors.index')
            ->with('status', 'Tutor deleted.');
    }
}
