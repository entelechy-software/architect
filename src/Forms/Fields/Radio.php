<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Entelechy\Architect\Forms\Fields\Concerns\HasOptions;

/**
 * Exclusive selection via styled radio buttons.
 */
class Radio extends Field
{
    use HasOptions;

    private bool $inline = false;

    public function inline(bool $inline = true): static
    {
        $clone = clone $this;
        $clone->inline = $inline;

        return $clone;
    }

    public function isInline(): bool
    {
        return $this->inline;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.radio';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();

        // Only a static option set can be validated with an `in:` rule here —
        // getRules() has no $get resolver, so Closure-based dynamic options
        // skip this constraint.
        if (! ($this->options instanceof \Closure) && $this->options !== []) {
            $keys = implode(',', array_keys($this->options));
            $rules[] = "in:{$keys}";
        }

        return $rules;
    }
}
