<?php

declare(strict_types=1);

namespace Entelechy\Architect\Content\Entries;

/**
 * Plain or badge-styled text display.
 *
 * Usage:
 *   TextEntry::make('status')->badge()->formatUsing(fn ($v) => ucfirst($v))
 *   TextEntry::make('email')->copyable()
 */
class TextEntry extends Entry
{
    protected bool $copyable = false;

    protected ?string $placeholder = null;

    protected bool $badge = false;

    public function copyable(bool $condition = true): static
    {
        $clone = clone $this;
        $clone->copyable = $condition;

        return $clone;
    }

    public function placeholder(string $placeholder): static
    {
        $clone = clone $this;
        $clone->placeholder = $placeholder;

        return $clone;
    }

    public function badge(bool $condition = true): static
    {
        $clone = clone $this;
        $clone->badge = $condition;

        return $clone;
    }

    public function isCopyable(): bool
    {
        return $this->copyable;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function isBadge(): bool
    {
        return $this->badge;
    }

    public function getViewName(): string
    {
        return 'architect::content.entries.text';
    }
}
