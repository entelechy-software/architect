<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\Actions;

use Entelechy\Architect\Supersearch\Actions\Concerns\ResolvesForRecord;
use Entelechy\Architect\Supersearch\Actions\Contracts\SearchAction;

/**
 * Execute multiple actions in sequence (e.g. copy + href).
 */
final class ChainAction implements SearchAction
{
    use ResolvesForRecord;

    /** @param list<SearchAction> $actions */
    private function __construct(private readonly array $actions) {}

    /** @param list<SearchAction> $actions */
    public static function make(array $actions): self
    {
        return new self($actions);
    }

    public function type(): string
    {
        return 'chain';
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'type' => $this->type(),
            'actions' => array_map(fn (SearchAction $a) => $a->toArray(), $this->actions),
        ];
    }

    /** @return array<string, mixed> */
    public function toArrayForRecord(mixed $record): array
    {
        return [
            'type' => $this->type(),
            'actions' => array_map(
                fn (SearchAction $a) => self::resolveActionForRecord($a, $record),
                $this->actions,
            ),
        ];
    }
}
