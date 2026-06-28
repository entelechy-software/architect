<?php

declare(strict_types=1);

namespace Entelechy\Architect\Content\Entries;

/**
 * Renders the resolved value as an <img> (e.g. an avatar or attachment URL).
 *
 * Usage:
 *   ImageEntry::make('avatar_url')->width(48)->height(48)->rounded()->fallback('/img/no-avatar.png')
 */
class ImageEntry extends Entry
{
    protected ?int $width = null;

    protected ?int $height = null;

    protected bool $rounded = false;

    protected ?string $fallback = null;

    public function width(int $width): static
    {
        $clone = clone $this;
        $clone->width = $width;

        return $clone;
    }

    public function height(int $height): static
    {
        $clone = clone $this;
        $clone->height = $height;

        return $clone;
    }

    public function rounded(bool $condition = true): static
    {
        $clone = clone $this;
        $clone->rounded = $condition;

        return $clone;
    }

    public function fallback(string $url): static
    {
        $clone = clone $this;
        $clone->fallback = $url;

        return $clone;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getHeight(): ?int
    {
        return $this->height;
    }

    public function isRounded(): bool
    {
        return $this->rounded;
    }

    public function getFallback(): ?string
    {
        return $this->fallback;
    }

    public function getViewName(): string
    {
        return 'architect::content.entries.image';
    }
}
