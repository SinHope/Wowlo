<x-app-layout>
    <x-slot name="header">New {{ $typeLabel }}</x-slot>

    <div class="mx-auto max-w-3xl">
        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3">
                <p class="text-sm font-semibold text-danger">This sheet wasn't submitted — please fix:</p>
                <ul class="mt-1 list-disc space-y-0.5 pl-5 text-xs text-danger">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('student.resources.store', $type) }}" x-data="sheetBuilder()" class="space-y-5">
            @csrf

            <div x-show="validationError" x-cloak x-transition
                 class="flex items-start gap-2 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-danger" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <p class="text-sm font-semibold text-danger" x-text="validationError"></p>
            </div>

            {{-- Meta --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm space-y-5">
                <div>
                    <h2 class="text-xl font-extrabold text-ink">{{ $typeLabel }}</h2>
                    <p class="text-sm text-muted">Record your answers to a paper, then submit to your tutor to mark.</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-ink" for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" required
                           placeholder="e.g. P5 Science 2023 SA2 — my answers"
                           class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('title') border-danger @enderror">
                    @error('title') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="block text-sm font-semibold text-ink" for="subject">Subject</label>
                    <select id="subject" name="subject" required
                            class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('subject') border-danger @enderror">
                        <option value="" disabled @selected(! old('subject'))>Select subject…</option>
                        @foreach ($subjects as $s)
                            <option value="{{ $s }}" @selected(old('subject') === $s)>{{ $s }}</option>
                        @endforeach
                    </select>
                    @error('subject') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>
            </div>

            {{-- Questions --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-xl font-extrabold text-ink">Answers</h2>
                        <p class="text-sm text-muted"><span x-text="questions.length"></span> question(s)</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="number" min="1" max="100" x-model.number="bulk"
                               class="w-16 rounded-lg border border-gray-200 px-2 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                        <button type="button" @click="addQuestions(bulk)"
                                class="rounded-lg border border-gray-200 px-3 py-2 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">Add rows</button>
                        <button type="button" @click="addQuestions(1)"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-primary/10 px-3 py-2 text-sm font-semibold text-primary hover:bg-primary/20 cursor-pointer">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Add question
                        </button>
                    </div>
                </div>

                <div class="space-y-3">
                    <template x-for="(q, qi) in questions" :key="q.uid">
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <span class="text-sm font-bold text-ink">Question <span x-text="qi + 1"></span></span>
                                <button type="button" @click="removeQuestion(qi)" x-show="questions.length > 1"
                                        class="text-xs font-semibold text-danger hover:underline cursor-pointer">Remove</button>
                            </div>

                            @if ($type === 'mcq')
                                <div class="flex flex-wrap gap-2">
                                    <template x-for="n in 4" :key="n">
                                        <label class="cursor-pointer">
                                            <input type="radio" :name="`questions[${qi}][choice]`" :value="n" x-model.number="q.choice" class="peer sr-only">
                                            <span class="grid h-10 w-10 place-items-center rounded-full border border-gray-300 text-sm font-bold text-muted transition-colors peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white"
                                                  x-text="n"></span>
                                        </label>
                                    </template>
                                </div>
                            @else
                                <textarea :name="`questions[${qi}][answer_text]`" x-model="q.answer_text" rows="3"
                                          placeholder="Type your answer…"
                                          class="block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"></textarea>
                            @endif
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('student.resources.index', $type) }}"
                   class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">Cancel</a>
                <button type="button" @click="confirmSubmit()"
                        class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-dark cursor-pointer">
                    Submit
                </button>
            </div>

            {{-- Confirm modal --}}
            <div x-show="confirming" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" x-transition.opacity>
                <div class="absolute inset-0 bg-ink/50" @click="confirming = false"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100">
                    <h3 class="text-lg font-bold text-ink">Submit to your tutor?</h3>
                    <p class="mt-1 text-sm text-muted">Once submitted, you can't change your answers. Your tutor will mark it.</p>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="confirming = false"
                                class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">Keep working</button>
                        <button type="submit" @click="submitting = true"
                                class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">Yes, submit</button>
                    </div>
                </div>
            </div>

            {{-- Spinner --}}
            <div x-show="submitting" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center bg-ink/40">
                <div class="flex flex-col items-center gap-3 rounded-2xl bg-white px-8 py-6 shadow-xl">
                    <svg class="h-8 w-8 animate-spin text-primary" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-sm font-semibold text-ink">Submitting…</p>
                </div>
            </div>
        </form>
    </div>

    <script>
        function sheetBuilder() {
            let uid = 0;
            const blank = () => ({ uid: uid++, choice: null, answer_text: '' });
            const oldQuestions = @json(old('questions', []));

            let questions = oldQuestions.length
                ? oldQuestions.map(q => ({ uid: uid++, choice: q.choice ? parseInt(q.choice) : null, answer_text: q.answer_text ?? '' }))
                : [blank()];

            return {
                questions,
                bulk: 5,
                confirming: false,
                submitting: false,
                validationError: '',
                addQuestions(n) {
                    const count = Math.max(1, Math.min(parseInt(n) || 1, 100 - this.questions.length));
                    for (let i = 0; i < count; i++) this.questions.push(blank());
                },
                removeQuestion(i) { this.questions.splice(i, 1); },
                metaValue(id) {
                    const el = document.getElementById(id);
                    return el ? el.value.trim() : '';
                },
                fail(message) {
                    this.validationError = message;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return false;
                },
                confirmSubmit() {
                    this.validationError = '';
                    if (! this.metaValue('title'))   return this.fail('Please enter a title.');
                    if (! this.metaValue('subject')) return this.fail('Please choose a subject.');
                    if (this.questions.length === 0) return this.fail('Add at least one question.');
                    this.confirming = true;
                },
            };
        }
    </script>
</x-app-layout>
