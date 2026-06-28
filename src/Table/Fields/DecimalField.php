<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Fields;

use Entelechy\Architect\Table\Contracts\ArchitectField;

/**
 * Decimal number field for monetary/fractional values.
 *
 * Renders as an HTML number input with a configurable step.
 * Defaults to 2 decimal places (step=0.01).
 */
class DecimalField extends ArchitectField
{
    private int $decimals = 2;

    public function decimals(int $decimals): self
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

    public function blade(): string
    {
        return 'architect::table.fields.decimal';
    }

    public function validationRules(): array
    {
        $rules = parent::validationRules();
        $rules[] = 'numeric';

        return $rules;
    }
}
