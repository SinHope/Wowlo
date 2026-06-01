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
                <button type="submit"
                        class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-dark cursor-pointer">
                    Save Quiz
                </button>
            </div>
        </form>
    </div>

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
                addQuestion() { this.questions.push(blank()); },
                removeQuestion(i) { this.questions.splice(i, 1); },
                get totalMarks() {
                    return this.questions.reduce((sum, q) => sum + (parseInt(q.marks) || 0), 0);
                },
            };
        }
    </script>
</x-app-layout>
