@php
    /** @var \Entelechy\Architect\Forms\Fields\Repeater $field */
    // NOTE: row sub-fields render via a minimal inline input switch rather
    // than the full field-view system — recursive re-use of arbitrary field
    // Blade views (which bind to a fixed `formData.{name}` path) is deferred
    // to Phase 8 alongside conditional/nested structure support.
@endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-repeater"
         x-data="architectRepeater({
            wireField: 'formData.{{ $field->getName() }}',
            minItems: {{ $field->getMinItems() ?? 'null' }},
            maxItems: {{ $field->getMaxItems() ?? 'null' }},
         })">
        <template x-for="(row, index) in items" :key="index">
            <div class="arch-repeater__row">
                <div class="arch-repeater__row-fields">
                    @foreach ($field->getStructure() as $subField)
                        <label class="arch-repeater__sub-field">
                            <span class="arch-field__label">{{ method_exists($subField, 'getLabel') ? $subField->getLabel() : '' }}</span>
                            <input type="text"
                                   class="arch-input"
                                   x-model="row.{{ method_exists($subField, 'getName') ? $subField->getName() : '' }}">
                        </label>
                    @endforeach
                </div>
                <button type="button" class="arch-repeater__remove" @click="remove(index)">{{ __('Remove') }}</button>
            </div>
        </template>

        <button type="button" class="arch-button" data-variant="outline" data-color="primary" data-size="sm" @click="add()">
            {{ $field->getAddButtonLabel() }}
        </button>
    </div>
</x-architect::field-wrapper>
