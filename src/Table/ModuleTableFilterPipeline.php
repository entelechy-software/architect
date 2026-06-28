<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table;

use Illuminate\Contracts\Database\Query\Builder;

/**
 * Applies all active filters in a QueryContext to a query builder.
 *
 * Data models call this once in forList() to avoid repeating per-filter
 * WHERE logic. Each filter's apply() method handles its own no-op guard
 * for empty/null values, so the pipeline skips no filters — it lets them
 * decide.
 *
 * For filters whose apply() logic cannot be expressed as a simple column
 * constraint (e.g. date-derived status, multi-table joins), register a
 * custom Closure via SelectFilter::applyUsing() in the definition class.
 * Or, handle those keys manually in forList() before or after calling
 * ModuleTableFilterPipeline::apply().
 *
 * Usage:
 *   ModuleTableFilterPipeline::apply($query, $context);
 */
final class ModuleTableFilterPipeline
{
    /**
     * Invoke apply() on every filter definition that has a value in the
     * context. Filters with no active value still receive apply() — each
     * filter implementation is responsible for being a no-op when empty.
     */
    public static function apply(Builder $query, QueryContext $context): void
    {
        foreach ($context->filterDefinitions as $name => $filter) {
            $value = $context->filters[$name] ?? null;
            $filter->apply($query, $value);
        }
    }
}
