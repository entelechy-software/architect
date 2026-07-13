<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Renders a form automatically from a JSON Schema, and captures the
 * resulting object — Wave D (FORMS_FEATURE_PLAN.md Phase 3). Value is an
 * array matching the shape described by schema().
 */
class SchemaDrivenObjectEditorField extends Field
{
    /** @var array<string, mixed> */
    private array $schema = [];

    /** @param  array<string, mixed>  $schema  A JSON Schema (decoded to an array). */
    public function schema(array $schema): static
    {
        $clone = clone $this;
        $clone->schema = $schema;

        return $clone;
    }

    /** @return array<string, mixed> */
    public function getSchema(): array
    {
        return $this->schema;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.schema-driven-object-editor';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
