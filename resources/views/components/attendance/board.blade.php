{{-- Shared attendance board: Calendar/List toggle, both views, legend, counter.
     Must sit inside an element with x-data="attendancePage(...)" (resources/js/attendance.js).
     Used by the tutor page (editable) and the student page (readonly: no ticking, no buttons,
     and the student never receives billed/paid info — see docs/attendance-tracking.md D10).
     All classes live HERE because Tailwind does not scan resources/js. --}}
@props(['readonly' => false])

{{-- View toggle --}}
<div class="inline-flex rounded-xl border border-gray-200 bg-white p-1 shadow-sm" role="tablist" aria-label="Attendance view">
    <button type="button" role="tab" :aria-selected="view === 'calendar'" @click="view = 'calendar'; filterOpen = false"
            :class="view === 'calendar' ? 'bg-primary text-white' : 'text-ink hover:bg-primary/10'"
            class="cursor-pointer rounded-lg px-4 py-1.5 text-sm font-semibold transition-colors duration-200">Calendar View</button>
    <button type="button" role="tab" :aria-selected="view === 'list'" @click="view = 'list'"
            :class="view === 'list' ? 'bg-primary text-white' : 'text-ink hover:bg-primary/10'"
            class="cursor-pointer rounded-lg px-4 py-1.5 text-sm font-semibold transition-colors duration-200">List View</button>
</div>

{{-- ============================ CALENDAR ============================ --}}
<section x-show="view === 'calendar'" x-cloak class="mt-4 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm" aria-label="Attendance calendar">
    <div class="relative mb-2 flex h-9 items-center justify-center">
        <button type="button" @click="prevMonth()" :disabled="! canPrev" aria-label="Previous month"
                class="absolute left-0 grid h-9 w-9 cursor-pointer place-items-center rounded-lg text-muted transition-colors duration-200 hover:bg-primary/10 hover:text-primary-dark disabled:cursor-not-allowed disabled:opacity-30">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
        </button>
        <p class="text-sm font-bold text-ink" x-text="monthLabel" aria-live="polite"></p>
        <button type="button" @click="nextMonth()" :disabled="! canNext" aria-label="Next month"
                class="absolute right-0 grid h-9 w-9 cursor-pointer place-items-center rounded-lg text-muted transition-colors duration-200 hover:bg-primary/10 hover:text-primary-dark disabled:cursor-not-allowed disabled:opacity-30">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
        </button>
    </div>

    <div class="mx-auto grid max-w-[19.25rem] grid-cols-7 justify-items-center gap-y-1">
        <template x-for="w in weekdays" :key="w">
            <span class="grid h-9 w-11 place-items-center text-xs font-semibold text-muted" x-text="w"></span>
        </template>

        <template x-for="cell in grid" :key="cell.date">
            <button type="button"
                    @click="toggle(cell.date)"
                    :disabled="cell.outside || ! canToggle(cell.date)"
                    :aria-pressed="st(cell.date) !== null"
                    :aria-label="fmtFull(cell.date) + (st(cell.date) ? ', ' + st(cell.date) : '')"
                    :class="{
                        'text-ink/30 cursor-default': cell.outside,
                        'text-ink hover:bg-primary/10 cursor-pointer': ! cell.outside && st(cell.date) === null && canToggle(cell.date),
                        'text-ink/40 cursor-not-allowed': ! cell.outside && st(cell.date) === null && ! canToggle(cell.date),
                        'bg-primary/15 text-primary-dark font-bold ring-2 ring-primary/40 shadow-[0_0_10px_rgba(124,58,237,0.35)]': ! cell.outside && (st(cell.date) === 'attended' || st(cell.date) === 'billed'),
                        'cursor-pointer': ! cell.outside && st(cell.date) === 'attended' && canToggle(cell.date),
                        'cursor-not-allowed': ! cell.outside && st(cell.date) === 'billed',
                        'bg-success/15 text-success font-bold ring-2 ring-success/40 shadow-[0_0_10px_rgba(22,163,74,0.3)] cursor-not-allowed': ! cell.outside && st(cell.date) === 'paid',
                        '!ring-danger !shadow-none': ! cell.outside && flagged(cell.date),
                    }"
                    class="relative grid h-10 w-11 place-items-center rounded-lg text-sm transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                <span x-text="cell.day"></span>
                {{-- today marker --}}
                <span x-show="cell.date === today && ! cell.outside" class="absolute bottom-1 left-1/2 h-[3px] w-[3px] -translate-x-1/2 rounded-full bg-primary"></span>
                {{-- billed marker: amber dot (locked, waiting for payment) --}}
                @unless ($readonly)
                    <span x-show="st(cell.date) === 'billed' && ! cell.outside" class="absolute right-0.5 top-0.5 h-2 w-2 rounded-full bg-accent ring-1 ring-white"></span>
                @endunless
            </button>
        </template>
    </div>
</section>

