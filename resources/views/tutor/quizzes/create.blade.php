<x-app-layout>
    <x-slot name="header">Create Quiz</x-slot>

    <div class="mx-auto max-w-3xl">
        @if ($errors->any())
            <div class="mb-5 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3">
                <p class="text-sm font-semibold text-danger">Please fix the errors below.</p>
            </div>
        @endif

        <form method="POST" action="{{ route('tutor.quizzes.store') }}" enctype="multipart/form-data" x-data="quizForm()" class="space-y-5">
            @csrf

            {{-- Live validation banner --}}
            <div x-show="validationError" x-cloak x-transition
                 class="flex items-start gap-2 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3">
                <svg class="mt-0.5 h-5 w-5 shrink-0 text-danger" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                <p class="text-sm font-semibold text-danger" x-text="validationError"></p>
            </div>

            {{-- Quiz meta --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm space-y-5">
                <h2 class="text-xl font-extrabold text-ink">Quiz Details</h2>

                <div>
                    <label class="block text-sm font-semibold text-ink" for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" required
                           placeholder="e.g. P4 Science WA1 Chapter 3"
                           class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('title') border-danger @enderror">
                    @error('title') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-ink" for="level">Level</label>
                        <select id="level" name="level" required
                                class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('level') border-danger @enderror">
                            <option value="" disabled @selected(! old('level'))>Select level…</option>
                            @foreach ($levels as $l)
                                <option value="{{ $l }}" @selected(old('level') === $l)>{{ $l }}</option>
                            @endforeach
                        </select>
                        @error('level') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
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

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-ink" for="topic">
                            Topic <span class="font-normal text-muted">(optional)</span>
                        </label>
                        <input id="topic" name="topic" type="text" value="{{ old('topic') }}"
                               placeholder="e.g. Photosynthesis"
                               class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('topic') border-danger @enderror">
                        @error('topic') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-ink" for="exam_type">Exam Type</label>
                        <select id="exam_type" name="exam_type" required
                                class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('exam_type') border-danger @enderror">
                            <option value="" disabled @selected(! old('exam_type'))>Select exam type…</option>
                            @foreach ($examTypes as $key => $label)
                                <option value="{{ $key }}" @selected(old('exam_type') === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('exam_type') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>

            {{-- Questions --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-extrabold text-ink">Questions</h2>
                        <p class="text-sm text-muted">
                            <span x-text="questions.length"></span> question(s) ·
                            <span x-text="totalMarks"></span> total marks
                        </p>
                    </div>
                    <button type="button" @click="addQuestion()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-primary/10 px-3 py-2 text-sm font-semibold text-primary hover:bg-primary/20 cursor-pointer">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        Add Question
                    </button>
                </div>

                @error('questions') <p class="mb-3 text-xs text-danger">{{ $message }}</p> @enderror

                <div class="space-y-4">
                    <template x-for="(q, qi) in questions" :key="q.uid">
                        <div class="rounded-xl border border-gray-200 p-4">
                            <div class="mb-3 flex items-center justify-between">
                                <span class="text-sm font-bold text-ink">Question <span x-text="qi + 1"></span></span>
                                <button type="button" @click="removeQuestion(qi)" x-show="questions.length > 1"
                                        class="text-xs font-semibold text-danger hover:underline cursor-pointer">Remove</button>
                            </div>

                            {{-- Question text --}}
                            <textarea :name="`questions[${qi}][question_text]`" x-model="q.question_text" rows="2" required
                                      placeholder="Enter the question…"
                                      class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"></textarea>

                            {{-- Options A–D, each with a "correct" radio --}}
                            <div class="mt-3 space-y-2">
                                <template x-for="letter in ['A','B','C','D']" :key="letter">
                                    <div class="flex items-center gap-2">
                                        <label class="flex items-center gap-1.5 text-xs font-bold text-muted cursor-pointer">
                                            <input type="radio" :name="`questions[${qi}][correct_answer]`" :value="letter"
                                                   x-model="q.correct_answer" required
                                                   class="text-primary focus:ring-primary">
                                            <span x-text="letter"></span>
                                        </label>
                                        <input type="text" :name="`questions[${qi}][option_${letter.toLowerCase()}]`"
                                               x-model="q.options[letter]" required
                                               :placeholder="`Option ${letter}`"
                                               class="block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
                                               :class="q.correct_answer === letter && 'border-success bg-success/5'">
                                    </div>
                                </template>
                                <p class="text-xs text-muted">Select the radio next to the correct option.</p>
                            </div>

                            {{-- Optional diagram/attachment --}}
                            <div class="mt-3">
                                <label class="block text-sm font-semibold text-ink">
                                    Diagram / attachment <span class="font-normal text-muted">(optional — PDF or image, max 10 MB)</span>
                                </label>
                                <input type="file" :name="`questions[${qi}][image]`" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp"
                                       class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2 text-sm
                                              file:mr-3 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:py-1
                                              file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/20">
                                <p class="mt-1 text-xs text-muted">Use this for questions that need a picture (e.g. a drawn diagram).</p>
                            </div>

                            {{-- Marks --}}
                            <div class="mt-3 flex items-center gap-2">
                                <label class="text-sm font-semibold text-ink">Marks</label>
                                <input type="number" :name="`questions[${qi}][marks]`" x-model.number="q.marks" min="1" max="100" required
                                       class="w-24 rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('tutor.quizzes.index') }}"
                   class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">Cancel</a>
                <button type="button" @click="confirmSave()"
                        class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-dark cursor-pointer">
                    Save Quiz
                </button>
            </div>

            {{-- Confirmation modal --}}
            <div x-show="confirming" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center p-4"
                 x-transition.opacity>
                <div class="absolute inset-0 bg-ink/50" @click="confirming = false"></div>
                <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100">
                    <div class="flex items-start gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-amber/10">
                            <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                            </svg>
                        </span>
                        <div>
                            <h3 class="text-lg font-bold text-ink">Save this quiz?</h3>
                            <p class="mt-1 text-sm text-muted">
                                You won't be able to make any further changes after this. Please double-check your questions and answers.
                            </p>
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" @click="confirming = false"
                                class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">
                            Cancel
                        </button>
                        <button type="submit" @click="submitting = true"
                                class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark cursor-pointer">
                            Yes, save quiz
                        </button>
                    </div>
                </div>
            </div>

            {{-- Processing spinner overlay --}}
            <div x-show="submitting" x-cloak
                 class="fixed inset-0 z-[60] flex items-center justify-center bg-ink/40">
                <div class="flex flex-col items-center gap-3 rounded-2xl bg-white px-8 py-6 shadow-xl">
                    <svg class="h-8 w-8 animate-spin text-primary" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-sm font-semibold text-ink">Saving your quiz…</p>
                </div>
            </div>
        </form>
    </div>

    <style>[x-cloak]{display:none!important;}</style>

    <script>
        function quizForm() {
            const oldQuestions = @json(old('questions', []));
            let uid = 0;
            const blank = () => ({
                uid: uid++,
                question_text: '',
                options: { A: '', B: '', C: '', D: '' },
                correct_answer: '',
                marks: 1,
            });

            // Rebuild from old() input after a validation error, else start with one blank.
            let questions;
            if (oldQuestions.length) {
                questions = oldQuestions.map(q => ({
                    uid: uid++,
                    question_text: q.question_text ?? '',
                    options: {
                        A: q.option_a ?? '', B: q.option_b ?? '',
                        C: q.option_c ?? '', D: q.option_d ?? '',
                    },
                    correct_answer: q.correct_answer ?? '',
                    marks: q.marks ? parseInt(q.marks) : 1,
                }));
            } else {
                questions = [blank()];
            }

            return {
                questions,
                confirming: false,
                submitting: false,
                validationError: '',
                addQuestion() { this.questions.push(blank()); },
                removeQuestion(i) { this.questions.splice(i, 1); },
                get totalMarks() {
                    return this.questions.reduce((sum, q) => sum + (parseInt(q.marks) || 0), 0);
                },
                // Helper: read a meta field's current value by element id.
                metaValue(id) {
                    const el = document.getElementById(id);
                    return el ? el.value.trim() : '';
                },
                // Block the save and surface a clear message at the top of the form.
                fail(message) {
                    this.validationError = message;
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    return false;
                },
                // Validate everything explicitly; only open the confirm modal if all good.
                confirmSave() {
                    this.validationError = '';

                    // Quiz details
                    if (! this.metaValue('title'))     return this.fail('Please enter a quiz title.');
                    if (! this.metaValue('level'))     return this.fail('Please choose a level.');
                    if (! this.metaValue('subject'))   return this.fail('Please choose a subject.');
                    if (! this.metaValue('exam_type')) return this.fail('Please choose an exam type.');

                    // Questions
                    if (this.questions.length === 0) return this.fail('Add at least one question.');

                    for (let i = 0; i < this.questions.length; i++) {
                        const q = this.questions[i];
                        const n = i + 1;
                        if (! q.question_text.trim())  return this.fail(`Question ${n}: please enter the question.`);
                        for (const L of ['A', 'B', 'C', 'D']) {
                            if (! (q.options[L] || '').trim()) return this.fail(`Question ${n}: Option ${L} is empty.`);
                        }
                        if (! q.correct_answer)        return this.fail(`Question ${n}: select the correct answer.`);
                        if (! q.marks || q.marks < 1)  return this.fail(`Question ${n}: marks must be at least 1.`);
                    }

                    this.confirming = true;
                },
            };
        }
    </script>
</x-app-layout>
