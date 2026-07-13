<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Node graph editor: connect ports between blocks (e.g. a data pipeline
 * or module contract diagram) — Wave D (FORMS_FEATURE_PLAN.md Phase 3).
 * Value shape: ['nodes' => array<int, array{id: string, type: string,
 * x: float, y: float}>, 'edges' => array<int, array{from: string, to:
 * string}>].
 */
class NodeGraphEditorField extends Field
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
        return 'architect::forms.fields.node-graph-editor';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
