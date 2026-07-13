@php /** @var \Entelechy\Architect\Forms\Fields\BlockEditor $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-block-editor arch-block-editor--layout"
         wire:ignore
         x-data="architectBlockEditor({ wireField: 'formData.{{ $field->getName() }}', blocks: @js($field->getBlocks()), layout: true })">
        <div x-ref="canvas"></div>
    </div>
</x-architect::field-wrapper>
