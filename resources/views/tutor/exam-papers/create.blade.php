<x-app-layout>
    <x-slot name="header">Upload Exam Paper</x-slot>

    <div class="mx-auto max-w-2xl">
        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">

            <div class="mb-6">
                <h2 class="text-xl font-extrabold text-ink">Upload Exam Paper</h2>
                <p class="mt-1 text-sm text-muted">
                    PDF or image, max 10 MB.
                    @if ($isSuperAdmin)
                        Goes live in the shared library immediately.
                    @else
                        It's sent to the admin for approval before it joins the shared library.
                    @endif
                </p>
            </div>

            <form method="POST" action="{{ route('tutor.exam-papers.store') }}" enctype="multipart/form-data" class="space-y-5"
                  x-data="spinner('Connecting to server…')" @submit="start()">
                @csrf

                {{-- Title --}}
                <div>
                    <label class="block text-sm font-semibold text-ink" for="title">Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" required
                           placeholder="e.g. 2023 PSLE Maths Paper 1"
                           class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('title') border-danger @enderror">
                    @error('title') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>

                {{-- Level + Subject --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-ink" for="level">Level</label>
                        <select id="level" name="level" required
                                class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('level') border-danger @enderror">
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
                                class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('subject') border-danger @enderror">
                            <option value="" disabled @selected(! old('subject'))>Select subject…</option>
                            @foreach ($subjects as $s)
                                <option value="{{ $s }}" @selected(old('subject') === $s)>{{ $s }}</option>
                            @endforeach
                        </select>
                        @error('subject') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Year --}}
                <div>
                    <label class="block text-sm font-semibold text-ink" for="year">Year</label>
                    <input id="year" name="year" type="number" value="{{ old('year', now()->year) }}" required
                           min="1990" max="{{ now()->year + 2 }}"
                           class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('year') border-danger @enderror">
                    @error('year') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>

                {{-- Remarks --}}
                <div>
                    <label class="block text-sm font-semibold text-ink" for="remarks">
                        Remarks <span class="font-normal text-muted">(optional)</span>
                    </label>
                    <textarea id="remarks" name="remarks" rows="2"
                              placeholder="e.g. Includes answer key"
                              class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('remarks') border-danger @enderror">{{ old('remarks') }}</textarea>
                    @error('remarks') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>

                {{-- File --}}
                <div>
                    <label class="block text-sm font-semibold text-ink" for="file">
                        File <span class="font-normal text-muted">(PDF, JPG, PNG — max 10 MB)</span>
                    </label>
                    <input id="file" name="file" type="file" accept=".pdf,.jpg,.jpeg,.png" required
                           class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm
                                  file:mr-3 file:rounded-md file:border-0 file:bg-primary/10 file:px-3 file:py-1
                                  file:text-xs file:font-semibold file:text-primary hover:file:bg-primary/20
                                  @error('file') border-danger @enderror">
                    @error('file') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a href="{{ route('tutor.exam-papers.index') }}"
                       class="rounded-lg border border-gray-200 px-4 py-2.5 text-sm font-semibold text-ink hover:bg-gray-50 cursor-pointer">
                        Cancel
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-primary-dark cursor-pointer">
                        {{ $isSuperAdmin ? 'Upload' : 'Send for approval' }}
                    </button>
                </div>

                <x-spinner-overlay />
            </form>
        </div>
    </div>
</x-app-layout>
