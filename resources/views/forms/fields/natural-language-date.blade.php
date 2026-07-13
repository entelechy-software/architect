@php /** @var \Entelechy\Architect\Forms\Fields\NaturalLanguageDateField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <input type="text"
           id="field-{{ $field->getName() }}"
           class="arch-input"
           wire:model="formData.{{ $field->getName() }}"
           @if ($field->getPlaceholder() !== null) placeholder="{{ $field->getPlaceholder() }}" @else placeholder="{{ __('e.g. next Friday at 2pm') }}" @endif>
    {{-- Host app's parser populates this preview; kept visible/correctable per the reference document's guidance. --}}
    <p class="arch-field__preview" data-parsed-preview="formData.{{ $field->getName() }}"></p>
</x-architect::field-wrapper>
