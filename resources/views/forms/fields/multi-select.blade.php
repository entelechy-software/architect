@php /** @var \Entelechy\Architect\Forms\Fields\MultiSelectField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <select id="field-{{ $field->getName() }}"
            class="arch-select"
            multiple
            wire:model="formData.{{ $field->getName() }}">
        @foreach ($field->getOptions($get) as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
</x-architect::field-wrapper>
