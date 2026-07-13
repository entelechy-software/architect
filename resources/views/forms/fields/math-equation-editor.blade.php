@php /** @var \Entelechy\Architect\Forms\Fields\MathEquationEditorField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-math-equation-editor"
         wire:ignore
         x-data="architectMathEquationEditor({ wireField: 'formData.{{ $field->getName() }}' })">
        <div x-ref="editor"></div>
    </div>
</x-architect::field-wrapper>
