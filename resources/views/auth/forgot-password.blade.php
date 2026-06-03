<x-guest-layout>
    <a href="{{ route('login') }}" class="inline-flex items-center gap-1 mb-4 text-sm font-semibold text-muted hover:text-primary-dark">
        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
        Back to login
    </a>

    <div class="mb-4 text-sm text-gray-600">
        {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
    </div>

    <!-- Success message (shown after the reset link is sent) -->
    @if (session('status'))
        <div x-data="{ show: true }" x-show="show" x-transition
             class="mb-4 flex items-center justify-between rounded-lg border border-success/30 bg-success/10 px-4 py-3">
            <p class="text-sm font-semibold text-success">Password reset link sent successfully. Please check your email.</p>
            <button @click="show = false" class="text-success cursor-pointer" aria-label="Dismiss">&times;</button>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}"
          x-data="spinner('Emailing Reset Link…')" @submit="start()">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-primary-button>
                {{ __('Email Password Reset Link') }}
            </x-primary-button>
        </div>

        <x-spinner-overlay />
    </form>
</x-guest-layout>
