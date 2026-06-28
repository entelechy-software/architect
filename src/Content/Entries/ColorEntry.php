<?php

declare(strict_types=1);

namespace Entelechy\Architect\Content\Entries;

/**
 * Renders the resolved value (a CSS colour string) as a swatch, optionally
 * alongside its hex text.
 *
 * Usage:
 *   ColorEntry::make('brand_color')->showHex()->circle()
 */
class ColorEntry extends Entry
{
    protected bool $showHex = true;

    protected bool $circle = false;

    public function showHex(bool $condition = true): static
    {
        $clone = clone $this;
        $clone->showHex = $condition;

        return $clone;
    }

    public function circle(bool $condition = true): static
    {
        $clone = clone $this;
        $clone->circle = $condition;

        return $clone;
    }

    public function shouldShowHex(): bool
    {
        return $this->showHex;
    }

    public function isCircle(): bool
    {
        return $this->circle;
    }

    public function getViewName(): string
    {
        return 'architect::content.entries.color';
    }
}
