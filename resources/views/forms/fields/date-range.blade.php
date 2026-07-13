@php /** @var \Entelechy\Architect\Forms\Fields\DateRangeField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-date-range">
        <input type="text"
               id="field-{{ $field->getName() }}-start"
               class="arch-input"
               placeholder="{{ __('Start date') }}"
               wire:model="formData.{{ $field->getName() }}.start"
               @if ($field->getMinDate() !== null) data-min-date="{{ $field->getMinDate() }}" @endif
               @if ($field->getMaxDate() !== null) data-max-date="{{ $field->getMaxDate() }}" @endif>
        <span class="arch-date-range__separator">&rarr;</span>
        <input type="text"
               id="field-{{ $field->getName() }}-end"
               class="arch-input"
               placeholder="{{ __('End date') }}"
               wire:model="formData.{{ $field->getName() }}.end"
               @if ($field->getMinDate() !== null) data-min-date="{{ $field->getMinDate() }}" @endif
               @if ($field->getMaxDate() !== null) data-max-date="{{ $field->getMaxDate() }}" @endif>
    </div>
</x-architect::field-wrapper>
