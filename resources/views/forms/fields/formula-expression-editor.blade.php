@php /** @var \Entelechy\Architect\Forms\Fields\FormulaExpressionEditorField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-formula-expression-editor"
         wire:ignore
         x-data="architectFormulaExpressionEditor({ wireField: 'formData.{{ $field->getName() }}', availableFields: @js($field->getAvailableFields()) })">
        <input type="text" class="arch-input arch-input--code" x-ref="input">
    </div>
</x-architect::field-wrapper>
