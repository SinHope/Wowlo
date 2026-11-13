<x-app-layout>
    <x-slot name="header">Message my tutor</x-slot>

    @php
        $limit = $quota['limit'];
        $remaining = $quota['remaining'];
        $refillsAt = $quota['resets_at']?->format('g:i A');
        // ok → violet, last one → amber, none left → red (status by text + colour).
        $state = $remaining === 0 ? 'none' : ($remaining === 1 ? 'last' : 'ok');
    @endphp

    <div class="mx-auto max-w-2xl space-y-5">

        <div class="flex items-center gap-3">
            <a href="{{ route('student.messages.index') }}" class="text-muted hover:text-ink" aria-label="Back">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </a>
            <h2 class="text-2xl font-extrabold text-ink">Message my tutor</h2>
        </div>

        <!-- Messages left this hour -->
        <div @class([
            'rounded-2xl border p-4',
            'border-primary/20 bg-primary/5' => $state === 'ok',
            'border-accent/30 bg-accent/10' => $state === 'last',
            'border-danger/30 bg-danger/5' => $state === 'none',
        ])>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                    <svg @class([
                        'h-5 w-5 shrink-0',
                        'text-primary-dark' => $state === 'ok',
                        'text-accent-dark' => $state === 'last',
                        'text-danger' => $state === 'none',
                    ]) fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                    <p @class([
                        'text-sm font-bold',
                        'text-primary-dark' => $state === 'ok',
                        'text-accent-dark' => $state === 'last',
                        'text-danger' => $state === 'none',
                    ])>{{ $remaining }} of {{ $limit }} messages left this hour</p>
                </div>
                <div class="flex gap-1.5" aria-hidden="true">
                    @for ($i = 0; $i < $limit; $i++)
                        <span @class([
                            'h-3 w-3 rounded-full',
                            'bg-primary' => $i < $remaining && $state === 'ok',
                            'bg-accent' => $i < $remaining && $state === 'last',
                            'bg-gray-200' => $i >= $remaining,
                        ])></span>
                    @endfor
                </div>
            </div>

            <p class="mt-2 text-sm text-ink">
                @if ($state === 'none')
                    You've used all {{ $limit }} messages for this hour. You can send again at <span class="font-semibold">{{ $refillsAt }}</span>.
                @elseif ($state === 'last')
                    This is your last message for this hour, so make it count.
                    You'll have {{ $limit }} again at <span class="font-semibold">{{ $refillsAt }}</span>.
                @elseif ($refillsAt)
                    You'll have {{ $limit }} again at <span class="font-semibold">{{ $refillsAt }}</span>.
                @else
                    You can send {{ $limit }} messages each hour, so try to say everything in one message.
                @endif
            </p>

            @if ($errors->has('limit'))
                <p class="mt-2 text-sm font-semibold text-danger">Your message wasn't sent. It's still in the box below, so you can copy it or send it later.</p>
            @endif
        </div>

        <form method="POST" action="{{ route('student.messages.store') }}"
              x-data="spinner('Sending message…')" @submit="start()"
              @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()"
              class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
            @csrf

            <fieldset @disabled($remaining === 0) class="space-y-5">
                <!-- Recipient is always the student's own tutor (set server-side). -->
                <div>
                    <p class="text-sm font-medium text-gray-700">To</p>
                    <p class="mt-1 font-semibold text-ink">{{ $tutor->name }} <span class="text-sm font-normal text-muted">(your tutor)</span></p>
                </div>

                @if ($replyTo)
                    <div class="rounded-xl border border-gray-100 bg-cream p-3">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted">Replying to</p>
                        <p class="mt-0.5 font-semibold text-ink">{{ $replyTo->subject }}</p>
                        <p class="mt-1 line-clamp-3 whitespace-pre-line text-sm text-muted">{{ $replyTo->body }}</p>
                    </div>
                @endif

                <!-- Subject -->
                <div>
                    <x-input-label for="subject" value="Subject" />
                    <x-text-input id="subject" name="subject" type="text" class="mt-1 block w-full"
                                  :value="old('subject', $replyTo?->replySubject())" maxlength="255" required
                                  placeholder="e.g. Question about my Maths homework" :autofocus="! $replyTo" />
                    <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                </div>

                <!-- Body -->
                <div>
                    <x-input-label for="body" value="Message" />
                    <textarea id="body" name="body" rows="8" maxlength="5000" required @if ($replyTo) autofocus @endif
                              placeholder="Take a moment to think before you send. Put all your questions and thoughts together in this one message, e.g. which homework or question it's about and what you've tried so far."
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm placeholder:text-gray-400 focus:border-primary focus:ring-primary disabled:bg-gray-50">{{ old('body') }}</textarea>
                    <x-input-error :messages="$errors->get('body')" class="mt-2" />
                </div>
            </fieldset>

            <div class="mt-6 flex items-center justify-end gap-3">
                <a href="{{ route('student.messages.index') }}"
                   class="rounded-lg px-4 py-2.5 text-sm font-semibold text-muted hover:text-ink cursor-pointer">Cancel</a>
                <button type="submit" @disabled($remaining === 0)
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-primary">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                    Send
                </button>
            </div>

            <x-spinner-overlay />
        </form>
    </div>
</x-app-layout>
