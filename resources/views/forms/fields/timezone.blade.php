@php /** @var \Entelechy\Architect\Forms\Fields\TimezoneField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <select id="field-{{ $field->getName() }}" class="arch-select" wire:model="formData.{{ $field->getName() }}">
        <option value="">{{ __('Select timezone…') }}</option>
        @foreach ($field->getTimezones() as $tz)
            <option value="{{ $tz }}">{{ $tz }}</option>
        @endforeach
    </select>
</x-architect::field-wrapper>
