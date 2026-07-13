<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Presents two versions of a record and lets the user choose which
 * version of each changed field survives — Wave C
 * (FORMS_FEATURE_PLAN.md Phase 3). Value shape: array<string field,
 * 'current'|'incoming'>.
 */
class DiffMergeField extends Field
{
    /** @var array<string, mixed> */
    private array $current = [];

    /** @var array<string, mixed> */
    private array $incoming = [];

    /** @param  array<string, mixed>  $current */
    public function current(array $current): static
    {
        $clone = clone $this;
        $clone->current = $current;

        return $clone;
    }

    /** @param  array<string, mixed>  $incoming */
    public function incoming(array $incoming): static
    {
        $clone = clone $this;
        $clone->incoming = $incoming;

        return $clone;
    }

    /** @return array<string, mixed> */
    public function getCurrent(): array
    {
        return $this->current;
    }

    /** @return array<string, mixed> */
    public function getIncoming(): array
    {
        return $this->incoming;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.diff-merge';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
