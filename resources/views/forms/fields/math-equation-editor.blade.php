@php /** @var \Entelechy\Architect\Forms\Fields\MathEquationEditorField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-math-equation-editor"
         wire:ignore
         x-data="architectMathEquationEditor({ wireField: 'formData.{{ $field->getName() }}' })">
        <div class="arch-math-equation-editor__palette">
            <button type="button" class="arch-button" data-variant="outline" data-color="primary" data-size="sm" x-on:click="insert('\\frac{}{}', -3)">{{ __('Fraction') }}</button>
            <button type="button" class="arch-button" data-variant="outline" data-color="primary" data-size="sm" x-on:click="insert('^{}', -1)">{{ __('Exponent') }}</button>
            <button type="button" class="arch-button" data-variant="outline" data-color="primary" data-size="sm" x-on:click="insert('\\sqrt{}', -1)">{{ __('Square root') }}</button>
            <button type="button" class="arch-button" data-variant="outline" data-color="primary" data-size="sm" x-on:click="insert('\\pi')">{{ __('π') }}</button>
        </div>
        <input type="text" class="arch-input arch-input--code" x-ref="editor" x-on:input="onInput($event.target.value)">
        <p class="arch-math-equation-editor__preview" x-text="value"></p>
    </div>
</x-architect::field-wrapper>
