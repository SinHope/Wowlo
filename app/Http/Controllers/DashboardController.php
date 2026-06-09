<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Show the dashboard for the logged-in user, branching by role.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        if ($user->actsAsTutor()) {
            // Scope every stat to this teacher's own roster (tenancy).
            return view('tutor.dashboard', [
                'studentCount' => $user->students()->count(),
                'pendingHomework' => \App\Models\Homework::where('tutor_id', $user->id)->where('status', 'pending')->count(),
                'recentHomework' => \App\Models\Homework::where('tutor_id', $user->id)->with('student')->latest()->take(5)->get(),
                'createdThisWeek' => \App\Models\Homework::where('tutor_id', $user->id)->where('created_at', '>=', now()->startOfWeek())->count(),
                // Submitted short-answer quizzes still waiting on this tutor's marking.
                'quizzesToMark' => \App\Models\QuizAttempt::whereNotNull('completed_at')
                    ->whereNull('graded_at')
                    ->whereHas('quiz', fn ($q) => $q->where('tutor_id', $user->id)
                        ->whereHas('questions', fn ($qq) => $qq->where('question_type', 'short_answer')))
                    ->count(),
            ]);
        }

        // Quizzes assigned to this student that they haven't submitted yet.
        $assignedQuizIds = \App\Models\Quiz::whereHas('assignments', fn ($q) => $q->where('student_id', $user->id))->pluck('id');
        $completedQuizIds = $user->quizAttempts()->whereNotNull('completed_at')->pluck('quiz_id');
        $pendingQuizIds = $assignedQuizIds->diff($completedQuizIds);

        return view('student.dashboard', [
            'upcomingHomework' => $user->homework()
                ->where('status', 'pending')
                ->orderBy('due_date')
                ->take(3)
                ->get(),
            'pendingCount' => $user->homework()->where('status', 'pending')->count(),
            'latestMessage' => $user->receivedMessages()->with('sender')->latest()->first(),
            'unreadMessages' => $user->receivedMessages()->where('is_read', false)->count(),
            'pendingQuizCount' => $pendingQuizIds->count(),
            'pendingQuizzes' => \App\Models\Quiz::whereIn('id', $pendingQuizIds)->latest('id')->take(3)->get(),
            'outstanding' => 0,
        ]);
    }
}
