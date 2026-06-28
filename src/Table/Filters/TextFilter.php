<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Filters;

use Entelechy\Architect\Table\Contracts\ArchitectFilter;
use Illuminate\Contracts\Database\Query\Builder;

class TextFilter extends ArchitectFilter
{
    /**
     * Search mode for the generated LIKE clause.
     *
     *   - 'contains'    (default, back-compat) emits LIKE '%term%'.
     *                    Cannot use a B-tree index — full table scan.
     *   - 'starts_with' emits LIKE 'term%'. Index-friendly; cuts query
     *                    cost dramatically on large tables but only
     *                    matches rows whose value begins with the term.
     *
     * Opt in per filter via ->startsWith() during the builder.
     */
    private string $searchMode = 'contains';

    /**
     * Switch this text filter to prefix-search mode (LIKE 'term%').
     *
     * Use when the column has (or can be given) a B-tree index on the
     * searched column and substring matching is not required by the
     * UX (typeahead-style filtering on names, codes, slugs).
     */
    public function startsWith(): static
    {
        $clone = clone $this;
        $clone->searchMode = 'starts_with';

        return $clone;
    }

    public function blade(): string
    {
        return 'architect::table.filters.text';
    }

    protected function doApply(Builder $query, mixed $value): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        $term = trim($value);
        $pattern = $this->searchMode === 'starts_with'
            ? $term.'%'
            : '%'.$term.'%';

        $query->where($this->name(), 'LIKE', $pattern);
    }
}
