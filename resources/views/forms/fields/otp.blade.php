@php /** @var \Entelechy\Architect\Forms\Fields\OtpField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <input type="text"
           id="field-{{ $field->getName() }}"
           class="arch-input arch-input--otp"
           inputmode="numeric"
           autocomplete="one-time-code"
           maxlength="{{ $field->getLength() }}"
           pattern="\d*"
           wire:model="formData.{{ $field->getName() }}">
</x-architect::field-wrapper>
