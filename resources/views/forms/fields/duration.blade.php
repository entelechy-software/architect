@php /** @var \Entelechy\Architect\Forms\Fields\DurationField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <input type="number"
           id="field-{{ $field->getName() }}"
           class="arch-input"
           min="0"
           @if ($field->getMaxMinutes() !== null) max="{{ $field->getMaxMinutes() }}" @endif
           wire:model="formData.{{ $field->getName() }}">
    <span class="arch-input-group__suffix">{{ __('minutes') }}</span>
</x-architect::field-wrapper>
