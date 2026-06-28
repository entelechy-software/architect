<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\Actions;

use Entelechy\Architect\Supersearch\Actions\Contracts\SearchAction;

/**
 * Copy a value to the clipboard (handled client-side via clipboard API).
 *
 * The `$valueResolver` callable receives the record and returns the string to copy.
 */
final class CopyAction implements SearchAction
{
    /** @var callable(mixed): string */
    private $valueResolver;

    private function __construct(callable $valueResolver)
    {
        $this->valueResolver = $valueResolver;
    }

    public static function make(callable $valueResolver): self
    {
        return new self($valueResolver);
    }

    public function resolveValue(mixed $record): string
    {
        return (string) ($this->valueResolver)($record);
    }

    public function type(): string
    {
        return 'copy';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return ['type' => $this->type()];
    }

    /** @return array<string, mixed> */
    public function toArrayForRecord(mixed $record): array
    {
        return array_merge($this->toArray(), ['value' => $this->resolveValue($record)]);
    }
}
