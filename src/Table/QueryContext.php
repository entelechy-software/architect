<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table;

use Entelechy\Architect\Table\Contracts\ArchitectFilter;

/**
 * Immutable snapshot of the user's current query state for a module table:
 * search text, active filter values, sort column/direction, page, page size,
 * scope context (parent IDs from the URL), and whether archived rows are
 * included.
 *
 * Also carries the filter definition objects (keyed by filter name) so that
 * data models can delegate query logic to the filter's apply() method via
 * ModuleTableFilterPipeline rather than re-implementing it by hand.
 *
 * Created by the engine from request input on each list request, then
 * passed to the data model's forList(). Construction validates that
 * sort direction is one of asc/desc and clamps page/perPage to sane
 * lower bounds.
 */
final readonly class QueryContext
{
    /**
     * @param  array<string, mixed>  $filters
     * @param  'asc'|'desc'  $sortDirection
     * @param  array<string, int|string>  $scope  URL-derived parent IDs (e.g. ['activity_id' => 42])
     * @param  array<string, ArchitectFilter>  $filterDefinitions  Filter objects keyed by name,
     *                                                             for use with ModuleTableFilterPipeline
     */
    public function __construct(
        public string $search = '',
        public array $filters = [],
        public ?string $sortColumn = null,
        public string $sortDirection = 'asc',
        public int $page = 1,
        public int $perPage = 25,
        public bool $includeArchived = false,
        public array $scope = [],
        public array $filterDefinitions = [],
    ) {
        // Defensive normalisation — these are public readonly so callers
        // could otherwise hand us nonsense values.
        if (! in_array($this->sortDirection, ['asc', 'desc'], true)) {
            throw new \InvalidArgumentException("sortDirection must be 'asc' or 'desc'");
        }

        if ($this->page < 1) {
            throw new \InvalidArgumentException('page must be >= 1');
        }

        if ($this->perPage < 1 || $this->perPage > 500) {
            throw new \InvalidArgumentException('perPage must be between 1 and 500');
        }
    }

    public function withPage(int $page): self
    {
        return new self(
            $this->search,
            $this->filters,
            $this->sortColumn,
            $this->sortDirection,
            $page,
            $this->perPage,
            $this->includeArchived,
            $this->scope,
            $this->filterDefinitions,
        );
    }

    /**
     * Return a copy of this context with the given filter key removed from
     * both $filters and $filterDefinitions. Use in data models to prevent
     * ModuleTableFilterPipeline from applying a filter that the model has
     * already translated into a custom WHERE clause.
     */
    public function withoutFilter(string $key): self
    {
        $filters = $this->filters;
        $definitions = $this->filterDefinitions;
        unset($filters[$key], $definitions[$key]);

        return new self(
            $this->search,
            $filters,
            $this->sortColumn,
            $this->sortDirection,
            $this->page,
            $this->perPage,
            $this->includeArchived,
            $this->scope,
            $definitions,
        );
    }
}
