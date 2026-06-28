<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\Actions;

use Entelechy\Architect\Supersearch\Actions\Contracts\SearchAction;
use Illuminate\Database\Eloquent\Model;

/**
 * Open an Architect form panel (create or edit).
 *
 * The `$recordResolver` callable receives the result record and returns the
 * record that should be loaded into the panel (often the same record).
 */
final class PanelAction implements SearchAction
{
    private string $mode = 'edit';

    /** @var callable(mixed): mixed|null */
    private $recordResolver = null;

    private function __construct(private readonly string $definitionClass) {}

    public static function make(string $definitionClass): self
    {
        return new self($definitionClass);
    }

    /** Open the panel in create mode rather than edit mode. */
    public function create(): self
    {
        $clone = clone $this;
        $clone->mode = 'create';

        return $clone;
    }

    /** Open the panel in edit mode (default). */
    public function edit(): self
    {
        $clone = clone $this;
        $clone->mode = 'edit';

        return $clone;
    }

    /**
     * Provide a callable that resolves the record to load into the panel.
     *
     * @param  callable(mixed): mixed  $resolver
     */
    public function record(callable $resolver): self
    {
        $clone = clone $this;
        $clone->recordResolver = $resolver;

        return $clone;
    }

    public function resolveRecordId(mixed $record): int|string|null
    {
        if (! isset($this->recordResolver)) {
            return $record instanceof Model ? $record->getKey() : null;
        }

        $resolved = ($this->recordResolver)($record);

        if ($resolved instanceof Model) {
            return $resolved->getKey();
        }

        return is_int($resolved) || is_string($resolved) ? $resolved : null;
    }

    public function type(): string
    {
        return 'panel';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'definitionClass' => $this->definitionClass,
            'mode' => $this->mode,
        ];
    }

    /** @return array<string, mixed> */
    public function toArrayForRecord(mixed $record): array
    {
        return array_merge($this->toArray(), [
            'recordId' => $this->resolveRecordId($record),
        ]);
    }
}
