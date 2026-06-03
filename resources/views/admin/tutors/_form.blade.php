@php $isEdit = (bool) $tutor; @endphp

<div class="space-y-5">
    {{-- Name --}}
    <div>
        <label class="block text-sm font-semibold text-ink" for="name">Full name</label>
        <input id="name" name="name" type="text" value="{{ old('name', $tutor->name ?? '') }}" required
               class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('name') border-danger @enderror">
        @error('name') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
    </div>

    {{-- Email --}}
    <div>
        <label class="block text-sm font-semibold text-ink" for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ old('email', $tutor->email ?? '') }}" required
               class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('email') border-danger @enderror">
        <p class="mt-1 text-xs text-muted">They log in with this email (or Google, once linked).</p>
        @error('email') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
    </div>

    {{-- Password --}}
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-semibold text-ink" for="password">
                Password @if ($isEdit) <span class="font-normal text-muted">(leave blank to keep)</span> @endif
            </label>
            <input id="password" name="password" type="password" {{ $isEdit ? '' : 'required' }} autocomplete="new-password"
                   class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('password') border-danger @enderror">
            @error('password') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-semibold text-ink" for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" {{ $isEdit ? '' : 'required' }} autocomplete="new-password"
                   class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary">
        </div>
    </div>

    {{-- Phone (optional) --}}
    <div>
        <label class="block text-sm font-semibold text-ink" for="phone_1">
            Phone <span class="font-normal text-muted">(optional)</span>
        </label>
        <input id="phone_1" name="phone_1" type="text" value="{{ old('phone_1', $tutor->phone_1 ?? '') }}"
               class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('phone_1') border-danger @enderror">
        @error('phone_1') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
    </div>

    {{-- Address (optional) --}}
    <div>
        <label class="block text-sm font-semibold text-ink" for="address">
            Address <span class="font-normal text-muted">(optional)</span>
        </label>
        <textarea id="address" name="address" rows="2"
                  class="mt-1 block w-full rounded-lg border border-gray-200 px-3 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary @error('address') border-danger @enderror">{{ old('address', $tutor->address ?? '') }}</textarea>
        @error('address') <p class="mt-1 text-xs text-danger">{{ $message }}</p> @enderror
    </div>
</div>
