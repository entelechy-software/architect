<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields\Concerns;

use Closure;

/**
 * Shared options(array|Closure) handling for choice-based fields
 * (CheckboxList, Radio, ToggleButtons).
 */
trait HasOptions
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
}
