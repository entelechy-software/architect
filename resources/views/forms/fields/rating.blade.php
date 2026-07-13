@php /** @var \Entelechy\Architect\Forms\Fields\RatingField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-rating" role="radiogroup" aria-label="{{ $field->getLabel() }}">
        @for ($value = 1; $value <= $field->getMax(); $value++)
            <label class="arch-rating__star">
                <input type="radio"
                       name="formData.{{ $field->getName() }}"
                       value="{{ $value }}"
                       wire:model="formData.{{ $field->getName() }}"
                       class="arch-rating__input">
                <span aria-hidden="true">&#9733;</span>
            </label>
        @endfor
    </div>
</x-architect::field-wrapper>
