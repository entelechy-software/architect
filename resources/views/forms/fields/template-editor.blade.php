@php /** @var \Entelechy\Architect\Forms\Fields\TemplateEditorField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-template-editor"
         wire:ignore
         x-data="architectTemplateEditor({ wireField: 'formData.{{ $field->getName() }}', availableVariables: @js($field->getAvailableVariables()) })">
        <textarea class="arch-input" rows="4" x-ref="input"></textarea>
        <div class="arch-template-editor__preview" x-ref="preview"></div>
    </div>
</x-architect::field-wrapper>
