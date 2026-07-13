@php /** @var \Entelechy\Architect\Forms\Fields\SchemaDrivenObjectEditorField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-schema-driven-object-editor"
         wire:ignore
         x-data="architectSchemaDrivenObjectEditor({ wireField: 'formData.{{ $field->getName() }}', schema: @js($field->getSchema()) })">
        <div x-ref="form"></div>
    </div>
</x-architect::field-wrapper>
