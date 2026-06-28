{{--
    <x-architect::definition-renderer :definition="$definition" />
    <x-architect::definition-renderer definition-class="\App\Modules\Foo\Components\Tables\FooNavigator" />

    Dispatcher component — resolves the correct partial based on
    $definition->type and wraps it in a position-aware container.

    For workspace-tabs type, renders ModuleTabsManager (Livewire) directly.
    The definition-class attribute is required for workspace-tabs so the
    class name can be forwarded to ModuleTabsManager (which must not
    receive callables as serializable Livewire state).

    Accepted positions:
      top    — rendered above the content; bottom border only
      bottom — rendered below the content; top border only
      left   — rendered in a flex column beside the content
      right  — rendered in a flex column beside the content

    Props:
      $definition       ArchitectNavigatorDefinition (optional if definitionClass given)
      $definitionClass  string FQCN (optional; used to build $definition + forwarded to workspace-tabs)
--}}
@props(['definition' => null, 'definitionClass' => '', 'wrapInCard' => true])

@php
    // When a definitionClass is provided, always build from it — this prevents
    // view()->share('definition', ...) calls (e.g. SpaSharedDefinition shared for
    // breadcrumbs) from bleeding into this component's scope and causing
    // "Undefined property" errors on classes that don't carry navigator properties.
    if ($definitionClass !== '') {
        if (class_exists($definitionClass) && method_exists($definitionClass, 'build')) {
            $definition = $definitionClass::build();
        }
    }

    if ($definition === null) {
        return;
    }

    $path = request()->getPathInfo();
@endphp

@if ($definition->type === 'workspace-tabs')
    {{-- Workspace tabs bypass the position wrapper — ModuleTabsManager renders its own full-width bar. --}}
    @if ($definitionClass !== '')
        @livewire(\Entelechy\Architect\Navigator\Livewire\ModuleTabsManager::class, ['definitionClass' => $definitionClass])
    @endif
@else
    @php
        $positionClass = match ($definition->position) {
            'bottom' => 'arch-navigator-wrapper--bottom border-t border-gray-200 dark:border-gray-700 pt-2 mt-3',
            'left'   => 'arch-navigator-wrapper--left mr-3',
            'right'  => 'arch-navigator-wrapper--right ml-3',
            default  => 'arch-navigator-wrapper--top border-b border-gray-200 dark:border-gray-700 pb-2 mb-3',
        };
    @endphp
    @if ($wrapInCard)
    <div class="arch-navigator-wrapper {{ $positionClass }}">
    @endif
        @switch ($definition->type)
            @case ('tabs')
                @include('architect::navigator.tabs', ['definition' => $definition, 'path' => $path, 'wrapInCard' => $wrapInCard])
            @break

        @case ('pills')
            @include('architect::navigator.pills', ['definition' => $definition, 'path' => $path, 'wrapInCard' => $wrapInCard])
        @break

        @case ('buttons')
            @include('architect::navigator.buttons', ['definition' => $definition, 'path' => $path, 'wrapInCard' => $wrapInCard])
        @break

        @case ('toolbar')
            @include('architect::navigator.toolbar', ['definition' => $definition, 'path' => $path, 'wrapInCard' => $wrapInCard])
        @break

        @case ('stepper')
            @include('architect::navigator.stepper', ['definition' => $definition, 'path' => $path, 'wrapInCard' => $wrapInCard])
        @break

        @case ('sidebar')
            @include('architect::navigator.sidebar', ['definition' => $definition, 'path' => $path, 'wrapInCard' => $wrapInCard])
        @break

        @case ('dropdown')
            @include('architect::navigator.dropdown', ['definition' => $definition, 'path' => $path, 'wrapInCard' => $wrapInCard])
        @break

        @default
            {{-- Unknown type — render nothing so unknown types degrade gracefully. --}}
    @endswitch
    @if ($wrapInCard)
    </div>
    @endif
@endif
