<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Closure;

/**
 * A select whose options depend on another field's current value — Wave B
 * (FORMS_FEATURE_PLAN.md Phase 3), e.g. Country -> County -> Town.
 *
 * Distinct from LookupField's cascadeFrom() (which is AJAX/remote-backed);
 * this is for statically-known cascading option sets resolved server-side
 * via the $get resolver.
 */
class CascadingSelectField extends Field
{
    private ?string $cascadeFrom = null;

    /** @var Closure(mixed, Closure(string): mixed): array<string|int, string> */
    private ?Closure $optionsFor = null;

    public function cascadeFrom(string $parentField): static
    {
        $clone = clone $this;
        $clone->cascadeFrom = $parentField;

        return $clone;
    }

    /** @param  Closure(mixed, Closure(string): mixed): array<string|int, string>  $callback  Receives the parent field's current value and the $get resolver. */
    public function optionsFor(Closure $callback): static
    {
        $clone = clone $this;
        $clone->optionsFor = $callback;

        return $clone;
    }

    public function getCascadeFrom(): ?string
    {
        return $this->cascadeFrom;
    }

    /** @return array<string|int, string> */
    public function getOptions(Closure $get): array
    {
        if ($this->optionsFor === null || $this->cascadeFrom === null) {
            return [];
        }

        return ($this->optionsFor)($get($this->cascadeFrom), $get);
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.cascading-select';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'string';

        return $rules;
    }
}
