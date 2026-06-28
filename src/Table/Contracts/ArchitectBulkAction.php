<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Contracts;

/**
 * A bulk action invoked from the floating action bar that appears when
 * the user has rows selected.
 *
 * Implementations declare:
 *   - getKey(): unique identifier sent from the client
 *   - getLabel(): button text
 *   - icon(): optional Tabler icon class (without the leading 'ti ti-')
 *   - confirm(): optional confirmation prompt rendered in the UI before
 *     the action fires; null disables confirmation
 *   - permissionNode(): the node required to invoke this action; null
 *     means inherit the table's `modify` permission
 *   - handle(): execute the action against the selected ids and the
 *     table's data model
 */
interface ArchitectBulkAction
{
    public function getKey(): string;

    public function getLabel(): string;

    public function icon(): ?string;

    /**
     * Colour token used for the button in the bulk action bar.
     * e.g. 'primary', 'warning', 'danger'
     */
    public function color(): string;

    public function confirm(): ?string;

    /**
     * When true the engine intercepts the action and opens a reason-entry
     * modal before executing. The typed reason is passed to handle() via
     * the data model (delete($id, $reason) / archive($id, $reason)).
     */
    public function requiresReason(): bool;

    public function permissionNode(): ?string;

    /**
     * @param  array<int, int>  $selectedIds
     * @return array{success: bool, message: string, count: int}
     */
    public function handle(array $selectedIds, ArchitectDataModel $dataModel, ?string $reason = null): array;
}
