@php /** @var \Entelechy\Architect\Forms\Fields\DecimalField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <input type="number"
           id="field-{{ $field->getName() }}"
           class="arch-input"
           step="{{ $field->getStep() }}"
           wire:model="formData.{{ $field->getName() }}"
           @if ($field->getPlaceholder() !== null) placeholder="{{ $field->getPlaceholder() }}" @endif>
</x-architect::field-wrapper>
