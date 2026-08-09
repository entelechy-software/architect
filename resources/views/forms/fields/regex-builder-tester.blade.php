@php /** @var \Entelechy\Architect\Forms\Fields\RegexBuilderTesterField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-regex-builder-tester"
         wire:ignore
         x-data="architectRegexTester({ wireField: 'formData.{{ $field->getName() }}', sampleText: @js($field->getSampleText() ?? '') })">
        <input type="text" class="arch-input arch-input--code" placeholder="{{ __('Pattern') }}" x-model="pattern" x-on:input="onPatternInput($event.target.value)">
        <input type="text" class="arch-input" placeholder="{{ __('Flags') }}" x-model="flags" x-on:input="onFlagsInput($event.target.value)">
        @if ($field->getSampleText() !== null)
            <p class="arch-regex-builder-tester__sample" x-ref="highlighted"></p>
            <ul class="arch-regex-builder-tester__groups" x-show="groups.length > 0">
                <template x-for="(group, index) in groups" :key="index">
                    <li x-text="`Match ${index + 1}: ` + group.filter(g => g !== undefined).join(', ')"></li>
                </template>
            </ul>
        @endif
    </div>
</x-architect::field-wrapper>
