<x-app-layout>
    <x-slot name="header">Multiplication Results</x-slot>

    @if ($attempt->hasReflection())
        {{-- ===================== NORMAL MODE (reflection done) ===================== --}}
        <div class="mx-auto max-w-2xl space-y-5">

            @if (session('status'))
                <div class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-success">
                    {{ session('status') }}
                </div>
            @endif

            <a href="{{ route('student.games.multiplication.progress') }}" class="inline-block text-sm font-semibold text-teal-700 hover:underline">&larr; My Progress</a>

            @include('student.games.multiplication.partials.results', ['attempt' => $attempt])

            {{-- Reflection (saved; editable) --}}
            <div x-data="{ editing: false }" class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-base font-extrabold text-ink">My Reflection / Learning Points</h3>
                <p class="mt-1 text-xs text-gray-500">What did you find tricky? What will you remember next time?</p>

                <div x-show="! editing" class="mt-3">
                    <p class="whitespace-pre-line text-sm text-ink">{{ $attempt->reflection }}</p>
                    <button type="button" @click="editing = true" class="mt-3 text-sm font-semibold text-teal-700 hover:underline cursor-pointer">Edit</button>
                </div>

                <form x-show="editing" x-cloak method="POST" action="{{ route('student.games.multiplication.reflection', $attempt) }}" class="mt-3"
                      x-data="{ text: @js($attempt->reflection) }">
                    @csrf
                    @method('PATCH')
                    <textarea name="reflection" rows="4" required x-model="text"
                              class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                              placeholder="I learnt that…">{{ $attempt->reflection }}</textarea>
                    <button type="submit" :disabled="text.trim() === ''"
                            class="mt-3 rounded-xl bg-teal-500 px-5 py-2.5 text-sm font-extrabold text-white transition-colors duration-200 hover:bg-teal-600 cursor-pointer disabled:cursor-not-allowed disabled:opacity-50">
                        Save reflection
                    </button>
                </form>
            </div>

            {{-- Tutor feedback --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <h3 class="text-base font-extrabold text-ink">Tutor Feedback</h3>
                @if ($attempt->hasFeedback())
                    <p class="mt-2 whitespace-pre-line text-sm text-ink">{{ $attempt->feedback }}</p>
                    <p class="mt-3 text-xs text-gray-400">
                        — {{ $attempt->feedbackBy?->name ?? 'Your tutor' }}{{ $attempt->feedback_at ? ', ' . $attempt->feedback_at->format('j M Y') : '' }}
                    </p>
                @else
                    <p class="mt-2 text-sm text-gray-400">No feedback yet — your tutor will add notes here.</p>
                @endif
            </div>

            <div class="text-center">
                <a href="{{ route('student.games.multiplication.play') }}"
                   class="inline-block rounded-2xl bg-teal-500 px-8 py-3 text-base font-extrabold text-white shadow-sm transition-colors duration-200 hover:bg-teal-600 cursor-pointer">
                    Play again
                </a>
            </div>
        </div>
    @else
        {{-- ===================== GATE MODE (reflection still required) =====================
             A full-screen, SCROLLABLE panel that covers the sidebar/top-bar (z-[100]) so the
             rest of the app is blocked — but it SHOWS the marks + per-question review first,
             then the reflection box at the bottom. The student must write something to
             continue; only then does the normal page (with tutor feedback) unlock. --}}
        <div x-data="{ text: @js(old('reflection', '')) }"
             class="fixed inset-0 z-[100] overflow-y-auto bg-cream">
            <div class="mx-auto max-w-2xl space-y-5 p-4 sm:p-6">

                <div class="rounded-2xl border border-teal-500/20 bg-teal-500/5 px-5 py-4 text-center">
                    <p class="text-lg font-extrabold text-ink">Round complete! Here's how you did.</p>
                    <p class="mt-0.5 text-sm text-gray-600">Look through your marks, then write your reflection at the bottom to finish.</p>
                </div>

                @include('student.games.multiplication.partials.results', ['attempt' => $attempt])

                {{-- Reflection — required, no minimum length --}}
                <div class="rounded-2xl border-2 border-teal-500/40 bg-teal-500/5 p-6 shadow-sm">
                    <h3 class="text-base font-extrabold text-ink">My Reflection / Learning Points</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Now that you've seen your results — what did you find tricky? What will you remember next time?
                        <span class="font-semibold text-teal-700">You can't go anywhere else until you write something (even a few words is fine).</span>
                    </p>

                    <form method="POST" action="{{ route('student.games.multiplication.reflection', $attempt) }}" class="mt-4">
                        @csrf
                        @method('PATCH')
                        <textarea name="reflection" rows="4" required x-model="text" autofocus
                                  class="w-full rounded-xl border-gray-300 text-sm focus:border-teal-500 focus:ring-teal-500"
                                  placeholder="I learnt that…">{{ old('reflection') }}</textarea>
                        @error('reflection')
                            <p class="mt-1 text-sm text-danger">{{ $message }}</p>
                        @enderror
                        <button type="submit" :disabled="text.trim() === ''"
                                class="mt-3 w-full rounded-xl bg-teal-500 px-5 py-3 text-base font-extrabold text-white transition-colors duration-200 hover:bg-teal-600 cursor-pointer disabled:cursor-not-allowed disabled:opacity-50">
                            Save &amp; continue
                        </button>
                    </form>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
