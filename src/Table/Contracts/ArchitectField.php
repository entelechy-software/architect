<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Contracts;

/**
 * Abstract base for every field type in the TableBuilder form panel.
 *
 * A field is a value object: created via the static make() factory,
 * configured via fluent setters that each return $this, then frozen
 * when the parent ArchitectTableDefinition is built.
 *
 * Subclasses declare:
 *   - blade(): which partial renders this field
 *   - validationRules(): the Laravel rule set for this field's value
 *   - inputName(): defaults to $name; overridden by lookup multi etc.
 */
abstract class ArchitectField implements HasVisibleWhen
{
    protected string $label;

    protected bool $required = false;

    protected bool $onCreate = true;

    protected bool $onEdit = true;

    /** @var string|null The permission node required to see this field; null = always visible. */
    protected ?string $visibleTo = null;

    protected ?string $hint = null;

    /**
     * Example/guide value rendered as the HTML `placeholder`
     * attribute on form inputs (text, date, textarea, select).
     * Empty string == no placeholder rendered.
     */
    protected string $placeholder = '';

    /** @var array<int, array{field: string, op: string, value: mixed}> */
    protected array $visibleWhen = [];

    final protected function __construct(protected readonly string $name)
    {
        $this->label = $this->humanise($name);
    }

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

    public function required(bool $required = true): static
    {
        $clone = clone $this;
        $clone->required = $required;

        return $clone;
    }

    public function onCreate(bool $only = true): static
    {
        $clone = clone $this;
        $clone->onCreate = true;
        $clone->onEdit = ! $only;

        return $clone;
    }

    public function onEdit(bool $only = true): static
    {
        $clone = clone $this;
        $clone->onEdit = true;
        $clone->onCreate = ! $only;

        return $clone;
    }

    public function hint(string $hint): static
    {
        $clone = clone $this;
        $clone->hint = $hint;

        return $clone;
    }

    /**
     * Set the input placeholder/example shown to users.
     *
     * Rendered as the HTML `placeholder` attribute on text-style
     * inputs. The same value is used as the example row in the
     * import wizard's CSV template when the matching Column also
     * has a placeholder set.
     */
    public function placeholder(string $value): static
    {
        $clone = clone $this;
        $clone->placeholder = $value;

        return $clone;
    }

    /**
     * Restrict visibility to users holding the named permission node.
     *
     * Enforced server-side: the engine strips fields the user lacks
     * permission for from rendered HTML and from API responses. There
     * is no client-side hide that could be bypassed.
     */
    public function visibleTo(string $node): static
    {
        $clone = clone $this;
        $clone->visibleTo = $node;

        return $clone;
    }

    /**
     * Conditional visibility within the form: only render this field
     * when another field's value matches. Generates Alpine x-show.
     */
    public function visibleWhen(string $field, string $op, mixed $value): static
    {
        $this->visibleWhen[] = ['field' => $field, 'op' => $op, 'value' => $value];

        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function inputName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getHint(): ?string
    {
        return $this->hint;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    public function shownOnCreate(): bool
    {
        return $this->onCreate;
    }

    public function shownOnEdit(): bool
    {
        return $this->onEdit;
    }

    public function getVisibleTo(): ?string
    {
        return $this->visibleTo;
    }

    /**
     * @return array<int, array{field: string, op: string, value: mixed}>
     */
    public function getVisibleWhen(): array
    {
        return $this->visibleWhen;
    }

    /**
     * The Blade partial under resources/views/module-table/fields/
     * that renders this field type. Subclasses must override.
     */
    abstract public function blade(): string;

    /**
     * Laravel validation rule set for this field's submitted value.
     * Subclasses build on top of the base required/optional logic.
     *
     * @return array<int, string>
     */
    public function validationRules(): array
    {
        return $this->required ? ['required'] : ['nullable'];
    }

    private function humanise(string $name): string
    {
        return ucwords(str_replace(['_', '-'], ' ', $name));
    }
}
