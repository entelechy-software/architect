<?php

declare(strict_types=1);

namespace Entelechy\Architect\Stats\Elements;

use Closure;
use Entelechy\Architect\Stats\DateRange;

/**
 * A single KPI card within a MetricGrid section.
 *
 * Two modes:
 *
 *   ->value(callable)  — resolved at dashboard render time. Callable receives
 *                        either (Container) or (DateRange, Container) depending
 *                        on its signature; the engine injects accordingly.
 *
 *   ->live(callable, every: int) — resolved fresh on every dashboard render,
 *                        ignoring the dashboard date filter. Auto-refresh
 *                        relies on the dashboard's own ->poll($every) interval
 *                        (StatBuilder::poll()) — set ->poll() to the same
 *                        value as $every so wire:poll drives the refresh.
 *                        The callable only receives (Container) — live cards
 *                        represent current state, not a date-range aggregate.
 *
 * Usage:
 *   MetricCard::make('New Cases')
 *       ->icon('fas fa-folder-plus')
 *       ->value(fn(DateRange $range, Container $app): int => ...)
 *       ->trend(+12.5);
 *
 *   MetricCard::make('Open Cases')
 *       ->icon('fas fa-folder-open')
 *       ->live(fn(Container $app): int => ..., every: 30);
 */
final class MetricCard
{
    private string $label;

    private ?string $icon = null;

    /**
     * Static value callable — resolved once at render time.
     * Signature: fn(Container): mixed  OR  fn(DateRange, Container): mixed
     */
    private ?Closure $valueCallable = null;

    /**
     * Whether $valueCallable requires DateRange as its first argument.
     * Detected at build time via ReflectionFunction.
     */
    private bool $requiresDateRange = false;

    /** Optional % change vs prior period. Positive = up, negative = down. */
    private ?float $trend = null;

    /**
     * Live-polling callable — signature: fn(Container): mixed
     * Null = not a live card.
     */
    private ?Closure $liveCallable = null;

    /** Polling interval in seconds (only meaningful when $liveCallable is set). */
    private int $liveEvery = 30;

    /**
     * Whether to animate the displayed value counting up from 0 on first render.
     * Only applies to static (non-live) cards with numeric values.
     */
    private bool $countUp = true;

    private function __construct(string $label)
    {
        $this->label = $label;
    }

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

    /**
     * Assign a date-range-aware or plain callable to resolve the card's value.
     *
     * The callable is inspected at build time (via ReflectionFunction) to
     * detect whether its first parameter is DateRange. The engine then injects
     * the appropriate arguments.
     *
     * @param  Closure  $callable  fn(Container): mixed  OR  fn(DateRange, Container): mixed
     */
    public function value(Closure $callable): self
    {
        $clone = clone $this;
        $clone->valueCallable = $callable;
        $clone->detectDateRange($callable);

        return $clone;
    }

    /**
     * Mark this card as live-polling, refreshing every N seconds.
     *
     * The callable receives only (Container) — live cards represent current
     * state and are independent of the dashboard date filter.
     *
     * @param  Closure  $callable  fn(Container): mixed
     * @param  int  $every  Polling interval in seconds
     */
    public function live(Closure $callable, int $every = 30): self
    {
        $clone = clone $this;
        $clone->liveCallable = $callable;
        $clone->liveEvery = $every;

        return $clone;
    }

    /**
     * Optional trend percentage vs prior period.
     * Positive float = increase (green up arrow), negative = decrease (red down arrow).
     */
    public function trend(float $percent): self
    {
        $clone = clone $this;
        $clone->trend = $percent;

        return $clone;
    }

    // ── Accessors (read by StatBuilder and Blade partials) ────────────────

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function getValueCallable(): ?Closure
    {
        return $this->valueCallable;
    }

    public function isDateRangeRequired(): bool
    {
        return $this->requiresDateRange;
    }

    public function getTrend(): ?float
    {
        return $this->trend;
    }

    public function isLive(): bool
    {
        return $this->liveCallable !== null;
    }

    /**
     * Enable or disable the count-up animation for this card.
     *
     * The animation is enabled by default for non-live cards with a
     * numeric value. Pass false to render the value statically.
     * Respects the global config('architect.animations') master switch.
     */
    public function countUp(bool $enabled = true): self
    {
        $clone = clone $this;
        $clone->countUp = $enabled;

        return $clone;
    }

    public function shouldCountUp(): bool
    {
        return $this->countUp;
    }

    public function getLiveCallable(): ?Closure
    {
        return $this->liveCallable;
    }

    public function getLiveEvery(): int
    {
        return $this->liveEvery;
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Inspect the callable's first parameter type to determine whether
     * DateRange injection is needed. Stored as a flag so the engine doesn't
     * have to reflect at render time.
     */
    private function detectDateRange(Closure $callable): void
    {
        try {
            $ref = new \ReflectionFunction($callable);
            $params = $ref->getParameters();
            if (empty($params)) {
                $this->requiresDateRange = false;

                return;
            }
            $first = $params[0]->getType();
            $this->requiresDateRange = $first instanceof \ReflectionNamedType
                && $first->getName() === DateRange::class;
        } catch (\ReflectionException) {
            $this->requiresDateRange = false;
        }
    }
}
