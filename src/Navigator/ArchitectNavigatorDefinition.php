<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator;

use Entelechy\Architect\Navigator\Contracts\NavigatorItem;
use Entelechy\Architect\Navigator\Items\Tab;

/**
 * Immutable snapshot produced by NavigatorBuilder::build().
 *
 * This is a plain value object with zero dependency on Livewire, Architect Table,
 * or any framework layer. It can be instantiated in unit tests and in standalone
 * Blade components alike.
 *
 * Supported types: tabs | pills | buttons | toolbar | stepper | sidebar | dropdown
 * Positions: top | bottom | left | right
 * Alignments: start | center | end | fill
 */
final class ArchitectNavigatorDefinition
{
    /**
     * @param  string  $type  Navigator render style.
     * @param  string  $position  Where the navigator is placed relative to the content.
     * @param  string  $align  Horizontal alignment of the item list.
     * @param  list<NavigatorItem>  $items  Ordered item list.
     * @param  bool  $validateOnStep  When true, steps beyond the first incomplete step are locked.
     * @param  string|null  $validateWithForm  HTML id of a <form> to check before forward navigation.
     * @param  string|null  $validateWithMethod  Livewire component method name (returns bool) to call before forward navigation.
     * @param  bool  $spa  When true, tab content is embedded inline and switching is client-side.
     * @param  string  $loadingStrategy  'eager' (all panels in initial HTML) | 'lazy' (on-load AJAX per panel).
     * @param  string|null  $urlParam  Query-string parameter name for SPA deep-linking. Null disables URL sync.
     * @param  array<int, array{title: string, url?: string}>  $breadcrumbs  Explicit breadcrumb trail for the topbar.
     * @param  bool  $inheritBreadcrumbs  When true, breadcrumbs are resolved from each SPA tab's embedded TableDefinition.
     * @param  string|null  $permission  node gating the whole navigator; null = no top-level gate.
     */
    public function __construct(
        public readonly string $type,
        public readonly string $position,
        public readonly string $align,
        public readonly array $items,
        public readonly bool $validateOnStep = false,
        public readonly ?string $validateWithForm = null,
        public readonly ?string $validateWithMethod = null,
        public readonly string $tabStyle = 'button',
        public readonly ?WorkspaceTabsDefinition $workspaceDefinition = null,
        public readonly bool $spa = false,
        public readonly string $loadingStrategy = 'eager',
        public readonly ?string $urlParam = 'tab',
        public readonly array $breadcrumbs = [],
        public readonly bool $inheritBreadcrumbs = false,
        public readonly ?string $permission = null,
    ) {}

    /**
     * Resolve which item is "active" for a given request path.
     *
     * Returns the first item whose URL prefix matches $path; falls back to
     * the first item marked ->default(); returns null if nothing matches.
     */
    public function activeItem(string $path): ?NavigatorItem
    {
        $default = null;
        $bestMatch = null;
        $bestMatchLength = -1;

        foreach ($this->items as $item) {
            if (method_exists($item, 'isDefault') && $item->isDefault() && $default === null) {
                $default = $item;
            }

            if (method_exists($item, 'isActiveForPath') && $item->isActiveForPath($path)) {
                $hrefLength = method_exists($item, 'getHref') ? strlen($item->getHref() ?? '') : 0;
                if ($hrefLength > $bestMatchLength) {
                    $bestMatch = $item;
                    $bestMatchLength = $hrefLength;
                }
            }
        }

        return $bestMatch ?? $default;
    }

    /**
     * Resolve the initial active tab slug for SPA mode.
     *
     * Checks $queryValue against the slugs of all non-disabled Tab items.
     * Falls back to the first non-disabled Tab if the query value is missing,
     * invalid, or belongs to a disabled tab.
     */
    public function initialTab(string $queryValue = ''): string
    {
        $first = null;

        foreach ($this->items as $item) {
            if (! ($item instanceof Tab) || $item->isDisabled()) {
                continue;
            }

            $first ??= $item->getSlug();

            if ($queryValue !== '' && $item->getSlug() === $queryValue) {
                return $item->getSlug();
            }
        }

        return $first ?? '';
    }
}
