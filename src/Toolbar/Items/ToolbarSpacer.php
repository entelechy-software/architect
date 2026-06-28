<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items;

use Entelechy\Architect\Toolbar\Items\Contracts\ToolbarItem;

/**
 * A spacer between toolbar items.
 *
 * Two modes:
 *   - 'push'  (default) — consumes all remaining horizontal space, pushing
 *     subsequent items to the far right of the toolbar.
 *   - 'fixed' — a constant-width gap; pair with ->width().
 *
 * Example:
 *   ->item(ToolbarSpacer::make())                          // auto-keyed, push mode
 *   ->item(ToolbarSpacer::make('gap')->mode('fixed')->width('4rem'))
 */
final class ToolbarSpacer implements ToolbarItem
{
    private string $pos = 'left';

    /** @var 'push'|'fixed' */
    private string $mode = 'push';

    private ?string $width = null;

    private function __construct(private readonly string $itemKey) {}

    public static function make(?string $key = null): self
    {
        return new self($key ?? ('spacer-'.uniqid()));
    }

    public function position(string $position): self
    {
        $clone = clone $this;
        $clone->pos = $position;

        return $clone;
    }

    /** @param  'push'|'fixed'  $mode */
    public function mode(string $mode): self
    {
        $clone = clone $this;
        $clone->mode = $mode;

        return $clone;
    }

    /** CSS width (e.g. '4rem'). Only applied when ->mode('fixed'). */
    public function width(string $cssWidth): self
    {
        $clone = clone $this;
        $clone->width = $cssWidth;

        return $clone;
    }

    public function getKey(): string
    {
        return $this->itemKey;
    }

    public function getItemType(): string
    {
        return 'spacer';
    }

    public function getPosition(): string
    {
        return $this->pos;
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function getWidth(): ?string
    {
        return $this->width;
    }
}
