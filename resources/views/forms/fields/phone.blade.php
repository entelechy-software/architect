@php /** @var \Entelechy\Architect\Forms\Fields\PhoneField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <input type="tel"
           id="field-{{ $field->getName() }}"
           class="arch-input"
           wire:model="formData.{{ $field->getName() }}"
           @if ($field->getDefaultCountry() !== null) data-default-country="{{ $field->getDefaultCountry() }}" @endif
           @if ($field->getPlaceholder() !== null) placeholder="{{ $field->getPlaceholder() }}" @else placeholder="+44 7000 000000" @endif>
</x-architect::field-wrapper>
