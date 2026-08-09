<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar;

use Entelechy\Architect\Toolbar\Items\Contracts\ToolbarItem;
use Entelechy\Architect\Toolbar\Items\Dropdown\DropdownCheckbox;
use Entelechy\Architect\Toolbar\Items\Dropdown\DropdownRadioGroup as DropdownRadioGroupItem;
use Entelechy\Architect\Toolbar\Items\Dropdown\DropdownTextInput;
use Entelechy\Architect\Toolbar\Items\ToolbarBadge;
use Entelechy\Architect\Toolbar\Items\ToolbarButton;
use Entelechy\Architect\Toolbar\Items\ToolbarButtonGroup;
use Entelechy\Architect\Toolbar\Items\ToolbarDropdown;
use Entelechy\Architect\Toolbar\Items\ToolbarRadioGroup;
use Entelechy\Architect\Toolbar\Items\ToolbarSearch;
use Entelechy\Architect\Toolbar\Items\ToolbarSeparator;
use Entelechy\Architect\Toolbar\Items\ToolbarSpacer;

/**
 * Fluent builder for ArchitectToolbarDefinition.
 *
 * Always end the chain with ->build() to produce the immutable definition DTO.
 *
 * Usage:
 *   Architect::toolbar()
 *       ->key('advice-dashboard')
 *       ->item(ToolbarButton::make('create')->label('New Case')->color('primary'))
 *       ->item(ToolbarSpacer::make('push'))
 *       ->item(ToolbarButton::make('settings')->position('right'))
 *       ->build();
 *
 * For use as a definition class (the recommended pattern), implement a class:
 *
 *   class MyToolbarDefinition implements \Entelechy\Architect\Toolbar\Contracts\ProvidesToolbarDefinition {
 *       public static function definition(): ArchitectToolbarDefinition {
 *           return Architect::toolbar()->key('my-toolbar')->item(...)->build();
 *       }
 *   }
 *
 * And pass it as: <livewire:architect-toolbar definition-class="MyToolbarDefinition" />
 */
final class ToolbarBuilder
{
    /** @var list<ToolbarItem> */
    private array $items = [];

    private string $toolbarKey = '';

    private ?string $boundTarget = null;

    private string $size = 'sm';

    private bool $bordered = true;

    private bool $sticky = false;

    private ?string $permission = null;

    private function __construct() {}

    public static function make(): self
    {
        return new self;
    }

    // ── Chainable setters ─────────────────────────────────────────────────────

    /**
     * Machine key for this toolbar. Identifies it in the Alpine store and in
     * localStorage persistence keys. If omitted, ToolbarEngine derives it from
     * the definition class name.
     */
    public function key(string $key): self
    {
        $clone = clone $this;
        $clone->toolbarKey = $key;

        return $clone;
    }

    /**
     * Add a toolbar item. Order determines left-to-right render order
     * within each position group.
     */
    public function item(ToolbarItem $item): self
    {
        $clone = clone $this;
        $clone->items[] = $item;

        return $clone;
    }

    /**
     * Convenience: push an item anchored to the right side of the toolbar.
     * Equivalent to ->item($item->position('right')).
     */
    public function right(ToolbarItem $item): self
    {
        if ($item instanceof ToolbarButton) {
            return $this->item($item->position('right'));
        }
        if ($item instanceof ToolbarButtonGroup) {
            return $this->item($item->position('right'));
        }
        if ($item instanceof ToolbarDropdown) {
            return $this->item($item->position('right'));
        }
        if ($item instanceof ToolbarRadioGroup) {
            return $this->item($item->position('right'));
        }
        if ($item instanceof ToolbarBadge) {
            return $this->item($item->position('right'));
        }
        if ($item instanceof ToolbarSeparator) {
            return $this->item($item->position('right'));
        }
        if ($item instanceof ToolbarSpacer) {
            return $this->item($item->position('right'));
        }

        // Fallback: item doesn't have a position() setter, add as-is.
        return $this->item($item);
    }

