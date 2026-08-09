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

    /** @var array<string, string> */
    private array $items = [];

    /** @param  array<int, string>  $columns */
    public function columns(array $columns): static
    {
        $clone = clone $this;
        $clone->columns = $columns;

        return $clone;
    }

    /** @param  array<string, string>  $items  itemKey => card label. */
    public function items(array $items): static
    {
        $clone = clone $this;
        $clone->items = $items;

        return $clone;
    }

    /** @return array<int, string> */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /** @return array<string, string> */
    public function getItems(): array
    {
        return $this->items;
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
