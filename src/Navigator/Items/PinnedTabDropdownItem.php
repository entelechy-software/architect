<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator\Items;

/**
 * A menu item for a dropdown attached to a pinned workspace tab.
 *
 * When selected, the item updates the tab's displayed label and dispatches
 * a Livewire event so any component on the page can react (e.g. a dashboard
 * component listening for a filter-change event).
 *
 * Usage:
 *   PinnedTabDropdownItem::make('My Cases')
 *       ->event('advice:set-filter', ['tab' => 'my-cases'])
 */
final class PinnedTabDropdownItem
{
    private string $eventName = '';

    /** @var array<string, mixed> */
    private array $payload = [];

    private bool $isSeparator = false;

    private ?string $switchTabId = null;

    private function __construct(private readonly string $label) {}

    public static function make(string $label): self
    {
        return new self($label);
    }

    /**
     * Create a visual divider (no label, no event).
     * Use PinnedTab::separator() instead of this directly.
     *
     * @internal
     */
    public static function makeSeparator(): self
    {
        $item = new self('');
        $item->isSeparator = true;

        return $item;
    }

    /**
     * Livewire event to dispatch globally when this item is selected.
     *
     * @param  array<string, mixed>  $payload
     */
    public function event(string $name, array $payload = []): self
    {
        $clone = clone $this;
        $clone->eventName = $name;
        $clone->payload = $payload;

        return $clone;
    }

    /**
     * Switch to a different workspace tab when this item is selected,
     * instead of staying on the tab that owns the dropdown.
     *
     * Use the target tab's stable ID (PinnedTab::getId() — e.g. 'pinned-archive').
     */
    public function switchTab(string $tabId): self
    {
        $clone = clone $this;
        $clone->switchTabId = $tabId;

        return $clone;
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'separator' => $this->isSeparator,
            'label' => $this->label,
            'event' => $this->eventName,
            'payload' => $this->payload,
            'switch_tab' => $this->switchTabId,
        ];
    }
}
