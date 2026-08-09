@php /** @var \Entelechy\Architect\Forms\Fields\MaskedInputField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <input type="text"
           id="field-{{ $field->getName() }}"
           class="arch-input"
           wire:ignore
           x-data="architectMaskedInput({ wireField: 'formData.{{ $field->getName() }}', mask: @js($field->getMask()) })"
           x-on:input="onInput($event)"
           @if ($field->getMask() !== null) data-mask="{{ $field->getMask() }}" @endif
           @if ($field->getPlaceholder() !== null) placeholder="{{ $field->getPlaceholder() }}" @elseif ($field->getMask() !== null) placeholder="{{ $field->getMask() }}" @endif>
</x-architect::field-wrapper>
