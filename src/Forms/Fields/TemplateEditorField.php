<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Text editor with `{{ variable }}` placeholder autocomplete and a
 * rendered-result preview — Wave C (FORMS_FEATURE_PLAN.md Phase 3).
 * Value is the raw template string; rendering it against real data is a
 * host-app concern.
 */
class TemplateEditorField extends Field
{
    /** @var array<int, string> */
    private array $availableVariables = [];

    /** @param  array<int, string>  $variables */
    public function availableVariables(array $variables): static
    {
        $clone = clone $this;
        $clone->availableVariables = $variables;

        return $clone;
    }

    /** @return array<int, string> */
    public function getAvailableVariables(): array
    {
        return $this->availableVariables;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.template-editor';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
