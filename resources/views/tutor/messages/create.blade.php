<x-app-layout>
    <x-slot name="header">Compose Message</x-slot>

    <div class="mx-auto max-w-2xl space-y-5">

        <div class="flex items-center gap-3">
            <a href="{{ route('tutor.messages.index') }}" class="text-muted hover:text-ink" aria-label="Back">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </a>
            <h2 class="text-2xl font-extrabold text-ink">Compose Message</h2>
        </div>

        <form method="POST" action="{{ route('tutor.messages.store') }}"
              x-data="spinner('Sending message…')" @submit="start()"
              class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            @csrf

            <div class="space-y-5">
                <!-- Recipient -->
                <div>
                    <x-input-label for="receiver_id" value="Send to student" />
                    <select id="receiver_id" name="receiver_id" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">
                        <option value="">Select a student…</option>
                        @foreach ($students as $student)
                            <option value="{{ $student->id }}" @selected(old('receiver_id') == $student->id)>{{ $student->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('receiver_id')" class="mt-2" />
                </div>

                <!-- Subject -->
                <div>
                    <x-input-label for="subject" value="Subject" />
                    <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full"
                                  :value="old('subject')" required autofocus />
                    <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                </div>

                <!-- Body -->
                <div>
                    <x-input-label for="body" value="Message" />
                    <textarea id="body" name="body" rows="7" required
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">{{ old('body') }}</textarea>
                    <x-input-error :messages="$errors->get('body')" class="mt-2" />
                </div>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('tutor.messages.index') }}"
                   class="rounded-lg px-4 py-2.5 text-sm font-semibold text-muted hover:text-ink cursor-pointer">Cancel</a>
                <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                    Send
                </button>
            </div>

            <x-spinner-overlay />
        </form>
    </div>
</x-app-layout>
