{{-- Shared compose form for create + edit. Expects $note (PatchNote|null). --}}
<div class="space-y-5">
    <div>
        <label for="title" class="block text-sm font-semibold text-ink">Title</label>
        <input id="title" name="title" type="text" value="{{ old('title', $note?->title) }}"
               class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary"
               placeholder="e.g. New domain — please reinstall">
        @error('title')
            <p class="mt-1 text-sm font-semibold text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="version" class="block text-sm font-semibold text-ink">Version <span class="font-normal text-muted">(optional)</span></label>
        <input id="version" name="version" type="text" value="{{ old('version', $note?->version) }}"
               class="mt-1 w-full rounded-lg border-gray-300 text-sm focus:border-primary focus:ring-primary"
               placeholder="e.g. v2.3 or June 2026">
        @error('version')
            <p class="mt-1 text-sm font-semibold text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-ink">What changed</label>
        <x-rich-text-editor
            name="body"
            :value="old('body', $note?->body ?? '')"
            :tools="['bold', 'italic', 'underline', 'strikeThrough', 'bulletList']"
            placeholder="Describe the update…" />
        @error('body')
            <p class="mt-1 text-sm font-semibold text-danger">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="image" class="block text-sm font-semibold text-ink">Image <span class="font-normal text-muted">(optional, max 4 MB)</span></label>

        @if ($note?->hasImage())
            <div class="mt-2 flex items-center gap-3">
                <img src="{{ route('patch-notes.image', $note) }}" alt="Current image"
                     class="h-20 w-20 rounded-lg border border-gray-200 object-cover">
                <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-danger">
                    <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300 text-danger focus:ring-danger">
                    Remove current image
                </label>
            </div>
        @endif

        <input id="image" name="image" type="file" accept="image/*"
               class="mt-2 block w-full text-sm text-muted file:mr-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-primary-dark hover:file:bg-primary/20">
        @error('image')
            <p class="mt-1 text-sm font-semibold text-danger">{{ $message }}</p>
        @enderror
    </div>
</div>
