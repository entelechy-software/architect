<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

/**
 * Decimal number field for monetary/fractional values.
 *
 * Renders as an HTML number input with a configurable step.
 * Defaults to 2 decimal places (step=0.01).
 */
class DecimalField extends Field
{
    private int $decimals = 2;

    public function decimals(int $decimals): static
    {
        $clone = clone $this;
        $clone->decimals = $decimals;

        return $clone;
    }

    public function getDecimals(): int
    {
        return $this->decimals;
    }

    public function getStep(): string
    {
        return number_format(1 / (10 ** $this->decimals), $this->decimals, '.', '');
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.decimal';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'numeric';

        return $rules;
    }
}
