<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Fields;

use Closure;
use Entelechy\Architect\Forms\Contracts\ArchitectField;
use Entelechy\Architect\Forms\Validation\ClientValidationMapper;
use Entelechy\Architect\Forms\Validation\Preset;
use Entelechy\Architect\Forms\Validation\Rule;
use Entelechy\Architect\Support\Redaction\Redactable;
use Illuminate\Contracts\Validation\ValidationRule as LaravelValidationRule;

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
    use Redactable;

    protected string $label = '';

    protected bool $required = false;

    protected ?string $placeholder = null;

    protected ?string $hint = null;

    /** Hover-only help text shown next to the field label. Native `title` attribute — no JS tooltip library. */
    protected ?string $tooltip = null;

    protected mixed $default = null;

    /** @var array<int, string> */
    protected array $rules = [];

    protected bool|Closure $hidden = false;

    protected bool|Closure $disabled = false;

    protected ?string $permission = null;

    protected ?Preset $preset = null;

    /** @var array<int, Rule|string> */
    protected array $dslRules = [];

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

    /** Hover-only help text; use hint() for always-visible text below the field instead. */
    public function tooltip(string $text): static
    {
        $clone = clone $this;
        $clone->tooltip = $text;

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

    /**
     * Gate this field behind a permission node — hidden from and, more
     * importantly, non-writable by any user lacking it. Server-enforced:
     * see Entelechy\Architect\Forms\Concerns\SanitizesFormData, which
     * reverts any submitted value for a field whose permission() the
     * current user lacks, regardless of what the client submitted.
     */
    public function permission(string $node): static
    {
        $clone = clone $this;
        $clone->permission = $node;

        return $clone;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    /**
     * Zero-config validation entrypoint (FORMS_FEATURE_PLAN.md Phase 4).
     * With no argument, this is a no-op marker — every shipped field type
     * already computes its own sensible default rules unconditionally via
     * getRules(), so "applying the shipped defaults" requires nothing
     * further. Pass a Preset to layer additional, named rule bundles on
     * top of those defaults (e.g. ->validate(Preset::workEmail())).
     */
    public function validate(?Preset $preset = null): static
    {
        $clone = clone $this;
        $clone->preset = $preset;

        return $clone;
    }

    /**
     * Additively layers Architect DSL Rule objects (or raw rule strings)
     * on top of whatever ->rules()/defaults/preset already produced.
     * Unlike ->rules(), which replaces the field's rule set outright,
     * ->ruleset() never clobbers anything already configured — this is
     * what lets ->validate() and ->ruleset() compose in the same chain.
     *
     * @param  array<int, Rule|string>  $rules
     */
    public function ruleset(array $rules): static
    {
        $clone = clone $this;
        $clone->dslRules = [...$this->dslRules, ...$rules];

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

    public function getTooltip(): ?string
    {
        return $this->tooltip;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    /** @return array<int, string|LaravelValidationRule> */
    public function getRules(): array
    {
        $rules = $this->rules;

        if ($this->required) {
            array_unshift($rules, 'required');
        } else {
            array_unshift($rules, 'nullable');
        }

        if ($this->preset !== null) {
            $rules = [...$rules, ...$this->preset->compile()];
        }

        if ($this->dslRules !== []) {
            $rules = [
                ...$rules,
                ...array_map(
                    static fn (Rule|string $rule): string|LaravelValidationRule => $rule instanceof Rule ? $rule->compile() : $rule,
                    $this->dslRules
                ),
            ];
        }

        return $rules;
    }

    /**
     * HTML5 attributes (required, min, max, pattern, type=email/url, ...)
     * derived from this field's current rule set — the "progressive
     * client-side subset" from FORMS_FEATURE_PLAN.md Phase 4. Purely
     * additive browser-side feedback; server-side validation via
     * getRules() remains authoritative and unchanged regardless of
     * whether a view renders these attributes.
     *
     * @return array<string, string|int|float|bool>
     */
    public function getClientValidationAttributes(): array
    {
        return ClientValidationMapper::toHtmlAttributes($this->getRules());
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
