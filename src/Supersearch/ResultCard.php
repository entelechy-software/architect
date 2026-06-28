<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch;

use Entelechy\Architect\Supersearch\Actions\Concerns\ResolvesForRecord;
use Entelechy\Architect\Supersearch\Actions\Contracts\SearchAction;

/**
 * Defines how a single result record should be rendered.
 *
 * All slot setters accept either a static value or a callable(mixed $record): mixed.
 * This makes the definition reusable across any record type without binding to a
 * specific model class at definition time.
 */
final class ResultCard
{
    use ResolvesForRecord;

    /** @var string|\Closure(mixed): string|null */
    private string|\Closure|null $icon = null;

    /** @var string|\Closure(mixed): string|null */
    private string|\Closure|null $iconColour = null;

    /** @var string|\Closure(mixed): string|null */
    private string|\Closure|null $avatar = null;

    /** @var string|\Closure(mixed): string|null */
    private string|\Closure|null $eyebrow = null;

    /** @var string|\Closure(mixed): string|null */
    private string|\Closure|null $title = null;

    /** @var string|\Closure(mixed): string|null */
    private string|\Closure|null $badge = null;

    /** @var string|\Closure(mixed): string|null */
    private string|\Closure|null $badgeColour = null;

    /** @var string|\Closure(mixed): string|null */
    private string|\Closure|null $meta = null;

    /** @var string|\Closure(mixed): string|null */
    private string|\Closure|null $timestamp = null;

    /** @var list<string>|\Closure(mixed): list<string>|null */
    private array|\Closure|null $tags = null;

    /** @var bool|\Closure(mixed): bool */
    private bool|\Closure $dim = false;

    private function __construct() {}

    public static function make(): self
    {
        return new self;
    }

    // -------------------------------------------------------------------------
    // Slot setters
    // -------------------------------------------------------------------------

    /** Font-Awesome class or similar icon identifier (e.g. 'fas fa-folder-open'). */
    public function icon(string|\Closure $icon): self
    {
        $clone = clone $this;
        $clone->icon = $icon;

        return $clone;
    }

    /**
     * Tailwind colour name used to tint the icon container (e.g. 'blue', 'red', 'green').
     * Maps to CSS custom properties defined in the result-card partial.
     */
    public function iconColour(string|\Closure $colour): self
    {
        $clone = clone $this;
        $clone->iconColour = $colour;

        return $clone;
    }

    /** Absolute URL to an avatar image; takes precedence over icon when set. */
    public function avatar(string|\Closure $avatar): self
    {
        $clone = clone $this;
        $clone->avatar = $avatar;

        return $clone;
    }

    /** Small text displayed above the title (e.g. category or section name). */
    public function eyebrow(string|\Closure $eyebrow): self
    {
        $clone = clone $this;
        $clone->eyebrow = $eyebrow;

        return $clone;
    }

    /** Primary label shown prominently. Required for a useful result. */
    public function title(string|\Closure $title): self
    {
        $clone = clone $this;
        $clone->title = $title;

        return $clone;
    }

    /** Short status or category badge text. */
    public function badge(string|\Closure $badge): self
    {
        $clone = clone $this;
        $clone->badge = $badge;

        return $clone;
    }

    /**
     * Tailwind colour name for the badge background
     * (e.g. 'green', 'red', 'amber', 'blue').
     */
    public function badgeColour(string|\Closure $colour): self
    {
        $clone = clone $this;
        $clone->badgeColour = $colour;

        return $clone;
    }

    /** Secondary descriptor line shown below the title. */
    public function meta(string|\Closure $meta): self
    {
        $clone = clone $this;
        $clone->meta = $meta;

        return $clone;
    }

    /** Relative or absolute time string shown on the trailing edge. */
    public function timestamp(string|\Closure $timestamp): self
    {
        $clone = clone $this;
        $clone->timestamp = $timestamp;

        return $clone;
    }

    /**
     * Collection of short labels shown as inline chips.
     *
     * @param  list<string>|\Closure  $tags
     */
    public function tags(array|\Closure $tags): self
    {
        $clone = clone $this;
        $clone->tags = $tags;

        return $clone;
    }

    /**
     * When true (or the callable returns true) the row is styled as muted /
     * de-emphasised (e.g. closed or archived records).
     */
    public function dim(bool|\Closure $dim): self
    {
        $clone = clone $this;
        $clone->dim = $dim;

        return $clone;
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    /**
     * Evaluate all slots against a concrete record and return a plain array
     * suitable for Livewire property storage and Blade.
     *
     * The `$action` parameter is accepted so record-specific action arrays
     * (e.g. OpenTabAction with resolved props) can be merged in at render time.
     *
     * @return array<string, mixed>
     */
    public function renderFor(mixed $record, ?SearchAction $action = null): array
    {
        $resolvedAction = null;

        if ($action !== null) {
            $resolvedAction = self::resolveActionForRecord($action, $record);
        }

        return [
            'icon' => $this->resolve($this->icon, $record),
            'iconColour' => $this->resolve($this->iconColour, $record),
            'avatar' => $this->resolve($this->avatar, $record),
            'eyebrow' => $this->resolve($this->eyebrow, $record),
            'title' => $this->resolve($this->title, $record),
            'badge' => $this->resolve($this->badge, $record),
            'badgeColour' => $this->resolve($this->badgeColour, $record),
            'meta' => $this->resolve($this->meta, $record),
            'timestamp' => $this->resolve($this->timestamp, $record),
            'tags' => $this->resolve($this->tags, $record) ?? [],
            'dim' => (bool) $this->resolve($this->dim, $record),
            'action' => $resolvedAction,
        ];
    }

    private function resolve(mixed $slot, mixed $record): mixed
    {
        return $slot instanceof \Closure ? $slot($record) : $slot;
    }
}
