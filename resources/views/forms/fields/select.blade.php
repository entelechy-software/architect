@php
/**
 * @var \Entelechy\Architect\Forms\Fields\SelectField $field
 * @var \Closure(string): mixed $get
 */
@endphp
<x-architect::field-wrapper :field="$field">
    <select id="field-{{ $field->getName() }}"
            class="arch-select"
            wire:model.live="formData.{{ $field->getName() }}">
        <option value="">{{ $field->getPlaceholder() ?? '— Select —' }}</option>
        @foreach ($field->getOptions($get) as $value => $optionLabel)
            <option value="{{ $value }}">{{ $optionLabel }}</option>
        @endforeach
    </select>
</x-architect::field-wrapper>
