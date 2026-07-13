<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Grid of resources x actions, each cell a checkbox — Wave D
 * (FORMS_FEATURE_PLAN.md Phase 3). Value shape: array<string resource,
 * array<string action, bool>>.
 */
class PermissionMatrixField extends Field
{
    /** @var array<int, string> */
    private array $resources = [];

    /** @var array<int, string> */
    private array $actions = ['view', 'create', 'update', 'delete'];

    /** @param  array<int, string>  $resources */
    public function resources(array $resources): static
    {
        $clone = clone $this;
        $clone->resources = $resources;

        return $clone;
    }

    /** @param  array<int, string>  $actions */
    public function actions(array $actions): static
    {
        $clone = clone $this;
        $clone->actions = $actions;

        return $clone;
    }

    /** @return array<int, string> */
    public function getResources(): array
    {
        return $this->resources;
    }

    /** @return array<int, string> */
    public function getActions(): array
    {
        return $this->actions;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.permission-matrix';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
