@php $hw = $homework ?? null; @endphp

<div class="space-y-5">
    <!-- Title -->
    <div>
        <x-input-label for="title" value="Title" />
        <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                      :value="old('title', $hw?->title)" required autofocus />
        <x-input-error :messages="$errors->get('title')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <!-- Subject -->
        @php
            $subjects = config('wowlo.subjects');
            $currentSubject = old('subject', $hw?->subject);
        @endphp
        <div>
            <x-input-label for="subject" value="Subject" />
            <select id="subject" name="subject" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                <option value="" disabled @selected(! $currentSubject)>Select subject…</option>
                {{-- Keep an existing non-canonical value selectable so editing never silently changes it. --}}
                @if ($currentSubject && ! in_array($currentSubject, $subjects, true))
                    <option value="{{ $currentSubject }}" selected>{{ $currentSubject }}</option>
                @endif
                @foreach ($subjects as $s)
                    <option value="{{ $s }}" @selected($currentSubject === $s)>{{ $s }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('subject')" class="mt-2" />
        </div>

        <!-- Student -->
        <div>
            <x-input-label for="student_id" value="Assign to student" />
            <select id="student_id" name="student_id" required
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                <option value="">Select a student…</option>
                @foreach ($students as $student)
                    <option value="{{ $student->id }}" @selected(old('student_id', $hw?->student_id) == $student->id)>{{ $student->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('student_id')" class="mt-2" />
        </div>
    </div>

    <!-- Description -->
    <div>
        <x-input-label for="description" value="Description / instructions" />
        <textarea id="description" name="description" rows="5" required
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">{{ old('description', $hw?->description) }}</textarea>
        <x-input-error :messages="$errors->get('description')" class="mt-2" />
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <!-- Start date -->
        <div>
            <x-input-label for="start_date" value="Start date" />
            <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full"
                          :value="old('start_date', $hw?->start_date?->format('Y-m-d'))" required />
            <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
        </div>

        <!-- Due date -->
        <div>
            <x-input-label for="due_date" value="Due date" />
            <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full"
                          :value="old('due_date', $hw?->due_date?->format('Y-m-d'))" required />
            <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
        </div>
    </div>

    <!-- Attachment -->
    <div>
        <x-input-label for="attachment" value="Attachment (optional — PDF, image, or Word, max 25MB)" />
        @if ($hw?->hasAttachment())
            <p class="mt-1 text-sm text-muted">
                Current: <a href="{{ route('tutor.homework.download', $hw) }}" class="font-semibold text-primary-dark hover:underline">{{ $hw->attachment_name }}</a>
                — uploading a new file replaces it.
            </p>
        @endif
        <input id="attachment" name="attachment" type="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
               class="mt-1 block w-full text-sm text-ink file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary-dark hover:file:bg-primary/20 cursor-pointer">
        <x-input-error :messages="$errors->get('attachment')" class="mt-2" />
    </div>
</div>
