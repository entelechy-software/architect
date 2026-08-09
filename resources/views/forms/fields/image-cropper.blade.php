@php /** @var \Entelechy\Architect\Forms\Fields\ImageCropperField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-image-cropper"
         x-data="architectImageCropper({ wireField: 'formData.{{ $field->getName() }}', aspectRatio: @js($field->getAspectRatio()) })">
        <input type="file" x-ref="input" wire:model="formData.{{ $field->getName() }}" x-on:change="onFileSelected($event)" @if ($field->getAccept() !== null) accept="{{ $field->getAccept() }}" @else accept="image/*" @endif>
        <div class="arch-image-cropper__canvas" x-ref="canvas" wire:ignore></div>
        <div class="arch-image-cropper__actions" x-show="cropping" x-cloak>
            <button type="button" class="arch-button" data-variant="solid" data-color="primary" x-on:click="applyCrop($refs.input)">{{ __('Apply crop') }}</button>
            <button type="button" class="arch-button" data-variant="ghost" x-on:click="cancelCrop()">{{ __('Cancel') }}</button>
        </div>
    </div>
</x-architect::field-wrapper>
