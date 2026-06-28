<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\Actions\Concerns;

use Entelechy\Architect\Supersearch\Actions\Contracts\SearchAction;

/**
 * Shared resolution helper: most SearchAction implementations expose an
 * optional `toArrayForRecord(mixed $record): array` method (duck-typed, not
 * part of the SearchAction contract, since a few actions have nothing
 * record-dependent to resolve). Composing actions (ChainAction, ConfirmAction)
 * and ResultCard::renderFor() all need to resolve an arbitrary wrapped
 * action the same way — falling back to toArray() when there's nothing to
 * resolve.
 */
trait ResolvesForRecord
{
    /** @return array<string, mixed> */
    private static function resolveActionForRecord(SearchAction $action, mixed $record): array
    {
        return method_exists($action, 'toArrayForRecord')
            ? $action->toArrayForRecord($record)
            : $action->toArray();
    }
}
