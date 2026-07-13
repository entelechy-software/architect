@php /** @var \Entelechy\Architect\Forms\Fields\YesNoUnknownField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-yes-no-unknown" role="radiogroup" aria-label="{{ $field->getLabel() }}">
        <label>
            <input type="radio" name="formData.{{ $field->getName() }}" value="yes" wire:model="formData.{{ $field->getName() }}">
            <span>{{ $field->getYesLabel() }}</span>
        </label>
        <label>
            <input type="radio" name="formData.{{ $field->getName() }}" value="no" wire:model="formData.{{ $field->getName() }}">
            <span>{{ $field->getNoLabel() }}</span>
        </label>
        <label>
            <input type="radio" name="formData.{{ $field->getName() }}" value="unknown" wire:model="formData.{{ $field->getName() }}">
            <span>{{ $field->getUnknownLabel() }}</span>
        </label>
    </div>
</x-architect::field-wrapper>
