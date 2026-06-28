<?php

declare(strict_types=1);

namespace Entelechy\Architect\Content\Entries;

use Closure;
use Entelechy\Architect\Content\Contracts\ArchitectEntry;

/**
 * Abstract base for every Content entry.
 *
 * An entry is a value object: created via the static make() factory,
 * configured via fluent setters that each return a clone, then frozen
 * once handed to ContentBuilder::structure()/entry(). Mirrors the
 * immutable-clone convention used by Forms\Fields\Field.
 */
abstract class Entry implements ArchitectEntry
{
    protected string $label = '';

    protected ?Closure $formatUsing = null;

    protected mixed $default = null;

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

    public function formatUsing(Closure $callback): static
    {
        $clone = clone $this;
        $clone->formatUsing = $callback;

        return $clone;
    }

    public function default(mixed $value): static
    {
        $clone = clone $this;
        $clone->default = $value;

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

    public function resolveValue(mixed $record): mixed
    {
        $raw = data_get($record, $this->name, $this->default);

        return $this->formatUsing ? ($this->formatUsing)($raw, $record) : $raw;
    }
}
