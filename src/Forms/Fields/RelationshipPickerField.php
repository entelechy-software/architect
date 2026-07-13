<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Links this record to another record/event/entity, for modular systems
 * where relationships/triggers/ownership are user-configured — Wave C
 * (FORMS_FEATURE_PLAN.md Phase 3). Value shape: ['type' => string, 'id'
 * => int|string].
 */
class RelationshipPickerField extends Field
{
    /** @var array<int, string> */
    private array $allowedTypes = [];

    /** @param  array<int, string>  $types */
    public function allowedTypes(array $types): static
    {
        $clone = clone $this;
        $clone->allowedTypes = $types;

        return $clone;
    }

    /** @return array<int, string> */
    public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.relationship-picker';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
