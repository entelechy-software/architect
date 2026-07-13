@php /** @var \Entelechy\Architect\Forms\Fields\MapLocationField $field */ @endphp
<x-architect::field-wrapper :field="$field">
    <div class="arch-map-location"
         wire:ignore
         x-data="architectMapLocation({
            wireField: 'formData.{{ $field->getName() }}',
            providerScriptUrl: @js($field->getProviderScriptUrl()),
            defaultZoom: {{ $field->getDefaultZoom() }},
         })"
         style="height: 320px">
        <div x-ref="map" style="height: 100%"></div>
    </div>
</x-architect::field-wrapper>
