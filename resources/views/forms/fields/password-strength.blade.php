@php /** @var \Entelechy\Architect\Forms\Fields\PasswordStrengthField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-password-strength"
         wire:ignore
         x-data="architectPasswordStrength({ wireField: 'formData.{{ $field->getName() }}', minLength: {{ $field->getMinLength() }} })">
        <input type="password" id="field-{{ $field->getName() }}" class="arch-input" x-ref="input" x-on:input="onInput($event.target.value)">
        <div class="arch-password-strength__meter" x-ref="meter"></div>
        <p class="arch-password-strength__label" x-show="label !== ''" x-text="label"></p>
    </div>
    @if ($field->isConfirmationRequired())
        <input type="password" class="arch-input" placeholder="{{ __('Confirm password') }}" wire:model="formData.{{ $field->getName() }}_confirmation">
    @endif
</x-architect::field-wrapper>
