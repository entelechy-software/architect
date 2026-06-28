<?php

declare(strict_types=1);

namespace Entelechy\Architect\Content\Entries;

use Closure;

/**
 * Renders a Font Awesome icon, optionally mapped/coloured per value.
 *
 * Usage:
 *   IconEntry::make('status')
 *       ->icon(fn ($v) => $v === 'active' ? 'fas fa-circle-check' : 'fas fa-circle-xmark')
 *       ->color(fn ($v) => $v === 'active' ? 'success' : 'danger')
 */
class IconEntry extends Entry
{
    protected string|Closure $icon = 'fas fa-circle';

    protected string|Closure $color = 'default';

    protected string $size = 'md';

    public function icon(string|Closure $icon): static
    {
        $clone = clone $this;
        $clone->icon = $icon;

        return $clone;
    }

    public function color(string|Closure $color): static
    {
        $clone = clone $this;
        $clone->color = $color;

        return $clone;
    }

    public function size(string $size): static
    {
        $clone = clone $this;
        $clone->size = $size;

        return $clone;
    }

    public function resolveIcon(mixed $value, mixed $record): string
    {
        return $this->icon instanceof Closure ? ($this->icon)($value, $record) : $this->icon;
    }

    public function resolveColor(mixed $value, mixed $record): string
    {
        return $this->color instanceof Closure ? ($this->color)($value, $record) : $this->color;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function getViewName(): string
    {
        return 'architect::content.entries.icon';
    }
}
