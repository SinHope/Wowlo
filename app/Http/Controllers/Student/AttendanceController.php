<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only attendance for the logged-in student.
 *
 * Isolation: this route takes NO student id — it only ever reads the
 * authenticated user's own rows. Billed/paid state is deliberately never sent:
 * fee information sits behind the parent's fee PIN lock, and a green "paid" day
 * would leak it around that lock (docs/attendance-tracking.md D10).
 */
class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $rows = $request->user()->attendances()
            ->orderBy('lesson_date')
            ->get(['lesson_date'])
            ->map(fn (Attendance $a) => [
                'date' => $a->lesson_date->format('Y-m-d'),
                'status' => 'attended',
            ])
            ->values()
            ->all();

        return view('student.attendance.index', [
            'config' => [
                'studentId' => $request->user()->id,
                'rows' => $rows,
                'today' => now()->toDateString(),
                'maxDate' => now()->addYear()->toDateString(),
                'readonly' => true,
                'justSaved' => false,
                'view' => 'calendar',
                'month' => now()->format('Y-m'),
                'from' => null,
                'to' => null,
            ],
        ]);
    }
}
