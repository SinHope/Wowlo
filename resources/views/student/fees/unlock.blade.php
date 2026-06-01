<x-app-layout>
    <x-slot name="header">Tuition Fee</x-slot>

    <div class="mx-auto max-w-md">
        <div class="rounded-2xl border border-gray-100 bg-white p-8 shadow-sm">
            <div class="text-center">
                <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-primary/10 text-primary-dark">
                    <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                </span>
                <h2 class="mt-4 text-xl font-extrabold text-ink">Fee section is locked</h2>
                <p class="mt-1 text-sm text-muted">This section is for parents. Please enter the fee password to continue.</p>
            </div>

            <form method="POST" action="{{ route('student.fees.unlock.attempt') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <x-input-label for="password" value="Fee password" />
                    <x-text-input id="password" name="password" type="password"
                                  class="mt-1 block w-full" required autofocus autocomplete="off" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <button type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-white transition-colors duration-200 hover:bg-primary-dark cursor-pointer">
                    Unlock
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
