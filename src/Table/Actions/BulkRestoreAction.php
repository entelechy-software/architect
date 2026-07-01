<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Actions;

use Entelechy\Architect\Table\Contracts\ArchitectBulkAction;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;

/**
 * Restores every selected archived row via the data model's restore() method.
 *
 * Natural counterpart to BulkArchiveAction. Permission defaults to null —
 * the engine resolves this to the table's `modify` node (same as a restore
 * on a single row) at invocation time.
 */
final class BulkRestoreAction implements ArchitectBulkAction
{
    public static function make(): self
    {
        return new self;
    }

    public function getKey(): string
    {
        return 'restore';
    }

    public function getLabel(): string
    {
        return 'Restore';
    }

    /** @phpstan-ignore-next-line return.unusedType -- nullable per interface contract */
    public function icon(): ?string
    {
        return 'rotate';
    }

    public function color(): string
    {
        return 'success';
    }

    /** @phpstan-ignore-next-line return.unusedType -- nullable per interface contract */
    public function confirm(): ?string
    {
        return 'Restore all selected records?';
    }

    public function requiresReason(): bool
    {
        return false;
    }

    public function requiresPhrase(): bool
    {
        return false;
    }

    public function getPhrase(): ?string
    {
        return null;
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
        $count = 0;

        foreach ($selectedIds as $id) {
            $dataModel->restore($id);
            $count++;
        }

        return [
            'success' => true,
            'message' => "Restored {$count} record(s).",
            'count' => $count,
        ];
    }
}
