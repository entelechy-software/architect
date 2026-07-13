<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Drag-between-columns board input — Wave C (FORMS_FEATURE_PLAN.md
 * Phase 3). Value shape: array<string columnKey, array<int, string
 * itemKey>>.
 */
class KanbanBoardField extends Field
{
    /** @var array<int, string> */
    private array $columns = [];

    /** @param  array<int, string>  $columns */
    public function columns(array $columns): static
    {
        $clone = clone $this;
        $clone->columns = $columns;

        return $clone;
    }

    /** @return array<int, string> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.kanban-board';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
