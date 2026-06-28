@php /** @var \Entelechy\Architect\Forms\Fields\KeyValue $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-key-value" x-data="architectKeyValue({ wireField: 'formData.{{ $field->getName() }}' })">
        <div class="arch-key-value__header">
            <span>{{ $field->getKeyLabel() }}</span>
            <span>{{ $field->getValueLabel() }}</span>
        </div>
        <template x-for="(row, index) in rows" :key="index">
            <div class="arch-key-value__row">
                <input type="text" class="arch-input" x-model="row.key">
                <input type="text" class="arch-input" x-model="row.value">
                <button type="button" class="arch-repeater__remove" @click="remove(index)">{{ __('Remove') }}</button>
            </div>
        </template>
        <button type="button" class="arch-button" data-variant="outline" data-color="primary" data-size="sm" @click="add()">
            {{ $field->getAddButtonLabel() }}
        </button>
    </div>
</x-architect::field-wrapper>
