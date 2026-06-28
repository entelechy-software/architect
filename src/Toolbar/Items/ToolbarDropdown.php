<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items;

use Entelechy\Architect\Toolbar\Items\Contracts\DropdownItem;
use Entelechy\Architect\Toolbar\Items\Contracts\ToolbarItem;

/**
 * A toolbar button that opens a dropdown menu containing DropdownItem instances.
 *
 * Example:
 *   ToolbarDropdown::make('options')
 *       ->label('Options')
 *       ->icon('fas fa-sliders-h')
 *       ->item(DropdownCheckbox::toggle('archived')->label('Show archived'))
 *       ->item(DropdownSeparator::make()->label('Sort by'))
 *       ->position('right')
 */
final class ToolbarDropdown implements ToolbarItem
{
    private string $label = '';

    private ?string $icon = null;

    private string $color = 'secondary';

    private bool $outlined = true;

    private bool $disabled = false;

    private ?string $permission = null;

    /** @var list<DropdownItem> */
    private array $dropdownItems = [];

    private string $pos = 'left';

    private ?string $tooltip = null;

    private ?string $badge = null;

    private string $badgeColor = 'secondary';

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

    public function color(string $color): self
    {
        $clone = clone $this;
        $clone->color = $color;

        return $clone;
    }

    public function filled(bool $filled = true): self
    {
        $clone = clone $this;
        $clone->outlined = ! $filled;

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

    public function item(DropdownItem $item): self
    {
        $clone = clone $this;
        $clone->dropdownItems[] = $item;

        return $clone;
    }

    public function position(string $position): self
    {
        $clone = clone $this;
        $clone->pos = $position;

        return $clone;
    }

    public function tooltip(string $text): self
    {
        $clone = clone $this;
        $clone->tooltip = $text;

        return $clone;
    }

    public function badge(string $text, string $color = 'secondary'): self
    {
        $clone = clone $this;
        $clone->badge = $text;
        $clone->badgeColor = $color;

        return $clone;
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getKey(): string
    {
        return $this->itemKey;
    }

    public function getItemType(): string
    {
        return 'dropdown';
    }

    public function getPosition(): string
    {
        return $this->pos;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function isOutlined(): bool
    {
        return $this->outlined;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    /** @return list<DropdownItem> */
    public function getItems(): array
    {
        return $this->dropdownItems;
    }

    public function getTooltip(): ?string
    {
        return $this->tooltip;
    }

    public function getBadge(): ?string
    {
        return $this->badge;
    }

    public function getBadgeColor(): string
    {
        return $this->badgeColor;
    }
}
