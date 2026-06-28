@php /** @var \Entelechy\Architect\Forms\Fields\MarkdownEditor $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-markdown-editor"
         x-data="{ value: $wire.entangle('formData.{{ $field->getName() }}'), showPreview: false }">
        <div class="arch-markdown-editor__toolbar">
            @foreach ($field->getToolbar() as $tool)
                <button type="button" class="arch-markdown-editor__tool" data-tool="{{ $tool }}">{{ ucfirst($tool) }}</button>
            @endforeach
            <button type="button" class="arch-markdown-editor__preview-toggle" @click="showPreview = !showPreview" x-text="showPreview ? '{{ __('Edit') }}' : '{{ __('Preview') }}'"></button>
        </div>

        <textarea class="arch-textarea"
                  rows="8"
                  x-show="!showPreview"
                  x-model="value"></textarea>

        {{-- Client-side preview is plain text; previewUsing() runs server-side via FormEngine::renderMarkdownPreview() when present. --}}
        <div class="arch-markdown-editor__preview" x-show="showPreview" x-text="value"></div>
    </div>
</x-architect::field-wrapper>
