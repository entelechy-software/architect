<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items\Dropdown;

use Entelechy\Architect\Toolbar\Items\Contracts\DropdownItem;

/**
 * A nested sub-menu inside a ToolbarDropdown (one level deep maximum).
 *
 * Renders as a menu item with a right-arrow indicator. Clicking it expands
 * the sub-items inline (accordion within the dropdown). Only
 * DropdownAction, DropdownSeparator, and DropdownLinkGroup are allowed as
 * children — recursive nesting is intentionally unsupported.
 *
 * Example:
 *   DropdownSubmenu::make('export-options')
 *       ->label('Export')
 *       ->icon('fas fa-download')
 *       ->item(DropdownAction::make('csv')->label('CSV')->dispatch('export:csv'))
 *       ->item(DropdownAction::make('excel')->label('Excel')->dispatch('export:excel'))
 *       ->item(DropdownAction::make('pdf')->label('PDF')->dispatch('export:pdf'))
 */
final class DropdownSubmenu implements DropdownItem
{
    private string $label = '';

    private ?string $icon = null;

    private bool $disabled = false;

    private ?string $permission = null;

    /** @var list<DropdownAction|DropdownSeparator|DropdownLinkGroup> */
    private array $subItems = [];

    private function __construct(private readonly string $itemKey) {}

    public static function make(string $key): self
    {
        return new self($key);
    }

    // ── Chainable setters ─────────────────────────────────────────────────────

    public function label(string $label): self
    {
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }

    public function icon(string $icon): self
    {
        $clone = clone $this;
        $clone->icon = $icon;

        return $clone;
    }

    public function disabled(bool $disabled = true): self
    {
        $clone = clone $this;
        $clone->disabled = $disabled;

        return $clone;
    }

    public function permission(string $node): self
    {
        $clone = clone $this;
        $clone->permission = $node;

        return $clone;
    }

    /**
     * Add a child item. Only DropdownAction, DropdownSeparator, and
     * DropdownLinkGroup are permitted (no recursive DropdownSubmenu).
     */
    public function item(DropdownAction|DropdownSeparator|DropdownLinkGroup $item): self
    {
        $clone = clone $this;
        $clone->subItems[] = $item;

        return $clone;
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getKey(): string
    {
        return $this->itemKey;
    }

    public function getItemType(): string
    {
        return 'submenu';
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    /** @return list<DropdownAction|DropdownSeparator|DropdownLinkGroup> */
    public function getItems(): array
    {
        return $this->subItems;
    }
}
