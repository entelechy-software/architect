<?php

declare(strict_types=1);

namespace Entelechy\Architect\Navigator\Items;

use Entelechy\Architect\Concerns\HasOpenInTab;
use Entelechy\Architect\Navigator\Behaviours\LinkBehaviour;
use Entelechy\Architect\Navigator\Contracts\NavigatorItem;

/**
 * A standalone button item inside a NavigatorBuilder.
 *
 * Phase A: link behaviour only.
 * Phase B: will support ajax, emit, modal, script behaviours.
 */
final class NavButton implements NavigatorItem
{
    use HasOpenInTab;

    private ?string $icon = null;

    private ?string $href = null;

    private ?LinkBehaviour $behaviour = null;

    private string $color = 'secondary';

    private bool $disabled = false;

    private function __construct(
        private readonly string $label,
    ) {}

    public static function make(string $label): self
    {
        return new self($label);
    }

    public function icon(string $icon): self
    {
        $clone = clone $this;
        $clone->icon = $icon;

        return $clone;
    }

    public function href(string $url): self
    {
        $clone = clone $this;
        $clone->href = $url;
        $clone->behaviour = new LinkBehaviour($url);

        return $clone;
    }

    public function behaviour(LinkBehaviour $behaviour): self
    {
        $clone = clone $this;
        $clone->behaviour = $behaviour;
        if ($clone->href === null) {
            $clone->href = $behaviour->url;
        }

        return $clone;
    }

    /**
     * Button colour variant: primary, secondary, success, danger, warning, info, light, dark.
     */
    public function color(string $color): self
    {
        $clone = clone $this;
        $clone->color = $color;

        return $clone;
    }

    public function disabled(): self
    {
        $clone = clone $this;
        $clone->disabled = true;

        return $clone;
    }

    // ── NavigatorItem contract ───────────────────────────────────────────

    public function getItemType(): string
    {
        return 'button';
    }

    // ── Accessors ────────────────────────────────────────────────────────

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getHref(): ?string
    {
        return $this->href;
    }

    public function getBehaviour(): ?LinkBehaviour
    {
        return $this->behaviour;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }
}
