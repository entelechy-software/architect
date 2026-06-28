@php
/**
 * @var \Entelechy\Architect\Forms\Fields\Radio $field
 * @var \Closure(string): mixed $get
 */
@endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-radio-group" data-inline="{{ $field->isInline() ? 'true' : 'false' }}">
        @foreach ($field->getOptions($get) as $value => $optionLabel)
            <label class="arch-radio">
                <input type="radio"
                       class="arch-radio__input"
                       name="field-{{ $field->getName() }}"
                       value="{{ $value }}"
                       wire:model="formData.{{ $field->getName() }}">
                <span class="arch-radio__label">{{ $optionLabel }}</span>
            </label>
        @endforeach
    </div>
</x-architect::field-wrapper>
