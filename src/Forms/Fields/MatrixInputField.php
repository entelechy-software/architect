<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Survey-style response grid: rows of statements, each scored against the
 * same set of columns — Wave C (FORMS_FEATURE_PLAN.md Phase 3). Value
 * shape: array<string row, string column>.
 */
class MatrixInputField extends Field
{
    /** @var array<int, string> */
    private array $rows = [];

    /** @var array<int, string> */
    private array $columns = [];

    /** @param  array<int, string>  $rows */
    public function rows(array $rows): static
    {
        $clone = clone $this;
        $clone->rows = $rows;

        return $clone;
    }

    /** @param  array<int, string>  $columns */
    public function columns(array $columns): static
    {
        $clone = clone $this;
        $clone->columns = $columns;

        return $clone;
    }

    /** @return array<int, string> */
    public function getRows(): array
    {
        return $this->rows;
    }

    /** @return array<int, string> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.matrix-input';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
