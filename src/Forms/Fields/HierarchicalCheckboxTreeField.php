<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Closure;

/**
 * A tree of checkboxes where parent nodes reflect partial/full child
 * selection — Wave B (FORMS_FEATURE_PLAN.md Phase 3). Value is the array
 * of selected leaf/branch keys.
 *
 * Tree shape: array<int, array{key: string, label: string, children?: array}>
 */
class HierarchicalCheckboxTreeField extends Field
{
    /** @var array<int, array<string, mixed>>|Closure(Closure(string): mixed): array<int, array<string, mixed>> */
    private array|Closure $tree = [];

    /** @param  array<int, array<string, mixed>>|Closure(Closure(string): mixed): array<int, array<string, mixed>>  $tree */
    public function tree(array|Closure $tree): static
    {
        $clone = clone $this;
        $clone->tree = $tree;

        return $clone;
    }

    /** @return array<int, array<string, mixed>> */
    public function getTree(Closure $get): array
    {
        return $this->tree instanceof Closure ? ($this->tree)($get) : $this->tree;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.hierarchical-checkbox-tree';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
