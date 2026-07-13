<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Entelechy\Architect\Forms\Fields\Concerns\HasOptions;

/**
 * Multi-value select with chip-style selected-value display — Wave B
 * (FORMS_FEATURE_PLAN.md Phase 3). Value is an array of selected option
 * keys, unlike SelectField's single scalar value.
 */
class MultiSelectField extends Field
{
    use HasOptions;

    private ?int $min = null;

    private ?int $max = null;

    public function min(int $min): static
    {
        $clone = clone $this;
        $clone->min = $min;

        return $clone;
    }

    public function max(int $max): static
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

    public function getViewName(): string
    {
        return 'architect::forms.fields.multi-select';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();
        $rules[] = 'array';

        if ($this->min !== null) {
            $rules[] = "min:{$this->min}";
        }

        if ($this->max !== null) {
            $rules[] = "max:{$this->max}";
        }

        if (! ($this->options instanceof \Closure) && $this->options !== []) {
            $rules[] = 'in:'.implode(',', array_keys($this->options));
        }

        return $rules;
    }
}
