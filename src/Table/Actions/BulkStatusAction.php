<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Actions;

use Entelechy\Architect\Table\Contracts\ArchitectBulkAction;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;

/**
 * Stub bulk action: dispatches 'architect:bulk-status' so the host module
 * can open a state-picker UI and transition the selected records to a new
 * status in a single operation.
 *
 * The available target statuses are declared per-table via the `options`
 * key in the bulkActions() config array and are forwarded in the browser
 * event payload for the host module's Alpine/Livewire handler to consume.
 *
 * Example:
 *   ->bulkActions(['status' => ['options' => ['open', 'closed', 'pending']]])
 */
final class BulkStatusAction implements ArchitectBulkAction
{
    /** @var list<string> */
    private array $options = [];

    public static function make(): self
    {
        return new self;
    }

    /**
     * @param  list<string>  $options  Available target status values.
     */
    public function withOptions(array $options): self
    {
        $clone = clone $this;
        $clone->options = $options;

        return $clone;
    }

    /** @return list<string> */
    public function options(): array
    {
        return $this->options;
    }

    public function getKey(): string
    {
        return 'status';
    }

    public function getLabel(): string
    {
        return 'Change Status';
    }

    /** @phpstan-ignore-next-line return.unusedType -- nullable per interface contract */
    public function icon(): ?string
    {
        return 'circle-check';
    }

    public function color(): string
    {
        return 'secondary';
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
        // The engine intercepts 'status' and dispatches architect:bulk-status.
        return [
            'success' => true,
            'message' => 'Status picker opened for '.count($selectedIds).' record(s).',
            'count' => count($selectedIds),
        ];
    }
}
