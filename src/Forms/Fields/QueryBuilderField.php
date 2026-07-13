<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Visual query builder: nested AND/OR condition groups — Wave D
 * (FORMS_FEATURE_PLAN.md Phase 3). Value shape: ['operator' =>
 * 'and'|'or', 'conditions' => array<int, array{field: string, operator:
 * string, value: mixed}|array{operator: 'and'|'or', conditions: array}>].
 */
class QueryBuilderField extends Field
{
    /** @var array<int, string> */
    private array $availableFields = [];

    /** @param  array<int, string>  $fields */
    public function availableFields(array $fields): static
    {
        $clone = clone $this;
        $clone->availableFields = $fields;

        return $clone;
    }

    /** @return array<int, string> */
    public function getAvailableFields(): array
    {
        return $this->availableFields;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.query-builder';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
