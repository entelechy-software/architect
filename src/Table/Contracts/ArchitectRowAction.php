<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Contracts;

/**
 * A one-off, class-based row action registered via
 * TableBuilder::customRowAction().
 *
 * Unlike Entelechy\Architect\Table\Actions\RowAction (which is purely
 * presentational — a link, a custom panel, or a raw browser event),
 * an ArchitectRowAction's handle() runs real PHP against the single
 * row it was invoked for and returns a success/message result that is
 * surfaced to the user as an inline banner. Mirrors ArchitectBulkAction,
 * scoped to one row instead of a selection set.
 *
 * Implementations declare:
 *   - getKey(): unique identifier sent from the client
 *   - getLabel(): button text
 *   - icon(): optional icon name (same convention as RowAction::icon())
 *   - color(): button colour token, e.g. 'primary', 'warning', 'danger'
 *   - confirm(): optional confirmation prompt; null disables confirmation
 *   - permissionNode(): the node required to invoke this action; null
 *     means inherit the table's `modify` permission
 *   - isVisibleFor(): whether the action should render for a given row
 *   - handle(): execute the action against the single row id and the
 *     table's data model
 */
interface ArchitectRowAction
{
    public function getKey(): string;

    public function getLabel(): string;

    public function icon(): ?string;

    /**
     * Colour token used for the action button.
     * e.g. 'primary', 'warning', 'danger'
     */
    public function color(): string;

    public function confirm(): ?string;

    public function permissionNode(): ?string;

    /**
     * Whether this action should be visible for the given row.
     *
     * @param  array<string, mixed>  $row
     */
    public function isVisibleFor(array $row): bool;

    /**
     * @return array{success: bool, message: string}
     */
    public function handle(int $id, ArchitectDataModel $dataModel): array;
}
