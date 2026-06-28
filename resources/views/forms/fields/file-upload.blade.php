@php /** @var \Entelechy\Architect\Forms\Fields\FileUpload $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-file-upload"
         x-data="architectFileUpload()"
         @dragover.prevent="dragging = true"
         @dragleave.prevent="dragging = false"
         @drop.prevent="dragging = false"
         :data-dragging="dragging">
        <label class="arch-file-upload__dropzone" for="field-{{ $field->getName() }}">
            <span class="arch-file-upload__icon"></span>
            <span class="arch-file-upload__text">{{ __('Drag & drop or click to upload') }}</span>
            @if ($field->getAccept())
                <span class="arch-file-upload__hint">{{ $field->getAccept() }}</span>
            @endif
        </label>

        <input type="file"
               id="field-{{ $field->getName() }}"
               class="arch-file-upload__input"
               wire:model="formData.{{ $field->getName() }}"
               @if ($field->isMultiple()) multiple @endif
               @if ($field->getAccept()) accept="{{ $field->getAccept() }}" @endif>

        <div wire:loading wire:target="formData.{{ $field->getName() }}" class="arch-progress">
            <div class="arch-progress__track">
                <div class="arch-progress__fill" style="width: 100%"></div>
            </div>
        </div>
    </div>
</x-architect::field-wrapper>
