<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Fields;

use Entelechy\Architect\Table\Contracts\ArchitectField;

/**
 * Integer number field.
 *
 * Renders as an HTML number input. Optionally constrains
 * the accepted range via ->min() and ->max().
 */
class IntegerField extends ArchitectField
{
    private ?int $min = null;

    private ?int $max = null;

    public function min(int $min): self
    {
        $clone = clone $this;
        $clone->min = $min;

        return $clone;
    }

    public function max(int $max): self
    {
        $clone = clone $this;
        $clone->max = $max;

        return $clone;
    }

    public function getMin(): ?int
    {
        return $this->min;
    }

    public function getMax(): ?int
    {
        return $this->max;
    }

    public function blade(): string
    {
        return 'architect::table.fields.integer';
    }

    public function validationRules(): array
    {
        $rules = parent::validationRules();
        $rules[] = 'integer';

        if ($this->min !== null) {
            $rules[] = "min:{$this->min}";
        }

        if ($this->max !== null) {
            $rules[] = "max:{$this->max}";
        }

        return $rules;
    }
}
