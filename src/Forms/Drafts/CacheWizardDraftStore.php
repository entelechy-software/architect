<?php

declare(strict_types=1);

namespace Entelechy\Architect\Forms\Drafts;

use Entelechy\Architect\Forms\Contracts\WizardDraftStore;
use Illuminate\Support\Facades\Cache;

/**
 * Default WizardDraftStore implementation — uses Laravel's cache facade,
 * no migration required. TTL is configurable via
 * config('architect.forms.draft_ttl_days'), default 7 days.
 *
 * This is intentionally not a database-table-backed store: that is the
 * territory of the separate Storage Contracts subsystem (see
 * STORAGE_CONTRACTS_PLAN.md) if a host app later wants long-term,
 * queryable draft retention. This default only needs to survive long
 * enough for a user to resume a session.
 */
final class CacheWizardDraftStore implements WizardDraftStore
{
    private function ttlSeconds(): int
    {
        return (int) config('architect.forms.draft_ttl_days', 7) * 86400;
    }

    private function cacheKey(string $key): string
    {
        return "architect:wizard-draft:{$key}";
    }

    public function put(string $key, array $payload): void
    {
        Cache::put($this->cacheKey($key), $payload, $this->ttlSeconds());
    }

    public function get(string $key): ?array
    {
        /** @var array<string, mixed>|null $value */
        $value = Cache::get($this->cacheKey($key));

        return $value;
    }

    public function forget(string $key): void
    {
        Cache::forget($this->cacheKey($key));
    }
}
