<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator;

use Entelechy\Architect\Navigator\Items\DynamicTabType;
use Entelechy\Architect\Navigator\Items\PinnedTab;

/**
 * Immutable value object produced by NavigatorBuilder::workspaceTabs()->build().
 *
 * Holds the full configuration for a workspace tab bar: which tabs are pinned,
 * which dynamic tab types are registered, and which UX features are enabled.
 *
 * Note: this class may contain callables (DynamicTabType::labelResolver,
 * DynamicTabType::createUsing). It must never be stored in Livewire public
 * state. ModuleTabsManager rebuilds it fresh on every request from the
 * definition class name string.
 */
final class WorkspaceTabsDefinition
{
    /**
     * @param  list<PinnedTab>  $pinnedTabs
     * @param  array<string, DynamicTabType>  $dynamicTypes  Keyed by type name
     */
    public function __construct(
        public readonly string $key,
        public readonly bool $persist,
        public readonly int $maxTabs,
        public readonly array $pinnedTabs,
        public readonly array $dynamicTypes,

        // ── UX feature flags ────────────────────────────────────────────
        public readonly bool $showOverflowPopover,
        public readonly bool $showRecentlyClosed,
        public readonly int $recentlyClosedMax,
        public readonly bool $enableSwitcherPalette,
        public readonly bool $notifyStaleRecords,
    ) {}

    /**
     * Look up a registered DynamicTabType by its type string.
     */
    public function dynamicType(string $type): ?DynamicTabType
    {
        return $this->dynamicTypes[$type] ?? null;
    }

    /**
     * Return the initial $openTabs state: all pinned tabs.
     *
     * @return list<array<string, mixed>>
     */
    public function initialOpenTabs(): array
    {
        return array_map(fn (PinnedTab $tab) => $tab->toTabArray(), $this->pinnedTabs);
    }

    /**
     * Return only the data the Alpine store needs client-side.
     * Excludes callables (labelResolver) and server-only data.
     *
     * @return array<string, mixed>
     */
    public function toClientConfig(): array
    {
        return [
            'key' => $this->key,
            'persist' => $this->persist,
            'showOverflowPopover' => $this->showOverflowPopover,
            'showRecentlyClosed' => $this->showRecentlyClosed,
            'recentlyClosedMax' => $this->recentlyClosedMax,
            'enableSwitcherPalette' => $this->enableSwitcherPalette,
            'notifyStaleRecords' => $this->notifyStaleRecords,
            'pinnedTabs' => array_map(
                fn (PinnedTab $tab) => ['id' => $tab->getId(), 'label' => $tab->getLabel()],
                $this->pinnedTabs
            ),
        ];
    }
}
