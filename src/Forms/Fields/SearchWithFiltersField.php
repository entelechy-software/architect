<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Search input with structured filter chips (e.g. `status:active
 * role:admin`) — Wave B (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Value shape: ['query' => string, 'filters' => array<string, mixed>].
 */
class SearchWithFiltersField extends Field
{
    /** @var array<int, string> */
    private array $availableFilters = [];

    /** @param  array<int, string>  $filters */
    public function availableFilters(array $filters): static
    {
        $clone = clone $this;
        $clone->availableFilters = $filters;

        return $clone;
    }

    /** @return array<int, string> */
    public function getAvailableFilters(): array
    {
        return $this->availableFilters;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.search-with-filters';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