{{-- ============================== LIST ============================== --}}
<section x-show="view === 'list'" x-cloak class="relative mt-4 rounded-2xl border border-gray-200 bg-white shadow-sm" aria-label="Attendance list">
    <div class="grid grid-cols-3 items-center rounded-t-2xl border-b border-gray-200 bg-primary/5 px-4 py-2.5 text-sm font-bold text-ink">
        <span>Date</span>
        <button type="button" @click="openFilter()" :aria-expanded="filterOpen"
                class="mx-auto flex cursor-pointer items-center gap-1.5 rounded-lg px-2 py-1 text-primary-dark transition-colors duration-200 hover:bg-primary/10">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" /></svg>
            Filter
        </button>
        <span class="text-right">Attended</span>
    </div>

    {{-- Filter popover --}}
    <div x-show="filterOpen" x-cloak @click.outside="filterOpen = false" @keydown.escape.window="filterOpen = false"
         class="absolute left-1/2 top-12 z-20 w-72 max-w-[calc(100%-1rem)] -translate-x-1/2 rounded-xl border border-gray-200 bg-white p-4 shadow-lg">
        <fieldset class="space-y-3">
            <legend class="sr-only">Filter by</legend>
            <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-ink">
                <input type="radio" value="date" x-model="draftFilter.mode" class="cursor-pointer text-primary focus:ring-primary"> Specific date
            </label>
            <div x-show="draftFilter.mode === 'date'">
                <input type="date" x-model="draftFilter.date" aria-label="Specific date"
                       class="w-full rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary">
            </div>
            <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-ink">
                <input type="radio" value="range" x-model="draftFilter.mode" class="cursor-pointer text-primary focus:ring-primary"> Date range
            </label>
            <div x-show="draftFilter.mode === 'range'" class="grid grid-cols-2 gap-2">
                <label class="text-xs font-semibold text-muted">From
                    <input type="date" x-model="draftFilter.from" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary">
                </label>
                <label class="text-xs font-semibold text-muted">To
                    <input type="date" x-model="draftFilter.to" class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary">
                </label>
            </div>
        </fieldset>
        <p x-show="filterError" x-text="filterError" class="mt-2 text-xs font-semibold text-danger"></p>
        <div class="mt-3 flex justify-between gap-2">
            <button type="button" @click="clearFilter()" class="cursor-pointer rounded-lg px-3 py-1.5 text-sm font-semibold text-muted hover:bg-gray-100">Clear</button>
            <button type="button" @click="applyFilter()" class="cursor-pointer rounded-lg bg-primary px-4 py-1.5 text-sm font-semibold text-white hover:bg-primary-dark">Apply</button>
        </div>
    </div>

    <p class="border-b border-gray-100 px-4 py-2 text-xs text-muted">Showing <span class="font-semibold text-ink" x-text="filterLabel"></span> &middot; newest first</p>

    <ul class="max-h-[28rem] divide-y divide-gray-100 overflow-y-auto">
        <template x-for="d in listDates" :key="d">
            <li class="flex items-center justify-between gap-3 px-4 py-2.5"
                :class="{ 'bg-primary/5': st(d) === 'attended' || st(d) === 'billed', 'bg-success/5': st(d) === 'paid' }">
                <div class="min-w-0">
                    <span class="text-sm font-semibold text-ink" x-text="fmtRow(d)"></span>
                    <span x-show="d === today" class="ml-1.5 rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-bold text-primary-dark">Today</span>
                    @unless ($readonly)
                        <span x-show="st(d) === 'billed'" class="ml-1.5 inline-flex items-center gap-1 text-xs font-bold text-accent-dark">
                            <span class="h-2 w-2 rounded-full bg-accent"></span>Billed
                        </span>
                        <span x-show="st(d) === 'paid'" class="ml-1.5 text-xs font-bold text-success">Paid</span>
                    @endunless
                </div>
                <button type="button" role="checkbox" :aria-checked="st(d) !== null"
                        :aria-label="'Attended on ' + fmtFull(d)"
                        @click="toggle(d)" :disabled="! canToggle(d)"
                        :class="{
                            'border-gray-300 bg-white hover:border-primary/60 cursor-pointer': st(d) === null && canToggle(d),
                            'border-gray-200 bg-gray-50 cursor-not-allowed': st(d) === null && ! canToggle(d),
                            'border-primary bg-primary/15 text-primary-dark ring-2 ring-primary/30 shadow-[0_0_10px_rgba(124,58,237,0.35)]': st(d) === 'attended' || st(d) === 'billed',
                            'cursor-pointer': st(d) === 'attended' && canToggle(d),
                            'cursor-not-allowed': st(d) === 'billed',
                            'border-success bg-success/15 text-success ring-2 ring-success/30 cursor-not-allowed': st(d) === 'paid',
                            '!border-danger !ring-danger': flagged(d),
                        }"
                        class="grid h-7 w-7 shrink-0 place-items-center rounded-md border-2 transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <svg x-show="st(d) === 'attended'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                    {{-- locked: billed/paid show a padlock --}}
                    <svg x-show="st(d) === 'billed' || st(d) === 'paid'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                </button>
            </li>
        </template>
        <li x-show="listDates.length === 0" class="px-4 py-10 text-center text-sm text-muted">No dates in this filter.</li>
    </ul>
</section>

{{-- ============ legend + counter ============ --}}
<div class="mt-4 flex flex-wrap items-center justify-between gap-3">
    <ul class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs font-semibold text-muted" aria-label="Legend">
        <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-primary/15 ring-2 ring-primary/40"></span>Attended</li>
        @unless ($readonly)
            <li class="flex items-center gap-1.5"><span class="relative h-3 w-3 rounded bg-primary/15 ring-2 ring-primary/40"><span class="absolute -right-1 -top-1 h-2 w-2 rounded-full bg-accent ring-1 ring-white"></span></span>Billed</li>
            <li class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-success/15 ring-2 ring-success/40"></span>Paid</li>
        @endunless
    </ul>
    <div class="text-right" aria-live="polite">
        <p class="inline-flex items-baseline gap-2 rounded-full bg-primary/10 px-4 py-1.5 text-sm font-bold text-primary-dark">
            <span>{{ $readonly ? 'Lessons attended' : 'No. of lessons attended' }}:</span>
            <span class="text-lg" x-text="count"></span>
        </p>
        @unless ($readonly)
            <p class="mt-1 text-xs text-muted">Billed: <span x-text="billedCount"></span> &middot; Paid: <span x-text="paidCount"></span></p>
        @endunless
    </div>
</div>

{{ $slot }}
