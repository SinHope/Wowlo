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
            ]);
        }

        return view('student.dashboard', [
            'upcomingHomework' => $user->homework()
                ->where('status', 'pending')
                ->orderBy('due_date')
                ->take(3)
                ->get(),
            'pendingCount' => $user->homework()->where('status', 'pending')->count(),
            'latestMessage' => $user->receivedMessages()->with('sender')->latest()->first(),
            'unreadMessages' => $user->receivedMessages()->where('is_read', false)->count(),
            'outstanding' => 0,
        ]);
    }
}
