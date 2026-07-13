<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Builds a conditional-visibility rule for another field/section, e.g.
 * "Show X when Y equals Z" — Wave D (FORMS_FEATURE_PLAN.md Phase 3).
 * Value shape: ['field' => string, 'operator' => string, 'value' =>
 * mixed]. This describes the rule declaratively; it does not itself
 * evaluate it (that remains the existing ->hidden()/->visible() closure
 * mechanism on Field).
 */
class DependencyBuilderField extends Field
{
    /** @var array<int, string> */
    private array $availableFields = [];

    /** @var array<int, string> */
    private array $availableOperators = ['equals', 'not_equals', 'contains', 'greater_than', 'less_than'];

    /** @param  array<int, string>  $fields */
    public function availableFields(array $fields): static
    {
        $clone = clone $this;
        $clone->availableFields = $fields;

        return $clone;
    }

    /** @param  array<int, string>  $operators */
    public function availableOperators(array $operators): static
    {
        $clone = clone $this;
        $clone->availableOperators = $operators;

        return $clone;
    }

    /** @return array<int, string> */
    public function getAvailableFields(): array
    {
        return $this->availableFields;
    }

    /** @return array<int, string> */
    public function getAvailableOperators(): array
    {
        return $this->availableOperators;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.dependency-builder';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
