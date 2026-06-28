<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Permissions;

use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\PermissionMap;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Enforces Layers 1, 2, and 3 of the Architect permission model on every
 * TableBuilder engine entry point.
 *
 * Module authors have no opt-out: the engine instantiates this gate
 * with the active definition and calls assertCan{Read,Create,Modify,Remove}
 * before any data operation. A failure throws AuthorizationException
 * (HTTP 403) without revealing whether the record exists.
 */
final readonly class PermissionGate
{
    public function __construct(private PermissionResolver $engine) {}

    public function assertCanRead(?Authenticatable $user, ArchitectTableDefinition $def): void
    {
        $this->require($user, $def->permissions->read);
    }

    public function assertCanCreate(?Authenticatable $user, ArchitectTableDefinition $def): void
    {
        $this->require($user, $def->permissions->create);
    }

    public function assertCanModify(?Authenticatable $user, ArchitectTableDefinition $def): void
    {
        $this->require($user, $def->permissions->modify);
    }

    public function assertCanRemove(?Authenticatable $user, ArchitectTableDefinition $def): void
    {
        $this->require($user, $def->permissions->remove);
    }

    /**
     * Layer 3 (data scope) gate. Combines the action's node check with
     * the data model's per-record canActOn() decision.
     */
    public function assertCanActOnRecord(
        ?Authenticatable $user,
        ArchitectTableDefinition $def,
        ArchitectDataModel $dataModel,
        string $action,
        int $recordId,
    ): void {
        $node = $this->nodeForAction($def->permissions, $action);
        $this->require($user, $node);

        if (! $user instanceof Model) {
            throw new AuthorizationException('Not authorised.');
        }

        if (! $dataModel->canActOn($user, $recordId)) {
            throw new AuthorizationException('Not authorised.');
        }
    }

    public function userCan(?Authenticatable $user, string $node): bool
    {
        return $this->engine->can($user, $node);
    }

    private function nodeForAction(PermissionMap $perms, string $action): string
    {
        return match ($action) {
            'read' => $perms->read,
            'create' => $perms->create,
            'modify' => $perms->modify,
            'remove' => $perms->remove,
            default => throw new \InvalidArgumentException("Unknown action '{$action}'"),
        };
    }

    private function require(?Authenticatable $user, string $node): void
    {
        if (! $this->engine->can($user, $node)) {
            throw new AuthorizationException("Missing permission: {$node}");
        }
    }
}
