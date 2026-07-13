@php /** @var \Entelechy\Architect\Forms\Fields\SegmentedControlField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-segmented-control" role="radiogroup" aria-label="{{ $field->getLabel() }}">
        @foreach ($field->getOptions($get) as $value => $label)
            <label class="arch-segmented-control__option">
                <input type="radio"
                       name="formData.{{ $field->getName() }}"
                       value="{{ $value }}"
                       wire:model="formData.{{ $field->getName() }}">
                <span>{{ $label }}</span>
            </label>
        @endforeach
    </div>
</x-architect::field-wrapper>
