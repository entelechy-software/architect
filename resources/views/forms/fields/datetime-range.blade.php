@php /** @var \Entelechy\Architect\Forms\Fields\DateTimeRangeField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-datetime-range">
        <input type="text" class="arch-input" placeholder="{{ __('Start') }}" wire:model="formData.{{ $field->getName() }}.start">
        <span class="arch-datetime-range__separator">&rarr;</span>
        <input type="text" class="arch-input" placeholder="{{ __('End') }}" wire:model="formData.{{ $field->getName() }}.end">
    </div>
</x-architect::field-wrapper>
