<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Closure;

/**
 * Range slider for numeric input.
 */
class Slider extends Field
{
    private int|float $min = 0;

    private int|float $max = 100;

    private int|float $step = 1;

    private ?Closure $displayFormat = null;

    public function min(int|float $min): static
    {
        $clone = clone $this;
        $clone->min = $min;

        return $clone;
    }

    public function max(int|float $max): static
    {
        $clone = clone $this;
        $clone->max = $max;

        return $clone;
    }

    public function step(int|float $step): static
    {
        $clone = clone $this;
        $clone->step = $step;

        return $clone;
    }

    public function displayFormat(Closure $callback): static
    {
        $clone = clone $this;
        $clone->displayFormat = $callback;

        return $clone;
    }

    public function getMin(): int|float
    {
        return $this->min;
    }

    public function getMax(): int|float
    {
        return $this->max;
    }

    public function getStep(): int|float
    {
        return $this->step;
    }

    public function formatDisplay(int|float $value): string
    {
        return $this->displayFormat ? (string) ($this->displayFormat)($value) : (string) $value;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.slider';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'numeric';
        $rules[] = "min:{$this->min}";
        $rules[] = "max:{$this->max}";

        return $rules;
    }
}
