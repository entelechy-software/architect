<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Contracts;

use Entelechy\Architect\Table\QueryContext;

/**
 * Optional contract for data models that can produce a lightweight
 * fingerprint representing the current filtered dataset.
 */
interface SupportsAutoRefreshFingerprint
{
    /**
     * Return a stable scalar fingerprint for the current context.
     *
     * Returning null signals "fingerprint unsupported for this context",
     * and the engine will fall back to normal full refresh behaviour.
     */
    public function refreshFingerprint(QueryContext $context, string $fingerprintOn): string|int|null;
}
