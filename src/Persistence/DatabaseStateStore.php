<?php

declare(strict_types=1);

namespace Entelechy\Architect\Persistence;

use Entelechy\Architect\Contracts\StateStore;
use Entelechy\Architect\Persistence\Models\ArchitectUserState;
use Illuminate\Database\Eloquent\Builder;

/**
 * Eloquent-backed StateStore used when `architect.state.mode` is
 * `database`. Reads/writes the host-generated state table using the
 * table/connection identity chosen once via `architect:init`.
 */
final class DatabaseStateStore implements StateStore
{
    public function get(int $userId, string $tenantIdentifier, string $scope, string $key): ?array
    {
        $row = $this->query($userId, $tenantIdentifier, $scope, $key)->first();

        if (! $row instanceof ArchitectUserState) {
            return null;
        }

        $payload = $row->getAttribute('payload');

        return is_array($payload) ? $payload : null;
    }

    public function put(int $userId, string $tenantIdentifier, string $scope, string $key, array $payload): void
    {
        ArchitectUserState::query()->updateOrCreate(
            [
                'user_id' => $userId,
                'tenant_identifier' => $tenantIdentifier,
                'scope' => $scope,
                'state_key' => $key,
            ],
            ['payload' => $payload]
        );
    }

    public function forget(int $userId, string $tenantIdentifier, string $scope, string $key): void
    {
        $this->query($userId, $tenantIdentifier, $scope, $key)->delete();
    }

    /**
     * @return Builder<ArchitectUserState>
     */
    private function query(int $userId, string $tenantIdentifier, string $scope, string $key): Builder
    {
        return ArchitectUserState::query()
            ->where('user_id', $userId)
            ->where('tenant_identifier', $tenantIdentifier)
            ->where('scope', $scope)
            ->where('state_key', $key);
    }
}
