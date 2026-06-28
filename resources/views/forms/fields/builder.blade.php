@php
    /** @var \Entelechy\Architect\Forms\Fields\Builder $field */
    // NOTE: see repeater.blade.php — block sub-fields use the same minimal
    // inline input switch pending Phase 8 nested-structure support.
    $blockOptions = collect($field->getBlocks())->mapWithKeys(fn ($block) => [$block->getName() => $block->getLabel()]);
@endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-builder"
         x-data="architectRepeater({ wireField: 'formData.{{ $field->getName() }}' })">
        <template x-for="(row, index) in items" :key="index">
            <div class="arch-builder__block">
                <div class="arch-builder__block-header">
                    <span class="arch-badge" data-color="primary" data-variant="soft" x-text="row.__type"></span>
                    <button type="button" class="arch-repeater__remove" @click="remove(index)">{{ __('Remove') }}</button>
                </div>

                @foreach ($field->getBlocks() as $block)
                    <div class="arch-builder__block-fields" x-show="row.__type === '{{ $block->getName() }}'">
                        @foreach ($block->getStructure() as $subField)
                            <label class="arch-repeater__sub-field">
                                <span class="arch-field__label">{{ method_exists($subField, 'getLabel') ? $subField->getLabel() : '' }}</span>
                                <input type="text"
                                       class="arch-input"
                                       x-model="row.{{ method_exists($subField, 'getName') ? $subField->getName() : '' }}">
                            </label>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </template>

        <div class="arch-builder__add-menu">
            @foreach ($field->getBlocks() as $block)
                <button type="button" class="arch-button" data-variant="outline" data-color="primary" data-size="sm"
                        @click="add({ __type: '{{ $block->getName() }}' })">
                    {{ __('Add') }} {{ $block->getLabel() }}
                </button>
            @endforeach
        </div>
    </div>
</x-architect::field-wrapper>
