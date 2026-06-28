<?php

declare(strict_types=1);

namespace Entelechy\Architect\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface PermissionResolver
{
    /**
     * Return true if the authenticated user may perform the given action.
     *
     * @param  string  $node  Dot-notation permission node, e.g. 'members.view'
     */
    public function can(?Authenticatable $user, string $node): bool;

    /**
     * Return true if the user may perform the action on a specific Eloquent record.
     */
    public function canOnRecord(?Authenticatable $user, string $action, mixed $record): bool;
}
