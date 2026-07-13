@php /** @var \Entelechy\Architect\Forms\Fields\QueryLanguageTextField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <textarea id="field-{{ $field->getName() }}"
              class="arch-input arch-input--code"
              rows="3"
              wire:model="formData.{{ $field->getName() }}"
              @if ($field->getPlaceholder() !== null) placeholder="{{ $field->getPlaceholder() }}" @endif></textarea>
</x-architect::field-wrapper>
