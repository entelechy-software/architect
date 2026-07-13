@php /** @var \Entelechy\Architect\Forms\Fields\GradientEditorField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-gradient-editor"
         wire:ignore
         x-data="architectGradientEditor({ wireField: 'formData.{{ $field->getName() }}' })">
        <div class="arch-gradient-editor__preview" x-ref="preview"></div>
        <div class="arch-gradient-editor__stops" x-ref="stops"></div>
    </div>
</x-architect::field-wrapper>
