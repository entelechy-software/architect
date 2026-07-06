<?php

declare(strict_types=1);

namespace Entelechy\Architect\Persistence;

use Entelechy\Architect\Contracts\StateStore;

/**
 * No-op StateStore used when `architect.state.mode` is `localStorage`.
 *
 * In this mode the browser owns all UI-state persistence (see
 * resources/js/components/moduleTable.js); the server never reads or
 * writes it, so every method here is intentionally inert.
 */
final class LocalStateStore implements StateStore
{
    public function get(int $userId, string $tenantIdentifier, string $scope, string $key): ?array
    {
        return null;
    }

    public function put(int $userId, string $tenantIdentifier, string $scope, string $key, array $payload): void
    {
        // Intentionally inert — localStorage mode is fully client-owned.
    }

    public function forget(int $userId, string $tenantIdentifier, string $scope, string $key): void
    {
        // Intentionally inert — localStorage mode is fully client-owned.
    }
}
