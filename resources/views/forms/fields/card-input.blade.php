@php /** @var \Entelechy\Architect\Forms\Fields\CardInputField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-card-input"
         wire:ignore
         x-data="architectCardInput({
            wireField: 'formData.{{ $field->getName() }}',
            providerScriptUrl: @js($field->getProviderScriptUrl()),
            publishableKey: @js($field->getPublishableKey()),
         })">
        {{-- The provider's hosted-field SDK mounts here; raw card data never touches this component's Livewire state. --}}
        <div x-ref="mount"></div>
        <p class="arch-field__hint" x-show="status" x-text="status"></p>
    </div>
</x-architect::field-wrapper>
