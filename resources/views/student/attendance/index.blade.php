<x-app-layout>
    <x-slot name="header">Attendance</x-slot>

    <div class="mx-auto max-w-lg" x-data="attendancePage({{ Illuminate\Support\Js::from($config) }})">
        <div class="mb-4">
            <h2 class="text-2xl font-extrabold text-ink">My Attendance</h2>
            <p class="text-sm text-muted">The lessons your tutor has marked as attended.</p>
        </div>

        <x-attendance.board :readonly="true" />
    </div>
</x-app-layout>
