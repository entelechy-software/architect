<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Visual workflow builder: an ordered/branching sequence of trigger and
 * action nodes — Wave D (FORMS_FEATURE_PLAN.md Phase 3). Value shape:
 * ['nodes' => array<int, array{id: string, type: string, config:
 * array}>, 'edges' => array<int, array{from: string, to: string}>].
 */
class RulesWorkflowBuilderField extends Field
{
    /** @var array<int, string> */
    private array $availableNodeTypes = [];

    /** @param  array<int, string>  $types */
    public function availableNodeTypes(array $types): static
    {
        $clone = clone $this;
        $clone->availableNodeTypes = $types;

        return $clone;
    }

    /** @return array<int, string> */
    public function getAvailableNodeTypes(): array
    {
        return $this->availableNodeTypes;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.rules-workflow-builder';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
