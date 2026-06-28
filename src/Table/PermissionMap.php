<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table;

/**
 * The four permission nodes declared per TableBuilder definition.
 *
 * Per the Architect permission model (Decision 1, Layer 2):
 *  - read   : view the index, view a single record, render the form (read-only)
 *  - create : invoke the create endpoint
 *  - modify : invoke the update endpoint and edit-mode form
 *  - remove : archive (or hard-delete, where the table opts in)
 *
 * Each maps to a node string in the form `{module}.{feature}.{action}`,
 * e.g. `activity_committees.read`. Nodes are checked against the
 * authenticated user via PermissionResolver.
 */
final readonly class PermissionMap
{
    public function __construct(
        public string $read,
        public string $create,
        public string $modify,
        public string $remove,
    ) {}
}
