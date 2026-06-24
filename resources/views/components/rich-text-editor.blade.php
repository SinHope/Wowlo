@props([
    'name',                                                   // form field name for the (hidden) HTML value
    'value' => '',                                            // initial sanitised HTML
    'tools' => ['bold', 'italic', 'underline', 'strikeThrough'],
    'placeholder' => 'Type here…',
])

@php
    // Shared button classes; the colour-active state is applied via :class.
    $btn = 'grid h-8 w-8 place-items-center rounded cursor-pointer transition-colors';
@endphp

<div x-data="richEditor" class="space-y-2">
    {{-- Formatting toolbar. Active commands highlight (active.* from queryCommandState). --}}
    <div class="flex flex-wrap items-center gap-1 rounded-t-lg border border-b-0 border-gray-300 bg-gray-50 px-2 py-1.5">
        @if (in_array('bold', $tools))
            <button type="button" @click="cmd('bold')" title="Bold"
                    class="{{ $btn }} font-bold"
                    :class="active.bold ? 'bg-primary text-white' : 'text-ink hover:bg-gray-200'">B</button>
        @endif
        @if (in_array('italic', $tools))
            <button type="button" @click="cmd('italic')" title="Italic"
                    class="{{ $btn }} italic"
                    :class="active.italic ? 'bg-primary text-white' : 'text-ink hover:bg-gray-200'">I</button>
        @endif
        @if (in_array('underline', $tools))
            <button type="button" @click="cmd('underline')" title="Underline"
                    class="{{ $btn }} underline"
                    :class="active.underline ? 'bg-primary text-white' : 'text-ink hover:bg-gray-200'">U</button>
        @endif
        @if (in_array('strikeThrough', $tools))
            <button type="button" @click="cmd('strikeThrough')" title="Strikethrough"
                    class="{{ $btn }} line-through"
                    :class="active.strikeThrough ? 'bg-primary text-white' : 'text-ink hover:bg-gray-200'">S</button>
        @endif
        @if (in_array('bulletList', $tools))
            <button type="button" @click="cmd('insertUnorderedList')" title="Bullet list"
                    class="{{ $btn }}"
                    :class="active.insertUnorderedList ? 'bg-primary text-white' : 'text-ink hover:bg-gray-200'">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                </svg>
            </button>
        @endif

        @if (in_array('color', $tools) || in_array('link', $tools))
            <span class="mx-1 h-6 w-px bg-gray-300"></span>
        @endif

        @if (in_array('color', $tools))
            <label title="Font colour" class="relative {{ $btn }} hover:bg-gray-200">
                <svg class="h-4 w-4 text-ink" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
                </svg>
                <input type="color" @input="cmd('foreColor', $event.target.value)" value="#DC2626"
                       class="absolute h-0 w-0 opacity-0">
            </label>
        @endif

        @if (in_array('link', $tools))
            <button type="button" @click="addLink()" title="Add link" class="{{ $btn }} text-ink hover:bg-gray-200">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.7" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                </svg>
            </button>
        @endif
    </div>

    {{-- The editable surface. Initial HTML is the (sanitised) saved content. --}}
    <div x-ref="editor" contenteditable="true" @input="sync()" @blur="sync()" @keyup="refresh()" @mouseup="refresh()"
         data-placeholder="{{ $placeholder }}"
         class="rte-editor min-h-[8rem] rounded-b-lg border border-gray-300 px-3 py-2 text-sm leading-relaxed text-ink focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary [&_a]:text-primary-dark [&_a]:underline [&_ul]:list-disc [&_ul]:pl-6 [&_ol]:list-decimal [&_ol]:pl-6">{!! $value !!}</div>

    {{-- The real submitted value, kept in sync with the editor. --}}
    <input type="hidden" name="{{ $name }}" x-bind:value="content">

    @once
        <style>
            .rte-editor:empty:before {
                content: attr(data-placeholder);
                color: #9ca3af;
            }
        </style>
    @endonce
</div>
