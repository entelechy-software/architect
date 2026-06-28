<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Entelechy\Architect\Forms\Fields\Concerns\HasOptions;

/**
 * Multi-select via a list of checkboxes.
 */
class CheckboxList extends Field
{
    use HasOptions;

    private bool $searchable = false;

    private int $columns = 1;

    public function searchable(bool $searchable = true): static
    {
        $clone = clone $this;
        $clone->searchable = $searchable;

        return $clone;
    }

    public function columns(int $columns): static
    {
        $clone = clone $this;
        $clone->columns = $columns;

        return $clone;
    }

    public function isSearchable(): bool
    {
        return $this->searchable;
    }

    public function getColumns(): int
    {
        return $this->columns;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.checkbox-list';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
