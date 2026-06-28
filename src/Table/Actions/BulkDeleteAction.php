<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Actions;

use Entelechy\Architect\Table\Contracts\ArchitectBulkAction;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;

/**
 * Permanently (force) deletes every selected row via the data model's
 * delete() method.
 *
 * The confirm message is intentionally stark because this is irreversible.
 * Permission defaults to null — the engine resolves this to the table's
 * `remove` node at invocation time.
 */
final class BulkDeleteAction implements ArchitectBulkAction
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
        return 'delete';
    }

    public function getLabel(): string
    {
        return 'Delete';
    }

    /** @phpstan-ignore-next-line return.unusedType -- nullable per interface contract */
    public function icon(): ?string
    {
        return 'trash';
    }

    public function color(): string
    {
        return 'danger';
    }

    /** @phpstan-ignore-next-line return.unusedType -- nullable per interface contract */
    public function confirm(): ?string
    {
        return 'Permanently delete all selected records? This cannot be undone.';
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
            $dataModel->delete($id, $reason);
            $count++;
        }

        return [
            'success' => true,
            'message' => "Deleted {$count} record(s) permanently.",
            'count' => $count,
        ];
    }
}
