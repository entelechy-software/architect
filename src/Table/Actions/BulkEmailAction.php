<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Actions;

use Entelechy\Architect\Table\Contracts\ArchitectBulkAction;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;

/**
 * Stub bulk action: dispatches 'architect:bulk-email' so the host module
 * can open its own compose modal and send a Communication to the people
 * behind the selected rows.
 *
 * Actual modal and send logic lives in the Communications module and is
 * wired up per-table by the module author listening to the browser event.
 * This action ships the plumbing; the Communications integration fills
 * in the compose UI when that module is ready.
 */
final class BulkEmailAction implements ArchitectBulkAction
{
    public static function make(): self
    {
        return new self;
    }

    public function getKey(): string
    {
        return 'email';
    }

    public function getLabel(): string
    {
        return 'Email';
    }

    /** @phpstan-ignore-next-line return.unusedType -- nullable per interface contract */
    public function icon(): ?string
    {
        return 'mail';
    }

    public function color(): string
    {
        return 'primary';
    }

    public function confirm(): ?string
    {
        return null;
    }

    public function requiresReason(): bool
    {
        return false;
    }

    public function permissionNode(): ?string
    {
        return null;
    }

    /**
     * @param  array<int, int>  $selectedIds
     * @return array{success: bool, message: string, count: int}
     */
    public function handle(array $selectedIds, ArchitectDataModel $dataModel, ?string $reason = null): array
    {
        // The engine intercepts 'email' and dispatches architect:bulk-email.
        return [
            'success' => true,
            'message' => 'Email compose opened for '.count($selectedIds).' recipient(s).',
            'count' => count($selectedIds),
        ];
    }
}
