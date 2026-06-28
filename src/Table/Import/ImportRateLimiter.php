<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Import;

use Entelechy\Architect\Contracts\TenantResolver;
use Entelechy\Architect\Table\Import\Models\ImportBatch;
use Illuminate\Support\Carbon;

/**
 * Per-user and per-tenant rolling-window rate limit for CSV imports.
 *
 * Backed directly by the import_batches table — no separate cache
 * or counter needed, because every batch is already persisted for
 * audit. Cost: two indexed COUNT(*) queries per upload attempt,
 * which is well below any sensible upload rate.
 *
 * Returned RateLimitResult objects encode both the verdict and the
 * human-readable reason, so the wizard can surface the same string
 * directly in the UI.
 */
final class ImportRateLimiter
{
    /**
     * Combined check: returns the first failing limit (user, then
     * tenant). Returns null when both limits are satisfied.
     */
    public function check(
        int $userId,
        string $definitionClass,
        ImportDefinition $importDef,
    ): ?string {
        if ($message = $this->checkUser($userId, $definitionClass, $importDef)) {
            return $message;
        }

        return $this->checkUnion($definitionClass, $importDef);
    }

    /**
     * Per-user limit. Returns the user-facing error message when
     * exceeded, null when under the limit.
     */
    public function checkUser(
        int $userId,
        string $definitionClass,
        ImportDefinition $importDef,
    ): ?string {
        $limit = $importDef->rateLimitUser;
        $since = $this->parseWindow($limit['period']);

        $count = ImportBatch::countForUserWithin($userId, $definitionClass, $since);

        if ($count >= $limit['attempts']) {
            return sprintf(
                'You have reached the per-user import limit (%d imports per %s). Please try again later.',
                $limit['attempts'],
                $limit['period'],
            );
        }

        return null;
    }

    /**
     * Per-tenant limit. Returns the user-facing error message when
     * exceeded, null when under the limit.
     */
    public function checkUnion(
        string $definitionClass,
        ImportDefinition $importDef,
    ): ?string {
        $limit = $importDef->rateLimitUnion;
        $since = $this->parseWindow($limit['period']);

        $tenantIdentifier = app(TenantResolver::class)->currentIdentifier();
        $count = ImportBatch::countForTenantWithin($definitionClass, $since, $tenantIdentifier);

        if ($count >= $limit['attempts']) {
            return sprintf(
                'Your union has reached the import limit (%d imports per %s). Please try again later.',
                $limit['attempts'],
                $limit['period'],
            );
        }

        return null;
    }

    /**
     * Parse a period string ("24 hours", "7 days", "30 minutes")
     * into a Carbon instance representing the start of the window.
     */
    private function parseWindow(string $period): Carbon
    {
        return Carbon::parse('-'.$period);
    }
}
