<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items;

use Entelechy\Architect\Toolbar\Items\Contracts\ToolbarItem;

/**
 * A thin vertical separator line between toolbar items.
 *
 * Example:
 *   ToolbarSeparator::make()           // auto-keyed
 *   ToolbarSeparator::make('sep-1')    // explicit key
 */
final class ToolbarSeparator implements ToolbarItem
{
    private string $pos = 'left';

    private function __construct(private readonly string $itemKey) {}

    public static function make(?string $key = null): self
    {
        return new self($key ?? ('sep-'.uniqid()));
    }

    public function position(string $position): self
    {
        $clone = clone $this;
        $clone->pos = $position;

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

    public function getPosition(): string
    {
        return $this->pos;
    }
}
