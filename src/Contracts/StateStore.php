<?php

declare(strict_types=1);

namespace Entelechy\Architect\Contracts;

/**
 * Persists small per-user, per-tenant, per-scope JSON blobs of UI state
 * (e.g. remembered table filters, bookmarked filters, hidden columns).
 *
 * The active implementation is selected at runtime strictly from the
 * locked `architect.state.mode` setup key (see
 * `Entelechy\Architect\ArchitectServiceProvider::register()`), so callers
 * never need to branch on persistence mode themselves.
 */
interface StateStore
{
    /**
     * Fetch a previously stored payload, or null when nothing is stored
     * for this key (or when the active store is a no-op, e.g.
     * localStorage mode).
     *
     * @return array<string, mixed>|null
     */
    public function get(int $userId, string $tenantIdentifier, string $scope, string $key): ?array;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function put(int $userId, string $tenantIdentifier, string $scope, string $key, array $payload): void;

    public function forget(int $userId, string $tenantIdentifier, string $scope, string $key): void;
}
