@php /** @var \Entelechy\Architect\Forms\Fields\ButtonGroupField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-button-group" role="radiogroup" aria-label="{{ $field->getLabel() }}">
        @foreach ($field->getOptions($get) as $value => $label)
            <label class="arch-button-group__option">
                <input type="radio"
                       name="formData.{{ $field->getName() }}"
                       value="{{ $value }}"
                       wire:model="formData.{{ $field->getName() }}">
                <span class="arch-button">{{ $label }}</span>
            </label>
        @endforeach
    </div>
</x-architect::field-wrapper>
