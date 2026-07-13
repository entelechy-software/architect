@php /** @var \Entelechy\Architect\Forms\Fields\ImageCropperField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-image-cropper"
         wire:ignore
         x-data="architectImageCropper({ wireField: 'formData.{{ $field->getName() }}', aspectRatio: @js($field->getAspectRatio()) })">
        <input type="file" wire:model="formData.{{ $field->getName() }}" @if ($field->getAccept() !== null) accept="{{ $field->getAccept() }}" @else accept="image/*" @endif>
        <div class="arch-image-cropper__canvas" x-ref="canvas"></div>
    </div>
</x-architect::field-wrapper>
