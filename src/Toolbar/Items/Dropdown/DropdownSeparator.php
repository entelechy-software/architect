<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items\Dropdown;

use Entelechy\Architect\Toolbar\Items\Contracts\DropdownItem;

/**
 * A visual divider or labelled section header inside a ToolbarDropdown.
 *
 * Without a label it renders as a plain <hr>.
 * With a label it renders as a small section heading (e.g. "Sort by").
 *
 * Example:
 *   DropdownSeparator::make('sort-heading')->label('Sort by')
 */
final class DropdownSeparator implements DropdownItem
{
    private ?string $label = null;

    private function __construct(private readonly string $itemKey) {}

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function label(string $label): self
    {
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }

    public function getKey(): string
    {
        return $this->itemKey;
    }

    public function getItemType(): string
    {
        return 'separator';
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }
}
