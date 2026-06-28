<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Concerns;

use Entelechy\Architect\Contracts\PermissionResolver;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared Layer-3 (data scope) gate for TableBuilder data models.
 *
 * Delegates the per-record decision to PermissionResolver::canOnRecord(), so
 * a canActOn() implementation reduces to loading the record and asking the
 * resolver. Returns false for a missing record — the resolver handles any
 * further user checks.
 *
 * Layer 1/2 (node access) is already enforced by the engine before
 * canActOn() runs, so this performs the scope check only.
 */
trait EnforcesRecordScope
{
    protected function scopeAllowsActing(Authenticatable $user, ?Model $record): bool
    {
        return $record !== null
            && app(PermissionResolver::class)->canOnRecord($user, 'act', $record);
    }
}
