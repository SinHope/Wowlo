{{-- Shared compose form for create + edit. Expects $banner (Banner|null). --}}
<div class="space-y-2">
    <label class="block text-sm font-semibold text-ink">Message</label>

    <x-rich-text-editor
        name="content"
        :value="old('content', $banner?->content ?? '')"
        :tools="['bold', 'italic', 'underline', 'strikeThrough', 'color', 'link']"
        placeholder="Type your announcement…" />

    @error('content')
        <p class="text-sm font-semibold text-danger">{{ $message }}</p>
    @enderror
</div>

<fieldset class="mt-6 space-y-2">
    <legend class="text-sm font-semibold text-ink">Who should see this?</legend>
    @foreach (config('wowlo.banner_audiences') as $value => $label)
        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-3 py-2.5 hover:bg-gray-50">
            <input type="radio" name="audience" value="{{ $value }}"
                   @checked(old('audience', $banner?->audience ?? 'everyone') === $value)
                   class="text-primary focus:ring-primary">
            <span class="text-sm font-semibold text-ink">{{ $label }}</span>
        </label>
    @endforeach
    @error('audience')
        <p class="text-sm font-semibold text-danger">{{ $message }}</p>
    @enderror
</fieldset>
