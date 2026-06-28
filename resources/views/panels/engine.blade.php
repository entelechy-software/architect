@php
/**
 * @var \Entelechy\Architect\Panels\ArchitectDashboardDefinition $definition
 */
@endphp

<div class="arch-dashboard" data-key="{{ $definition->key }}">
    <div class="arch-dashboard__grid">
        @foreach ($definition->panels as $slot)
            @php
                /** @var \Entelechy\Architect\Panels\Contracts\Panel $panel */
                $panel      = $slot['panel'];
                $span       = $slot['span'];
                $def        = $panel->build();
                $panelIndex = $loop->index;
            @endphp
            <div class="arch-dashboard__slot" data-span="{{ $span }}">
                @include('architect::panels.partials.' . $def->type, ['panel' => $panel, 'def' => $def, 'panelIndex' => $panelIndex])
            </div>
        @endforeach
    </div>
</div>
