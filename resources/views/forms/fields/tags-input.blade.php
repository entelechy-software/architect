@php /** @var \Entelechy\Architect\Forms\Fields\TagsInput $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-tags-input"
         x-data="architectTagsInput({
            wireField: 'formData.{{ $field->getName() }}',
            suggestions: @js($field->getSuggestions()),
            allowCreate: {{ $field->getAllowCreate() ? 'true' : 'false' }},
         })">
        <div class="arch-tags-input__tags">
            <template x-for="(tag, index) in tags" :key="index">
                <span class="arch-badge" data-color="primary" data-variant="soft">
                    <span x-text="tag"></span>
                    <button type="button" @click="removeTag(index)">&times;</button>
                </span>
            </template>
        </div>
        <input type="text"
               class="arch-input"
               x-model="query"
               @keydown.enter.prevent="addFromQuery()"
               @keydown.backspace="query === '' && tags.length && removeTag(tags.length - 1)"
               list="tags-suggestions-{{ $field->getName() }}"
               placeholder="{{ $field->getPlaceholder() ?? __('Add a tag…') }}">
        <datalist id="tags-suggestions-{{ $field->getName() }}">
            <template x-for="s in suggestions" :key="s">
                <option :value="s"></option>
            </template>
        </datalist>
    </div>
</x-architect::field-wrapper>
