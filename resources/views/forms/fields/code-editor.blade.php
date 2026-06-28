@php /** @var \Entelechy\Architect\Forms\Fields\CodeEditor $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-code-editor"
         wire:ignore
         style="height: {{ $field->getHeight() }}"
         x-data="architectCodeEditor({
            wireField: 'formData.{{ $field->getName() }}',
            language: '{{ $field->getLanguage() }}',
            theme: '{{ $field->getTheme() }}',
         })">
        <div x-ref="editor" style="height: 100%"></div>
    </div>
</x-architect::field-wrapper>
