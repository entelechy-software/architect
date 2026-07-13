<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Closure;

/**
 * Static or dynamic-options select field.
 *
 * Use when the full set of choices is known at definition time, or
 * depends on another field's current value. For AJAX-driven or large
 * remote datasets, use LookupField instead.
 *
 * ->options(['value' => 'Label', ...])
 * ->options(fn (Closure $get) => $get('country') === 'US' ? [...] : [...])
 */
class SelectField extends Field
{
    /** @var array<string|int, string>|Closure(Closure(string): mixed): array<string|int, string> */
    private array|Closure $options = [];

    /**
     * @param  array<string|int, string>|Closure(Closure(string): mixed): array<string|int, string>  $options  key => display label pairs, or a closure resolving them from other fields' values
     */
    public function options(array|Closure $options): static
    {
        $clone = clone $this;
        $clone->options = $options;

        return $clone;
    }

    /**
     * @param  Closure(string): mixed  $get  Resolver for other fields' current values, needed when options() was given a closure.
     * @return array<string|int, string>
     */
    public function getOptions(Closure $get): array
    {
        return $this->options instanceof Closure ? ($this->options)($get) : $this->options;
    }

    public function getViewName(): string
    {
        return 'architect::forms.fields.select';
    }

    public function getRules(): array
    {
        $rules = parent::getRules();

        // Only a static option set can be validated with an `in:` rule here —
        // getRules() has no $get resolver, so Closure-based dynamic options
        // skip this constraint and rely on required/nullable plus whatever
        // the saveUsing()/model layer enforces.
        if (! ($this->options instanceof Closure) && $this->options !== []) {
            $keys = implode(',', array_keys($this->options));
            $rules[] = "in:{$keys}";
        }

        return $rules;
    }
}
