<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Contracts;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Support\Renderable;

/**
 * Abstract base for the filter controls rendered above the table.
 *
 * Subclasses contribute:
 *   - blade(): the partial that renders the filter UI control
 *   - apply(): given a query builder and the filter's current value,
 *     mutate the query to constrain results.
 */
abstract class ArchitectFilter
{
    protected string $label;

    /** @var string|Renderable|null */
    protected string|Renderable|null $renderOverride = null;

    /** @var (\Closure(Builder, mixed): void)|null */
    protected ?\Closure $customApply = null;

    final protected function __construct(protected readonly string $name)
    {
        $this->label = ucwords(str_replace(['_', '-'], ' ', $name));
    }

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function label(string $label): static
    {
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }

    /**
     * Override how this filter UI is rendered in the filter panel.
     *
     * Accepts either:
     *   - a Blade view name (string), or
     *   - any Renderable object that returns HTML from render().
     */
    public function render(string|Renderable $renderer): static
    {
        $clone = clone $this;
        $clone->renderOverride = $renderer;

        return $clone;
    }

    /**
     * Override the default apply() behaviour with a custom closure.
     *
     * Useful when a filter's query logic does not map simply to a
     * WHERE column = value pattern (e.g. date-derived status flags,
     * relationship joins, computed conditions).
     *
     * @param  \Closure(Builder, mixed): void  $fn
     */
    public function applyUsing(\Closure $fn): static
    {
        $clone = clone $this;
        $clone->customApply = $fn;

        return $clone;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    /** @return string|Renderable */
    public function renderer(): string|Renderable
    {
        return $this->renderOverride ?? $this->blade();
    }

    abstract public function blade(): string;

    /**
     * Apply this filter to the supplied query builder.
     *
     * Calls the custom closure registered via applyUsing() when present;
     * otherwise delegates to doApply() on the concrete subclass.
     *
     * Implementations should be no-ops when $value is empty, so callers
     * do not need to guard each apply() invocation.
     */
    final public function apply(Builder $query, mixed $value): void
    {
        if ($this->customApply !== null) {
            ($this->customApply)($query, $value);

            return;
        }

        $this->doApply($query, $value);
    }

    /**
     * Default query logic for this filter type.
     *
     * Subclasses implement this instead of apply(). Override with
     * applyUsing() at the definition site for custom logic.
     */
    abstract protected function doApply(Builder $query, mixed $value): void;
}
