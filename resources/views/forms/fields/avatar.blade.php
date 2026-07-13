@php /** @var \Entelechy\Architect\Forms\Fields\AvatarField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-avatar-upload"
         wire:ignore
         x-data="architectAvatarUpload({ wireField: 'formData.{{ $field->getName() }}', initialsFrom: @js($field->getInitialsFrom()) })">
        <div class="arch-avatar-upload__preview" x-ref="preview"></div>
        <input type="file" wire:model="formData.{{ $field->getName() }}" @if ($field->getAccept() !== null) accept="{{ $field->getAccept() }}" @else accept="image/*" @endif>
    </div>
</x-architect::field-wrapper>
