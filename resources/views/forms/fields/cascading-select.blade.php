@php /** @var \Entelechy\Architect\Forms\Fields\CascadingSelectField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <select id="field-{{ $field->getName() }}"
            class="arch-select"
            wire:model="formData.{{ $field->getName() }}">
        <option value="">{{ __('Select…') }}</option>
        @foreach ($field->getOptions($get) as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
        @endforeach
    </select>
</x-architect::field-wrapper>
