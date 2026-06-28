@php /** @var \Entelechy\Architect\Forms\Fields\IntegerField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <input type="number"
           id="field-{{ $field->getName() }}"
           class="arch-input"
           step="1"
           wire:model="formData.{{ $field->getName() }}"
           @if ($field->getPlaceholder() !== null) placeholder="{{ $field->getPlaceholder() }}" @endif
           @if ($field->getMin() !== null) min="{{ $field->getMin() }}" @endif
           @if ($field->getMax() !== null) max="{{ $field->getMax() }}" @endif>
</x-architect::field-wrapper>
