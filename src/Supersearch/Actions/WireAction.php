<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\Actions;

use Entelechy\Architect\Supersearch\Actions\Contracts\SearchAction;

/**
 * Call a Livewire component method via `$wire.call()`.
 *
 * The optional `$paramsResolver` callable receives the record and returns
 * an array of positional arguments.
 */
final class WireAction implements SearchAction
{
    /** @var (callable(mixed): array<int|string, mixed>)|null */
    private $paramsResolver = null;

    /**
     * @param  array<int|string, mixed>  $params
     */
    private function __construct(
        private readonly string $method,
        private readonly array $params = [],
    ) {}

    /** @param  array<int|string, mixed>  $params */
    public static function make(string $method, array $params = []): self
    {
        return new self($method, $params);
    }

    /**
     * Provide a callable that returns record-specific method arguments.
     *
     * @param  callable(mixed): array<int|string, mixed>  $resolver
     */
    public function params(callable $resolver): self
    {
        $clone = clone $this;
        $clone->paramsResolver = $resolver;

        return $clone;
    }

    /** @return array<int|string, mixed> */
    public function resolveParams(mixed $record): array
    {
        $extra = isset($this->paramsResolver) ? ($this->paramsResolver)($record) : [];

        return array_merge($this->params, $extra);
    }

    public function type(): string
    {
        return 'wire';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'method' => $this->method,
            'params' => $this->params,
        ];
    }

    /** @return array<string, mixed> */
    public function toArrayForRecord(mixed $record): array
    {
        return array_merge($this->toArray(), [
            'params' => $this->resolveParams($record),
        ]);
    }
}
