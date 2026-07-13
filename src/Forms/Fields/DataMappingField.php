<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Maps incoming field names to application field names, with optional
 * per-row transforms — Wave D (FORMS_FEATURE_PLAN.md Phase 3). Value
 * shape: array<int, array{source: string, destination: string,
 * transform: ?string}>.
 */
class DataMappingField extends Field
{
    /** @var array<int, string> */
    private array $sourceFields = [];

    /** @var array<int, string> */
    private array $destinationFields = [];

    /** @param  array<int, string>  $fields */
    public function sourceFields(array $fields): static
    {
        $clone = clone $this;
        $clone->sourceFields = $fields;

        return $clone;
    }

    /** @param  array<int, string>  $fields */
    public function destinationFields(array $fields): static
    {
        $clone = clone $this;
        $clone->destinationFields = $fields;

        return $clone;
    }

    /** @return array<int, string> */
    public function getSourceFields(): array
    {
        return $this->sourceFields;
    }

    /** @return array<int, string> */
    public function getDestinationFields(): array
    {
        return $this->destinationFields;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.data-mapping';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
