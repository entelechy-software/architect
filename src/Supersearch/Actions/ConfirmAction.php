<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\Actions;

use Entelechy\Architect\Supersearch\Actions\Concerns\ResolvesForRecord;
use Entelechy\Architect\Supersearch\Actions\Contracts\SearchAction;

/**
 * Show a confirmation dialog before executing a follow-up action.
 */
final class ConfirmAction implements SearchAction
{
    use ResolvesForRecord;

    private ?SearchAction $then = null;

    private function __construct(private readonly string $message) {}

    public static function make(string $message): self
    {
        return new self($message);
    }

    /**
     * The action to execute when the user confirms.
     */
    public function then(SearchAction $action): self
    {
        $clone = clone $this;
        $clone->then = $action;

        return $clone;
    }

    public function type(): string
    {
        return 'confirm';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'message' => $this->message,
            'then' => $this->then?->toArray(),
        ];
    }

    /** @return array<string, mixed> */
    public function toArrayForRecord(mixed $record): array
    {
        return [
            'type' => $this->type(),
            'message' => $this->message,
            'then' => $this->then !== null
                ? self::resolveActionForRecord($this->then, $record)
                : null,
        ];
    }
}
