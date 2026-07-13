@php /** @var \Entelechy\Architect\Forms\Fields\GeographicBoundaryField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-geographic-boundary"
         wire:ignore
         x-data="architectGeographicBoundary({ wireField: 'formData.{{ $field->getName() }}', providerScriptUrl: @js($field->getProviderScriptUrl()) })"
         style="height: 320px">
        <div x-ref="map" style="height: 100%"></div>
    </div>
</x-architect::field-wrapper>
