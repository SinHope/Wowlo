{{-- Full-screen processing spinner. Expects `busy` (bool) + `stage` (text) in the
     surrounding Alpine scope — pair with the `spinner` Alpine component. --}}
<div x-show="busy" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-ink/40">
    <div class="flex flex-col items-center gap-3 rounded-2xl bg-white px-8 py-6 shadow-xl">
        <svg class="h-8 w-8 animate-spin text-primary" viewBox="0 0 24 24" fill="none">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <p class="text-sm font-semibold text-ink" x-text="stage"></p>
    </div>
</div>
