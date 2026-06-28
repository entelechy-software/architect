<?php

declare(strict_types=1);

namespace Entelechy\Architect\Concerns;

/**
 * Shared behaviour for any action or navigator item that can dispatch
 * the `architect:open-tab` intent event to open a record in a workspace tab.
 *
 * When a ModuleTabsManager (or equivalent tab host) is present on the page it
 * opens a dynamic tab. When no tab bar is available the global fallback
 * navigates to the $fallback URL instead.
 *
 * Usage:
 *   ->openInTab('case', '/advice/cases/create')
 *   ->openInTab('case')   // fallback derived from href at render time
 */
trait HasOpenInTab
{
    /** @var array{type: string, fallback: string}|null */
    private ?array $openInTab = null;

    /**
     * When clicked, dispatch the `architect:open-tab` intent event so the
     * nearest tab manager opens a dynamic tab.
     *
     * @param  string  $type  DynamicTabType key (e.g. 'case')
     * @param  string  $fallback  URL to navigate to when no tab bar is present.
     *                            For row actions supports {id} placeholder.
     *                            Defaults to '' — Blade templates fall back to
     *                            the item's own href when this is empty.
     */
    public function openInTab(string $type, string $fallback = ''): static
    {
        $clone = clone $this;
        $clone->openInTab = ['type' => $type, 'fallback' => $fallback];

        return $clone;
    }

    /** @return array{type: string, fallback: string}|null */
    public function getOpenInTab(): ?array
    {
        return $this->openInTab;
    }
}
