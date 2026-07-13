<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Numeric input with a unit suffix and optional automatic conversion
 * between a fixed set of units — Wave B (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Phase 3 ships unit selection and validation only; unit-to-unit
 * conversion math is a host-app/client-widget concern layered on top via
 * the Blade view's `x-data` hook.
 */
class MeasurementField extends Field
{
    /** @var array<int, string> */
    private array $units = ['mm', 'cm', 'm'];

    private string $defaultUnit = 'mm';

    /** @param  array<int, string>  $units */
    public function units(array $units): static
    {
        $clone = clone $this;
        $clone->units = $units;

        return $clone;
    }

    public function defaultUnit(string $unit): static
    {
        $clone = clone $this;
        $clone->defaultUnit = $unit;

        return $clone;
    }

    /** @return array<int, string> */
    public function getUnits(): array
    {
        return $this->units;
    }

    public function getDefaultUnit(): string
    {
        return $this->defaultUnit;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.measurement';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        return $rules;
    }
}
