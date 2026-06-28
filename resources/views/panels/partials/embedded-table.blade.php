@php
/**
 * @var \Entelechy\Architect\Panels\Panels\EmbeddedTablePanel $panel
 * @var \Entelechy\Architect\Panels\ArchitectPanelDefinition $def
 */
$definitionClass = $panel->getDefinitionClass();
$scope = $panel->getScope();
@endphp

<div class="arch-panel arch-panel--embedded-table">
    @if ($def->title)
        <h3 class="arch-panel__title">{{ $def->title }}</h3>
    @endif

    @if ($definitionClass)
        <livewire:architect-engine :definition-class="$definitionClass" :scope="$scope" :embedded="true" />
    @else
        <p class="arch-panel__placeholder">{{ __('No table definition class configured.') }}</p>
    @endif
</div>
