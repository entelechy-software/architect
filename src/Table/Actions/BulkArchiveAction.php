<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Actions;

use Entelechy\Architect\Table\Contracts\ArchitectBulkAction;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;

/**
 * Archives every selected row via the data model's archive() method.
 *
 * Permission node defaults to null — the engine resolves this to the
 * table's `remove` node at invocation time so module authors do not
 * have to repeat themselves.
 */
final class BulkArchiveAction implements ArchitectBulkAction
{
    private bool $reasonRequired = false;

    public static function make(): self
    {
        return new self;
    }

    /**
     * Mark this action as requiring a typed reason before executing.
     * Called internally by the TableBuilder parser — not used in definition files.
     */
    public function withReasonRequired(): self
    {
        $clone = clone $this;
        $clone->reasonRequired = true;

        return $clone;
    }

    public function getKey(): string
    {
        return 'archive';
    }

    public function getLabel(): string
    {
        return 'Archive';
    }

    /** @phpstan-ignore-next-line return.unusedType -- nullable per interface contract */
    public function icon(): ?string
    {
        return 'archive';
    }

    public function color(): string
    {
        return 'warning';
    }

    /** @phpstan-ignore-next-line return.unusedType -- nullable per interface contract */
    public function confirm(): ?string
    {
        return 'Archive all selected records? They can be restored later.';
    }

    public function requiresReason(): bool
    {
        return $this->reasonRequired;
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
            $dataModel->archive($id, $reason);
            $count++;
        }

        return [
            'success' => true,
            'message' => "Archived {$count} record(s).",
            'count' => $count,
        ];
    }
}
