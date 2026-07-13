@php /** @var \Entelechy\Architect\Forms\Fields\CurrencyField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-input-group">
        <span class="arch-input-group__prefix">{{ $field->getCurrency() }}</span>
        <input type="number"
               id="field-{{ $field->getName() }}"
               class="arch-input"
               step="{{ $field->getDecimals() > 0 ? '0.' . str_repeat('0', $field->getDecimals() - 1) . '1' : '1' }}"
               wire:model="formData.{{ $field->getName() }}"
               @if ($field->getPlaceholder() !== null) placeholder="{{ $field->getPlaceholder() }}" @endif
               @if ($field->getMin() !== null) min="{{ $field->getMin() }}" @endif
               @if ($field->getMax() !== null) max="{{ $field->getMax() }}" @endif>
    </div>
</x-architect::field-wrapper>
