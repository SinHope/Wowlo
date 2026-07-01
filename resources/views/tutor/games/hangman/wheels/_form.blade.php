@php
    // Repopulate on validation error, else the wheel's slices (edit) or two blank rows (create).
    $initialSlices = old('slices', $wheel?->slices ?? ['', '']);
    $initialSlices = array_values($initialSlices);
    if (count($initialSlices) < 2) {
        $initialSlices = array_pad($initialSlices, 2, '');
    }
    $initialStandard = (bool) old('is_standard', $wheel?->isStandard() ?? false);
@endphp

<div x-data="wheelForm(@js($initialSlices), {{ $maxSlices }})"
     @keydown.enter="if ($event.target.tagName === 'INPUT') $event.preventDefault()">

    {{-- name --}}
    <div>
        <x-input-label for="name" value="Wheel name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $wheel?->name)" maxlength="80" required autofocus
                      placeholder="e.g. Panda's Lucky Wheel" />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    {{-- standard toggle (super_admin only) --}}
    @if ($canStandard)
        <label class="mt-5 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 cursor-pointer">
            <input type="checkbox" name="is_standard" value="1" @checked($initialStandard)
                   @if($wheel && $wheel->isStandard()) checked disabled @endif
                   class="mt-0.5 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
            <span class="text-sm">
                <span class="font-extrabold text-amber-800">Make this a standard wheel</span>
                <span class="mt-0.5 block text-amber-700">Standard wheels are available to <strong>everyone</strong> on Wowlo. Only you (the super admin) can create or edit them.</span>
            </span>
        </label>
        @if ($wheel && $wheel->isStandard())
            <input type="hidden" name="is_standard" value="1">
        @endif
    @endif

    {{-- slices --}}
    <div class="mt-6">
        <div class="flex items-center justify-between">
            <x-input-label value="Wheel slices" />
            <span class="text-xs font-semibold text-muted"><span x-text="slices.length"></span> / {{ $maxSlices }}</span>
        </div>
        <p class="mt-1 text-xs text-muted">Each slice is a short message a student can land on. Need at least 2.</p>

        <div class="mt-3 space-y-2">
            <template x-for="(slice, i) in slices" :key="i">
                <div class="flex items-center gap-2">
                    <span class="w-6 shrink-0 text-right text-xs font-bold text-muted" x-text="(i + 1) + '.'"></span>
                    <input type="text" :name="`slices[${i}]`" x-model="slices[i]" maxlength="{{ $sliceLength }}"
                           placeholder="e.g. +1 Free Guess"
                           class="block w-full rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary focus:ring-primary">
                    <button type="button" @click="removeSlice(i)" x-show="slices.length > 2"
                            class="shrink-0 rounded-lg p-2 text-muted transition-colors hover:bg-danger/10 hover:text-danger cursor-pointer" title="Remove slice">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2.2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14"/></svg>
                    </button>
                </div>
            </template>
        </div>

        <button type="button" @click="addSlice()" x-show="slices.length < {{ $maxSlices }}"
                class="mt-3 inline-flex items-center gap-1.5 rounded-xl border-2 border-dashed border-gray-300 px-3 py-2 text-sm font-bold text-muted transition-colors hover:border-primary hover:text-primary-dark cursor-pointer">
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add slice
        </button>

        <x-input-error :messages="$errors->get('slices')" class="mt-2" />
        <x-input-error :messages="$errors->get('slices.*')" class="mt-2" />
    </div>

    {{-- suggestions --}}
    <div class="mt-6 rounded-xl bg-gray-50 p-4">
        <p class="text-xs font-extrabold uppercase tracking-wide text-muted">Need ideas? Tap to add</p>
        <div class="mt-2 flex flex-wrap gap-1.5">
            @foreach ($suggestions as $s)
                <button type="button" @click="addSuggestion(@js($s))"
                        class="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-semibold text-gray-700 transition-colors hover:border-primary hover:text-primary-dark cursor-pointer">
                    {{ $s }}
                </button>
            @endforeach
        </div>
    </div>

    <script>
        function wheelForm(initialSlices, maxSlices) {
            return {
                slices: initialSlices.length ? [...initialSlices] : ['', ''],
                maxSlices,
                addSlice() {
                    if (this.slices.length < this.maxSlices) this.slices.push('');
                },
                removeSlice(i) {
                    if (this.slices.length > 2) this.slices.splice(i, 1);
                },
                addSuggestion(text) {
                    // Reuse a trailing blank slice if there is one, else add a new slice.
                    const blank = this.slices.findIndex((s) => s.trim() === '');
                    if (blank !== -1) {
                        this.slices[blank] = text;
                    } else if (this.slices.length < this.maxSlices) {
                        this.slices.push(text);
                    }
                },
            };
        }
    </script>
</div>
