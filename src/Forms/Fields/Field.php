<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Closure;
use Entelechy\Architect\Forms\Contracts\ArchitectField;

/**
 * Abstract base for every standalone Forms field.
 *
 * A field is a value object: created via the static make() factory,
 * configured via fluent setters that each return a clone, then frozen
 * once handed to FormBuilder::structure()/field(). Mirrors the
 * immutable-clone convention already used by Table\Contracts\ArchitectField.
 */
abstract class Field implements ArchitectField
{
    protected string $label = '';

    protected bool $required = false;

    protected ?string $placeholder = null;

    protected ?string $hint = null;

    protected mixed $default = null;

    /** @var array<int, string> */
    protected array $rules = [];

    protected bool|Closure $hidden = false;

    protected bool|Closure $disabled = false;

    final public function __construct(protected readonly string $name) {}

    public static function make(string $name): static
    {
        return new static($name);
    }

    public function label(string $label): static
    {
        $clone = clone $this;
        $clone->label = $label;

        return $clone;
    }

    public function required(bool $condition = true): static
    {
        $clone = clone $this;
        $clone->required = $condition;

        return $clone;
    }

    public function placeholder(string $placeholder): static
    {
        $clone = clone $this;
        $clone->placeholder = $placeholder;

        return $clone;
    }

    public function hint(string $hint): static
    {
        $clone = clone $this;
        $clone->hint = $hint;

        return $clone;
    }

    public function default(mixed $value): static
    {
        $clone = clone $this;
        $clone->default = $value;

        return $clone;
    }

    /**
     * @param  string|array<int, string>  $rules
     */
    public function rules(string|array $rules): static
    {
        $clone = clone $this;
        $clone->rules = is_string($rules) ? explode('|', $rules) : $rules;

        return $clone;
    }

    /** Hide the field when $condition is true. */
    public function hidden(bool|Closure $condition = true): static
    {
        $clone = clone $this;
        $clone->hidden = $condition;

        return $clone;
    }

    /** Show the field only when $condition is true (inverse of hidden). */
    public function visible(bool|Closure $condition = true): static
    {
        $clone = clone $this;
        $clone->hidden = is_bool($condition) ? ! $condition : static fn (Closure $get) => ! $condition($get);

        return $clone;
    }

    /** Disable the field when $condition is true. */
    public function disabled(bool|Closure $condition = true): static
    {
        $clone = clone $this;
        $clone->disabled = $condition;

        return $clone;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label !== '' ? $this->label : str($this->name)->headline()->toString();
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getHint(): ?string
    {
        return $this->hint;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    /** @return array<int, string> */
    public function getRules(): array
    {
        $rules = $this->rules;

        if ($this->required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        return $rules;
    }

    public function getType(): string
    {
        return static::class;
    }

    /**
     * Evaluate whether this field should be hidden.
     *
     * @param  Closure(string): mixed  $get  Resolver that returns the current form value for a given field name.
     */
    public function isHidden(Closure $get): bool
    {
        if (is_bool($this->hidden)) {
            return $this->hidden;
        }

        return (bool) ($this->hidden)($get);
    }

    /**
     * Evaluate whether this field should be disabled.
     *
     * @param  Closure(string): mixed  $get  Resolver that returns the current form value for a given field name.
     */
    public function isDisabled(Closure $get): bool
    {
        if (is_bool($this->disabled)) {
            return $this->disabled;
        }

        return (bool) ($this->disabled)($get);
    }

    /**
     * Returns the architect:: namespaced Blade view name for rendering
     * this field. Each concrete field class must implement this.
     */
    abstract public function getViewName(): string;
}
