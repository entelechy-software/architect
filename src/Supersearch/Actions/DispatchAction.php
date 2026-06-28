<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\Actions;

use Entelechy\Architect\Supersearch\Actions\Contracts\SearchAction;

/**
 * Dispatch a browser/Livewire custom event.
 *
 * The optional `$payloadResolver` callable receives the record and returns
 * an array merged into the base payload.
 */
final class DispatchAction implements SearchAction
{
    /** @var (callable(mixed): array<string, mixed>)|null */
    private $payloadResolver = null;

    /** @param  array<string, mixed>  $payload */
    private function __construct(
        private readonly string $event,
        private readonly array $payload = [],
    ) {}

    /** @param  array<string, mixed>  $payload */
    public static function make(string $event, array $payload = []): self
    {
        return new self($event, $payload);
    }

    /**
     * Provide a callable that adds record-specific payload fields.
     *
     * @param  callable(mixed): array<string, mixed>  $resolver
     */
    public function payload(callable $resolver): self
    {
        $clone = clone $this;
        $clone->payloadResolver = $resolver;

        return $clone;
    }

    /** @return array<string, mixed> */
    public function resolvePayload(mixed $record): array
    {
        $extra = isset($this->payloadResolver) ? ($this->payloadResolver)($record) : [];

        return array_merge($this->payload, $extra);
    }

    public function type(): string
    {
        return 'dispatch';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'event' => $this->event,
            'payload' => $this->payload,
        ];
    }

    /** @return array<string, mixed> */
    public function toArrayForRecord(mixed $record): array
    {
        return array_merge($this->toArray(), [
            'payload' => $this->resolvePayload($record),
        ]);
    }
}
