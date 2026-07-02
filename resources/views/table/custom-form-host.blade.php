@php
    $customDefinitionClass = (string) ($customDefinitionClass ?? '');
    $engineComponent = (string) ($engineComponent ?? 'architect-form-engine');
    $recordId = $recordId ?? null;
    $instanceKey = (string) ($instanceKey ?? '');

    $componentKey = 'arch-custom-form-'.md5($customDefinitionClass.'|'.$engineComponent.'|'.(string) ($recordId ?? 'new'));
@endphp

<div
    x-data="{}"
    x-on:architect:form:saved.window="$dispatch('architect:refresh', { instanceKey: '{{ $instanceKey }}' }); $dispatch('architect:close-panel')"
    x-on:architect:wizard:completed.window="$dispatch('architect:refresh', { instanceKey: '{{ $instanceKey }}' }); $dispatch('architect:close-panel')"
>
    @if ($engineComponent === 'architect-wizard-engine')
        <livewire:architect-wizard-engine :definition-class="$customDefinitionClass" :key="$componentKey" />
    @else
        <livewire:architect-form-engine :definition-class="$customDefinitionClass" :key="$componentKey" />
    @endif
</div>
