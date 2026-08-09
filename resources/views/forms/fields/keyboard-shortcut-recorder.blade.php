@php /** @var \Entelechy\Architect\Forms\Fields\KeyboardShortcutRecorderField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-keyboard-shortcut-recorder"
         wire:ignore
         x-data="architectKeyboardShortcutRecorder({ wireField: 'formData.{{ $field->getName() }}' })">
        <input type="text" class="arch-input" x-ref="display" readonly placeholder="{{ __('Press keys…') }}" x-on:keydown="record($event)">
    </div>
</x-architect::field-wrapper>
