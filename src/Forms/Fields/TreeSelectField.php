<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Closure;

/**
 * Dropdown containing hierarchical options — Wave B (FORMS_FEATURE_PLAN.md
 * Phase 3). Value is the selected node's key (a leaf or a branch,
 * depending on selectableBranches()).
 *
 * Tree shape: array<int, array{key: string, label: string, children?: array}>
 */
class TreeSelectField extends Field
{
    /** @var array<int, array<string, mixed>>|Closure(Closure(string): mixed): array<int, array<string, mixed>> */
    private array|Closure $tree = [];

    private bool $selectableBranches = false;

    /** @param  array<int, array<string, mixed>>|Closure(Closure(string): mixed): array<int, array<string, mixed>>  $tree */
    public function tree(array|Closure $tree): static
    {
        $clone = clone $this;
        $clone->tree = $tree;

        return $clone;
    }

    public function selectableBranches(bool $selectable = true): static
    {
        $clone = clone $this;
        $clone->selectableBranches = $selectable;

        return $clone;
    }

    /** @return array<int, array<string, mixed>> */
    public function getTree(Closure $get): array
    {
        return $this->tree instanceof Closure ? ($this->tree)($get) : $this->tree;
    }

    public function areBranchesSelectable(): bool
    {
        return $this->selectableBranches;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.tree-select';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