    /**
     * Bind this toolbar to a target Architect component (e.g. a TableEngine).
     * Used for bidirectional state sync (Phase 4).
     */
    public function bind(string $targetKey): self
    {
        $clone = clone $this;
        $clone->boundTarget = $targetKey;

        return $clone;
    }

    /**
     * Control button size: 'sm' | 'md'.
     */
    public function size(string $size): self
    {
        $clone = clone $this;
        $clone->size = $size;

        return $clone;
    }

    /**
     * Whether the toolbar has a bottom border separating it from content below.
     */
    public function bordered(bool $bordered = true): self
    {
        $clone = clone $this;
        $clone->bordered = $bordered;

        return $clone;
    }

    /**
     * Whether the toolbar sticks to the top of the viewport when scrolling.
     */
    public function sticky(bool $sticky = true): self
    {
        $clone = clone $this;
        $clone->sticky = $sticky;

        return $clone;
    }

    /**
     * Permission node gating the whole toolbar. When set, ToolbarEngine
     * checks it on every render and dispatches `architect:unauthorized`
     * if the current user lacks it — defence in depth in case session
     * permissions changed since mount. Individual items still self-filter
     * via their own visibility checks regardless of this setting.
     */
    public function permission(?string $node): self
    {
        $clone = clone $this;
        $clone->permission = $node;

        return $clone;
    }

    // ── Build ─────────────────────────────────────────────────────────────────

    public function build(): ArchitectToolbarDefinition
    {
        $persistConfig = [];
        foreach ($this->items as $item) {
            $config = $this->resolvePersistConfig($item);
            if ($config !== []) {
                $persistConfig[$item->getKey()] = $config;
            }
        }

        return new ArchitectToolbarDefinition(
            key: $this->toolbarKey,
            items: $this->items,
            persistConfig: $persistConfig,
            boundTarget: $this->boundTarget,
            size: $this->size,
            bordered: $this->bordered,
            sticky: $this->sticky,
            permission: $this->permission,
        );
    }

    // ── Internal helpers ──────────────────────────────────────────────────────

    /** @return array<string, mixed> */
    private function resolvePersistConfig(ToolbarItem $item): array
    {
        if ($item instanceof ToolbarRadioGroup) {
            return [
                'type' => 'radio',
                'persist' => $item->getPersist(),
                'default' => $item->getDefault(),
            ];
        }

        if ($item instanceof ToolbarSearch) {
            return [
                'type' => 'search',
                'persist' => $item->getPersist(),
            ];
        }

        if ($item instanceof ToolbarDropdown) {
            $childConfig = [];
            foreach ($item->getItems() as $dropdownItem) {
                if (($dropdownItem instanceof DropdownCheckbox && $dropdownItem->isToggle()) && $dropdownItem->getPersist() !== 'none') {
                    $childConfig[$dropdownItem->getKey()] = ['type' => 'toggle', 'persist' => $dropdownItem->getPersist(), 'default' => $dropdownItem->getDefault()];
                }
                if ($dropdownItem instanceof DropdownCheckbox && $dropdownItem->getPersist() !== 'none') {
                    $childConfig[$dropdownItem->getKey()] = ['type' => 'checkbox', 'persist' => $dropdownItem->getPersist(), 'default' => $dropdownItem->getDefault()];
                }
                if ($dropdownItem instanceof DropdownRadioGroupItem && $dropdownItem->getPersist() !== 'none') {
                    $childConfig[$dropdownItem->getKey()] = ['type' => 'dropdown-radio', 'persist' => $dropdownItem->getPersist(), 'default' => $dropdownItem->getDefault()];
                }
                if ($dropdownItem instanceof DropdownTextInput && $dropdownItem->getPersist() !== 'none') {
                    $childConfig[$dropdownItem->getKey()] = ['type' => 'text', 'persist' => $dropdownItem->getPersist(), 'default' => $dropdownItem->getDefault()];
                }
            }

            return $childConfig !== [] ? ['type' => 'dropdown', 'children' => $childConfig] : [];
        }

        return [];
    }
}
