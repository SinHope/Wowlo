<x-app-layout>
    <x-slot name="header">Exam Papers</x-slot>

    <div class="mx-auto max-w-5xl space-y-5">

        <div>
            <h2 class="text-2xl font-extrabold text-ink">Exam Papers</h2>
            <p class="text-muted">{{ $total }} {{ Str::plural('paper', $total) }} available for download</p>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('student.exam-papers.index') }}"
              class="flex flex-wrap items-end gap-3 rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
            <div class="min-w-[130px] flex-1">
                <label class="mb-1 block text-xs font-semibold text-muted">Level</label>
                <select name="level"
                        class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                    <option value="">All levels</option>
                    @foreach ($levels as $l)
                        <option value="{{ $l }}" @selected($level === $l)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[130px] flex-1">
                <label class="mb-1 block text-xs font-semibold text-muted">Subject</label>
                <select name="subject"
                        class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                    <option value="">All subjects</option>
                    @foreach ($subjects as $s)
                        <option value="{{ $s }}" @selected($subject === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[110px] flex-1">
                <label class="mb-1 block text-xs font-semibold text-muted">Year</label>
                <select name="year"
                        class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                    <option value="">All years</option>
                    @foreach ($years as $y)
                        <option value="{{ $y }}" @selected((string) $year === (string) $y)>{{ $y }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit"
                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-primary-dark cursor-pointer">
                Filter
            </button>
            @if ($level || $subject || $year)
                <a href="{{ route('student.exam-papers.index') }}"
                   class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">
                    Clear
                </a>
            @endif
        </form>

        @forelse ($grouped as $level => $subjects)
            <div x-data="{ open: true }" class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
                {{-- Level header --}}
                <button type="button" @click="open = !open"
                        class="flex w-full items-center justify-between gap-3 bg-primary/5 px-5 py-4 text-left cursor-pointer hover:bg-primary/10">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-white">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                            </svg>
                        </span>
                        <span class="text-lg font-extrabold text-ink">{{ $level }}</span>
                        <span class="rounded-full bg-primary/10 px-2 py-0.5 text-xs font-semibold text-primary">{{ $subjects->flatten()->count() }}</span>
                    </div>
                    <svg class="h-5 w-5 text-primary transition-transform duration-200" :class="open && 'rotate-180'"
                         fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>

                {{-- Subjects within this level --}}
                <div x-show="open" x-collapse.duration.200ms class="border-t border-gray-100 p-3 space-y-2">
                    @foreach ($subjects as $subject => $papers)
                        <div x-data="{ subOpen: true }" class="overflow-hidden rounded-xl border border-gray-100">
                            <button type="button" @click="subOpen = !subOpen"
                                    class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left cursor-pointer hover:bg-gray-50">
                                <div class="flex items-center gap-2.5">
                                    <svg class="h-4 w-4 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                    <span class="font-bold text-ink">{{ $subject }}</span>
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-muted">{{ $papers->count() }}</span>
                                </div>
                                <svg class="h-4 w-4 text-gray-400 transition-transform duration-200" :class="subOpen && 'rotate-180'"
                                     fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                </svg>
                            </button>

                            {{-- Years within this subject --}}
                            <div x-show="subOpen" x-collapse.duration.200ms class="border-t border-gray-100">
                                @foreach ($papers->groupBy('year') as $year => $yearPapers)
                                    <div class="px-4 py-2">
                                        <p class="mb-1.5 text-xs font-bold uppercase tracking-wide text-amber-600">{{ $year }}</p>
                                        <div class="space-y-1.5">
                                            @foreach ($yearPapers as $paper)
                                                <div class="flex items-center gap-3 rounded-lg bg-gray-50 px-3 py-2">
                                                    <p class="min-w-0 flex-1 truncate text-sm font-semibold text-ink">{{ $paper->title }}</p>
                                                    <a href="{{ route('student.exam-papers.download', $paper) }}"
                                                       class="inline-flex shrink-0 items-center gap-1 rounded-md bg-primary px-2.5 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-primary-dark cursor-pointer">
                                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                        </svg>
                                                        Download
                                                    </a>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-gray-100 bg-white py-16 text-center shadow-sm">
                <p class="text-lg font-bold text-ink">No exam papers available</p>
                <p class="mt-1 text-sm text-muted">
                    @if ($level || $subject || $year)
                        No papers match this filter.
                        <a href="{{ route('student.exam-papers.index') }}" class="text-primary underline">Clear filters</a>.
                    @else
                        Check back later.
                    @endif
                </p>
            </div>
        @endforelse
    </div>
</x-app-layout>
