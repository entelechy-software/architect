<?php

declare(strict_types=1);

namespace Entelechy\Architect\Permissions;

use Entelechy\Architect\Contracts\PermissionResolver;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Default resolver — allows everything.
 *
 * Suitable for apps that implement their own gate at the route level
 * and do not need field- or record-level permission checks inside Architect.
 * Swap this in config/architect.php or via AppServiceProvider::register().
 */
final class AllowAllPermissionResolver implements PermissionResolver
{
    public function can(?Authenticatable $user, string $node): bool
    {
        return true;
    }

    public function canOnRecord(?Authenticatable $user, string $action, mixed $record): bool
    {
        return true;
    }
}
