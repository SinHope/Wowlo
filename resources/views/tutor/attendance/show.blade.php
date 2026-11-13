<x-app-layout>
    <x-slot name="header">Attendance Tracking</x-slot>

    <div class="mx-auto max-w-lg"
         x-data="attendancePage({{ Illuminate\Support\Js::from($config) }})"
         @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
         @pageshow.window="busy = false">

        {{-- Flash + validation messages --}}
        @if (session('status'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="mb-4 flex items-center justify-between rounded-xl border border-success/30 bg-success/10 px-4 py-3">
                <p class="text-sm font-semibold text-success">{{ session('status') }}</p>
                <button type="button" @click="show = false" class="cursor-pointer text-success" aria-label="Dismiss">&times;</button>
            </div>
        @endif
        @if (session('warning'))
            <div x-data="{ show: true }" x-show="show" x-transition
                 class="mb-4 flex items-center justify-between rounded-xl border border-accent/40 bg-accent/10 px-4 py-3">
                <p class="text-sm font-semibold text-accent-dark">{{ session('warning') }}</p>
                <button type="button" @click="show = false" class="cursor-pointer text-accent-dark" aria-label="Dismiss">&times;</button>
            </div>
        @endif
        @if ($errors->any())
            <div class="mb-4 rounded-xl border border-danger/30 bg-danger/5 px-4 py-3" role="alert">
                <ul class="list-inside list-disc space-y-0.5 text-sm font-semibold text-danger">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Recover ticks lost to a failed save (419 / 500 bounce) --}}
        <div x-show="draft" x-cloak class="mb-4 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-primary/30 bg-primary/5 px-4 py-3">
            <p class="text-sm font-semibold text-primary-dark">You have unsaved ticks from earlier. Restore them?</p>
            <div class="flex gap-2">
                <button type="button" @click="discardDraft()" class="cursor-pointer rounded-lg px-3 py-1.5 text-sm font-semibold text-muted hover:bg-white">Discard</button>
                <button type="button" @click="restoreDraft()" class="cursor-pointer rounded-lg bg-primary px-3 py-1.5 text-sm font-semibold text-white hover:bg-primary-dark">Restore</button>
            </div>
        </div>

        {{-- Student header --}}
        <div class="mb-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-muted">Student</p>
            <div class="flex flex-wrap items-baseline gap-x-3">
                <h2 class="text-2xl font-extrabold text-ink">{{ $student->name }}</h2>
                <a href="{{ route('tutor.attendance.index') }}" class="cursor-pointer text-sm font-semibold text-primary-dark hover:underline">Change student</a>
            </div>
        </div>

        <x-attendance.board>
            {{-- Actions --}}
            <div class="mt-5 flex flex-wrap items-center justify-end gap-3">
                <span x-show="hasPending" x-cloak class="mr-auto rounded-full bg-accent/15 px-3 py-1 text-xs font-bold text-accent-dark">
                    Unsaved changes (<span x-text="pendingCount"></span>)
                </span>
                <button type="button" @click="requestSave()" :disabled="! hasPending || busy"
                        class="cursor-pointer rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark disabled:cursor-not-allowed disabled:opacity-50">Save</button>
                <button type="button" @click="requestBilling()" :disabled="count === 0 || busy"
                        class="cursor-pointer rounded-lg bg-success px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">Add To Billing</button>
            </div>
        </x-attendance.board>

        {{-- Real submit forms (the buttons above drive them after any confirm pop-up) --}}
        <form method="POST" action="{{ route('tutor.attendance.update', $student->id) }}" x-ref="saveForm" class="hidden">
            @csrf
            @method('PUT')
            @include('tutor.attendance.partials.fields')
        </form>
        <form method="POST" action="{{ route('tutor.attendance.billing', $student->id) }}" x-ref="billForm" class="hidden">
            @csrf
            @include('tutor.attendance.partials.fields')
        </form>

        {{-- Save warning: later dates are allowed to be saved, but ask first. --}}
        <div x-show="saveWarnOpen" x-cloak @keydown.escape.window="saveWarnOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4" role="dialog" aria-modal="true" aria-labelledby="save-warn-title">
            <div @click.outside="saveWarnOpen = false" class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </span>
                    <div>
                        <h3 id="save-warn-title" class="text-lg font-bold text-ink">Later date selected</h3>
                        <p class="mt-1 text-sm text-muted">Some ticked dates are later than today (<span x-text="fmtFull(today)"></span>). Save anyway?</p>
                        <ul class="mt-2 list-inside list-disc text-sm font-semibold text-ink">
                            <template x-for="d in popupDates" :key="d"><li x-text="fmtFull(d)"></li></template>
                        </ul>
                    </div>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" @click="saveWarnOpen = false"
                            class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-ink hover:bg-gray-50">Go back</button>
                    <button type="button" @click="confirmSave()"
                            class="cursor-pointer rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark">Yes, save</button>
                </div>
            </div>
        </div>

        {{-- Add-to-Billing block: a later date can never reach a bill. --}}
        <div x-show="billBlockOpen" x-cloak @keydown.escape.window="billBlockOpen = false"
             class="fixed inset-0 z-50 flex items-center justify-center bg-ink/40 p-4" role="alertdialog" aria-modal="true" aria-labelledby="bill-block-title">
            <div @click.outside="billBlockOpen = false" class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-xl">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-red-100 text-danger">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" /></svg>
                    </span>
                    <div>
                        <h3 id="bill-block-title" class="text-lg font-bold text-ink">Please recheck the dates</h3>
                        <p class="mt-1 text-sm text-muted">A later date can't be added to billing (today is <span x-text="fmtFull(today)"></span>). Untick or change:</p>
                        <ul class="mt-2 list-inside list-disc text-sm font-semibold text-danger">
                            <template x-for="d in popupDates" :key="d"><li x-text="fmtFull(d)"></li></template>
                        </ul>
                    </div>
                </div>
                <div class="mt-5 flex justify-end">
                    <button type="button" @click="billBlockOpen = false"
                            class="cursor-pointer rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark">Go back</button>
                </div>
            </div>
        </div>

        <x-spinner-overlay />
    </div>
</x-app-layout>
