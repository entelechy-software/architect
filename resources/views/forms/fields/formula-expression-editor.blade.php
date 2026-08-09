@php /** @var \Entelechy\Architect\Forms\Fields\FormulaExpressionEditorField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-formula-expression-editor"
         wire:ignore
         x-data="architectFormulaExpressionEditor({ wireField: 'formData.{{ $field->getName() }}', availableFields: @js($field->getAvailableFields()) })">
        @if ($field->getAvailableFields() !== [])
            <div class="arch-formula-expression-editor__fields">
                @foreach ($field->getAvailableFields() as $available)
                    <button type="button" class="arch-chip" x-on:click="insertField('{{ $available }}')">{{ $available }}</button>
                @endforeach
            </div>
        @endif
        <input type="text" class="arch-input arch-input--code" x-ref="input" x-on:input="onInput($event.target.value)">
        <p class="arch-formula-expression-editor__preview" x-ref="preview"></p>
    </div>
</x-architect::field-wrapper>
