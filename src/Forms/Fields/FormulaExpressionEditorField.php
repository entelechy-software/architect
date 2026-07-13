<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Formula/expression editor with field-reference and function
 * autocomplete hooks — Wave C (FORMS_FEATURE_PLAN.md Phase 3), e.g.
 * `(total - discount) * tax_rate`. Value is the raw expression string;
 * evaluating it is a host-app concern.
 */
class FormulaExpressionEditorField extends Field
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
        return 'architect::forms.fields.formula-expression-editor';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
