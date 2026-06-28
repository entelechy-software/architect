<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar\Items;

use Closure;
use Entelechy\Architect\Toolbar\Items\Contracts\ToolbarItem;
use Illuminate\Contracts\Container\Container;

/**
 * A read-only reactive pill/counter that displays a value or label.
 *
 * The badge value is provided server-side. For dynamic values, the
 * owning Livewire component should re-render or use a wire:poll to
 * update the toolbar definition.
 *
 * Example:
 *   ToolbarBadge::make('case-count')
 *       ->label('42 cases')
 *       ->color('primary')
 *       ->position('left')
 *
 * ->live(fn (Container $app): int => ..., every: 30) marks the badge as
 * self-refreshing — same convention as Stats\Elements\MetricCard::live().
 * Note: as with MetricCard, ToolbarEngine does not yet resolve the live
 * callable on a poll cycle; this stores the intent so a host app's own
 * Blade override or a future engine update can wire the refresh.
 */
final class ToolbarBadge implements ToolbarItem
{
    private string $label = '';

    private string $color = 'secondary';

    private ?string $icon = null;

    private string $pos = 'left';

    private ?string $tooltip = null;

    /** @var Closure(Container): mixed|null */
    private ?Closure $liveCallable = null;

    private int $liveEvery = 30;

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

    public function color(string $color): self
    {
        $clone = clone $this;
        $clone->color = $color;

        return $clone;
    }

    public function icon(string $icon): self
    {
        $clone = clone $this;
        $clone->icon = $icon;

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

    /**
     * @param  Closure(Container): mixed  $callable
     */
    public function live(Closure $callable, int $every = 30): self
    {
        $clone = clone $this;
        $clone->liveCallable = $callable;
        $clone->liveEvery = $every;

        return $clone;
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function getKey(): string
    {
        return $this->itemKey;
    }

    public function getItemType(): string
    {
        return 'badge';
    }

    public function getPosition(): string
    {
        return $this->pos;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getTooltip(): ?string
    {
        return $this->tooltip;
    }

    public function isLive(): bool
    {
        return $this->liveCallable !== null;
    }

    /** @return Closure(Container): mixed|null */
    public function getLiveCallable(): ?Closure
    {
        return $this->liveCallable;
    }

    public function getLiveEvery(): int
    {
        return $this->liveEvery;
    }
}
