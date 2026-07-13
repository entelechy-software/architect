<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Currency amount input — Wave B (FORMS_FEATURE_PLAN.md Phase 3).
 *
 * Value is a plain numeric string/float in the field's base unit (e.g.
 * pounds, not pence); currency() is presentational (prefix/label) only —
 * this field does not perform currency conversion.
 */
class CurrencyField extends Field
{
    private string $currency = 'GBP';

    private int $decimals = 2;

    private ?float $min = null;

    private ?float $max = null;

    public function currency(string $currency): static
    {
        $clone = clone $this;
        $clone->currency = $currency;

        return $clone;
    }

    public function decimals(int $decimals): static
    {
        $clone = clone $this;
        $clone->decimals = $decimals;

        return $clone;
    }

    public function min(float $min): static
    {
        $clone = clone $this;
        $clone->min = $min;

        return $clone;
    }

    public function max(float $max): static
    {
        $clone = clone $this;
        $clone->max = $max;

        return $clone;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    public function getMin(): ?float
    {
        return $this->min;
    }

    public function getMax(): ?float
    {
        return $this->max;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.currency';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'numeric';

        if ($this->min !== null) {
            $rules[] = "min:{$this->min}";
        }

        if ($this->max !== null) {
            $rules[] = "max:{$this->max}";
        }

        return $rules;
    }
}
