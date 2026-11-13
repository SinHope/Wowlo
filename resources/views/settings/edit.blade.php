<x-app-layout>
    <x-slot name="header">Settings</x-slot>

    <form method="POST" action="{{ route('settings.update') }}"
          x-data="{
              theme: @js($user->theme),
              bg: @js($user->app_background ?? ''),
              // Slider shows transparency (0 = solid); the server stores opacity (100 = solid).
              see: {{ 100 - (int) $user->card_opacity }},
              maxSee: {{ 100 - (int) config('wowlo.card_opacity_min') }},
          }"
          class="mx-auto max-w-4xl space-y-6">
        @csrf
        @method('PATCH')

        @if (session('status') === 'settings-updated')
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" x-cloak
                 class="rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm font-semibold text-success">
                Settings saved.
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm font-semibold text-danger">
                {{ $errors->first() }}
            </div>
        @endif

        {{-- Appearance: light / dark / system --}}
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold text-ink">Appearance</h2>
            <p class="mt-1 text-sm text-muted">Choose how Wowlo looks. "System" follows your device's light or dark setting.</p>

            @php
                $themes = [
                    'light'  => ['Light',  'M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z'],
                    'dark'   => ['Dark',   'M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z'],
                    'system' => ['System', 'M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25m18 0A2.25 2.25 0 0 0 18.75 3H5.25A2.25 2.25 0 0 0 3 5.25m18 0V12a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 12V5.25'],
                ];
            @endphp
            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3" role="radiogroup" aria-label="Theme">
                @foreach ($themes as $value => [$label, $icon])
                    <label class="cursor-pointer">
                        <input type="radio" name="theme" value="{{ $value }}" x-model="theme"
                               @change="wowloAppearance.theme('{{ $value }}')" class="peer sr-only">
                        <span class="flex items-center gap-3 rounded-xl border-2 border-gray-200 bg-white px-4 py-3 font-semibold text-ink transition-colors hover:bg-primary/5 peer-checked:border-primary peer-checked:bg-primary/10 peer-focus-visible:ring-2 peer-focus-visible:ring-primary">
                            <svg class="h-6 w-6 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                            </svg>
                            {{ $label }}
                        </span>
                    </label>
                @endforeach
            </div>
        </section>

        {{-- App background --}}
        <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm sm:p-6">
            <h2 class="text-lg font-bold text-ink">Background</h2>
            <p class="mt-1 text-sm text-muted">Pick a picture to show behind the app, or keep it plain.</p>

            <label class="mt-4 inline-flex cursor-pointer">
                <input type="radio" name="app_background" value="" x-model="bg"
                       @change="wowloAppearance.bg(null)" class="peer sr-only">
                <span class="rounded-xl border-2 border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-ink transition-colors hover:bg-primary/5 peer-checked:border-primary peer-checked:bg-primary/10 peer-focus-visible:ring-2 peer-focus-visible:ring-primary">
                    No background
                </span>
            </label>

            {{-- Card transparency: lets the picture show through cards, sidebar and header. --}}
            <div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 p-4" :class="! bg && 'opacity-60'">
                <div class="flex items-center justify-between gap-3">
                    <label for="card-see" class="text-sm font-bold text-ink">Card transparency</label>
                    <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-bold text-primary-dark" x-text="see === 0 ? 'Solid' : see + '%'"></span>
                </div>
                <p class="mt-1 text-xs text-muted" x-show="bg">Drag right to see more of your background through the cards.</p>
                <p class="mt-1 text-xs text-muted" x-show="! bg" x-cloak>Pick a background first. This only applies when one is set.</p>
                <div class="mt-3 flex items-center gap-3">
                    <span class="text-xs font-semibold text-muted">Solid</span>
                    <input id="card-see" type="range" min="0" :max="maxSee" step="5" x-model.number="see"
                           @input="wowloAppearance.opacity(100 - see)" :disabled="! bg"
                           :aria-valuetext="see === 0 ? 'Solid' : see + ' percent transparent'"
                           class="h-2 w-full cursor-pointer accent-primary disabled:cursor-not-allowed">
                    <span class="text-xs font-semibold text-muted">See-through</span>
                </div>
                <input type="hidden" name="card_opacity" :value="100 - see" value="{{ (int) $user->card_opacity }}">
            </div>

            @forelse ($backgrounds as $category => $images)
                <h3 class="mt-6 text-sm font-bold uppercase tracking-wide text-muted">{{ $category }}</h3>
                @if (count($images))
                    <div class="mt-2 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($images as $img)
                            <label class="cursor-pointer">
                                <input type="radio" name="app_background" value="{{ $img['path'] }}" x-model="bg"
                                       @change="wowloAppearance.bg(@js($img['url'])); wowloAppearance.opacity(100 - see)" class="peer sr-only">
                                <span class="block overflow-hidden rounded-xl border-2 border-gray-200 transition peer-checked:border-primary peer-checked:ring-2 peer-checked:ring-primary peer-focus-visible:ring-2 peer-focus-visible:ring-primary">
                                    <img src="{{ $img['url'] }}" alt="{{ $img['name'] }}" loading="lazy" class="aspect-video w-full object-cover">
                                </span>
                                <span class="mt-1 block truncate text-xs text-muted">{{ $img['name'] }}</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <p class="mt-2 text-sm text-muted">No images in this category yet.</p>
                @endif
            @empty
                <p class="mt-6 text-sm text-muted">No backgrounds available yet.</p>
            @endforelse
        </section>

        <div class="flex justify-end">
            <button type="submit"
                    class="cursor-pointer rounded-lg bg-primary px-5 py-2.5 font-semibold text-white hover:bg-primary-dark">
                Save settings
            </button>
        </div>
    </form>
</x-app-layout>
