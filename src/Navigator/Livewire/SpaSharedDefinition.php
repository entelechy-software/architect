<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator\Livewire;

/**
 * Lightweight DTO shared with the layout via view()->share('definition', ...) by SpaTabsEngine.
 *
 * The layout reads $sharedDef->breadcrumbs and $sharedDef->inheritBreadcrumbs to render
 * the topbar breadcrumb block. Using a dedicated DTO (rather than re-sharing the
 * ArchitectNavigatorDefinition) avoids undefined-property errors because
 * ArchitectNavigatorDefinition does not carry pageTitle.
 *
 * For inherit mode the layout renders an Alpine-reactive breadcrumb block whose
 * initial `crumbs` value comes from $breadcrumbs (the initial tab's crumbs) and
 * which updates when a `architect:breadcrumbs` custom event fires on window.
 */
final class SpaSharedDefinition
{
    /**
     * @param  array<int, array{title: string, url?: string}>  $breadcrumbs  Initial/static crumb trail.
     */
    public function __construct(
        public readonly array $breadcrumbs = [],
        public readonly bool $inheritBreadcrumbs = false,
    ) {}
}
