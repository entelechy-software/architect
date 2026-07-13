<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Combines permission selection, scope, conditions, inheritance, and
 * exceptions into one role definition — Wave D (FORMS_FEATURE_PLAN.md
 * Phase 3). Value shape: ['permissions' => array<int, string>, 'scope'
 * => string|null, 'inherits_from' => string|null, 'exceptions' =>
 * array<int, string>].
 */
class RoleBuilderField extends Field
{
    /** @var array<int, string> */
    private array $availablePermissions = [];

    /** @var array<int, string> */
    private array $availableRolesToInheritFrom = [];

    /** @param  array<int, string>  $permissions */
    public function availablePermissions(array $permissions): static
    {
        $clone = clone $this;
        $clone->availablePermissions = $permissions;

        return $clone;
    }

    /** @param  array<int, string>  $roles */
    public function availableRolesToInheritFrom(array $roles): static
    {
        $clone = clone $this;
        $clone->availableRolesToInheritFrom = $roles;

        return $clone;
    }

    /** @return array<int, string> */
    public function getAvailablePermissions(): array
    {
        return $this->availablePermissions;
    }

    /** @return array<int, string> */
    public function getAvailableRolesToInheritFrom(): array
    {
        return $this->availableRolesToInheritFrom;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.role-builder';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
