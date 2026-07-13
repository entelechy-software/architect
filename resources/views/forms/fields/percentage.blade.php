@php /** @var \Entelechy\Architect\Forms\Fields\PercentageField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-input-group">
        <input type="number"
               id="field-{{ $field->getName() }}"
               class="arch-input"
               step="{{ $field->getDecimals() > 0 ? '0.' . str_repeat('0', $field->getDecimals() - 1) . '1' : '1' }}"
               wire:model="formData.{{ $field->getName() }}"
               min="{{ $field->getMin() }}"
               max="{{ $field->getMax() }}"
               @if ($field->getPlaceholder() !== null) placeholder="{{ $field->getPlaceholder() }}" @endif>
        <span class="arch-input-group__suffix">%</span>
    </div>
</x-architect::field-wrapper>
