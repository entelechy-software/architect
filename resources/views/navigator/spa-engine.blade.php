{{--
    SPA Tabs Engine view.

    Delegates to the existing navigator partial for the configured tab style.
    The definition must have ->spa() set — SpaTabsEngine enforces this at mount.

    Tab styles:
      'tabs'    (default) — pill-button or page-underline row
      'pills'             — filled pill row
      'buttons'           — button-group row
      'sidebar'           — vertical left-rail
--}}
<div class="arch-navigator" data-loading="{{ $isLoading ? 'true' : 'false' }}">
    @if ($hasError || $definition === null)
        <x-architect::callout type="danger">{{ $errorMessage }}</x-architect::callout>
    @else
        @include('architect::navigator.' . $definition->type, [
            'definition'     => $definition,
            'path'           => request()->path(),
            'wrapInCard'     => false,
            'tabBreadcrumbs' => $tabBreadcrumbs ?? [],
        ])
    @endif
</div>
