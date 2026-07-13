@php /** @var \Entelechy\Architect\Forms\Fields\RegexBuilderTesterField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-regex-builder-tester">
        <input type="text" class="arch-input arch-input--code" placeholder="{{ __('Pattern') }}" wire:model="formData.{{ $field->getName() }}.pattern">
        <input type="text" class="arch-input" placeholder="{{ __('Flags') }}" wire:model="formData.{{ $field->getName() }}.flags">
        @if ($field->getSampleText() !== null)
            <p class="arch-regex-builder-tester__sample">{{ $field->getSampleText() }}</p>
        @endif
    </div>
</x-architect::field-wrapper>
