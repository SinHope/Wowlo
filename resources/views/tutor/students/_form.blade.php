@php $editing = isset($student) && $student; @endphp

<div class="space-y-5">
    <!-- Name -->
    <div>
        <x-input-label for="name" value="Full name" />
        <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                      :value="old('name', $student?->name)" required autofocus />
        <x-input-error :messages="$errors->get('name')" class="mt-2" />
    </div>

    <!-- Email -->
    <div>
        <x-input-label for="email" value="Email (used to log in)" />
        <x-text-input id="email" name="email" type="email" class="mt-1 block w-full"
                      :value="old('email', $student?->email)" required autocomplete="off" />
        <x-input-error :messages="$errors->get('email')" class="mt-2" />
    </div>

    <!-- Password -->
    <div>
        <x-input-label for="password" :value="$editing ? 'Password (leave blank to keep current)' : 'Password'" />
        <x-text-input id="password" name="password" type="password" class="mt-1 block w-full"
                      autocomplete="new-password" :required="! $editing" />
        <x-input-error :messages="$errors->get('password')" class="mt-2" />
    </div>
    <div>
        <x-input-label for="password_confirmation" value="Confirm password" />
        <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                      class="mt-1 block w-full" autocomplete="new-password" :required="! $editing" />
    </div>

    <!-- Phone numbers -->
    <div>
        <p class="text-sm font-semibold text-ink">Phone numbers</p>
        <p class="mb-2 text-xs text-muted">At least one is required.</p>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <x-input-label for="phone_1" value="Student" />
                <x-text-input id="phone_1" name="phone_1" type="tel" class="mt-1 block w-full" :value="old('phone_1', $student?->phone_1)" />
            </div>
            <div>
                <x-input-label for="phone_2" value="Father" />
                <x-text-input id="phone_2" name="phone_2" type="tel" class="mt-1 block w-full" :value="old('phone_2', $student?->phone_2)" />
            </div>
            <div>
                <x-input-label for="phone_3" value="Mother" />
                <x-text-input id="phone_3" name="phone_3" type="tel" class="mt-1 block w-full" :value="old('phone_3', $student?->phone_3)" />
            </div>
            <div>
                <x-input-label for="phone_4" value="Next of kin" />
                <x-text-input id="phone_4" name="phone_4" type="tel" class="mt-1 block w-full" :value="old('phone_4', $student?->phone_4)" />
            </div>
            <div>
                <x-input-label for="phone_5" value="Home (optional)" />
                <x-text-input id="phone_5" name="phone_5" type="tel" class="mt-1 block w-full" :value="old('phone_5', $student?->phone_5)" />
            </div>
        </div>
        <x-input-error :messages="$errors->get('phone_1')" class="mt-2" />
    </div>

    <!-- Address -->
    <div>
        <x-input-label for="address" value="Address" />
        <textarea id="address" name="address" rows="3"
                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-primary focus:ring-primary">{{ old('address', $student?->address) }}</textarea>
        <x-input-error :messages="$errors->get('address')" class="mt-2" />
    </div>
</div>
