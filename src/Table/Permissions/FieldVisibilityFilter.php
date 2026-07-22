<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Permissions;

use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Column;
use Entelechy\Architect\Table\Contracts\ArchitectField;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Applies Layer 4 (field visibility) of the Architect permission model to
 * TableBuilder definitions.
 *
 * Two concerns:
 *   - visibleFields(): returns the subset of form fields the current user
 *     may see. Hidden fields are omitted from rendered HTML and from any
 *     API payload returned to the client.
 *   - visibleColumns(): same treatment for index-page columns.
 *   - stripRow(): given a forList() row, removes keys for columns whose
 *     visibleTo node the user lacks.
 *
 * Enforcement is server-side only — there is no client-side hide that
 * could be bypassed by inspecting the network response.
 */
final readonly class FieldVisibilityFilter
{
    public function __construct(private PermissionResolver $engine) {}

    /**
     * @return list<ArchitectField>
     */
    public function visibleFields(?Authenticatable $user, ArchitectTableDefinition $def): array
    {
        return array_values(array_filter(
            $def->fields,
            fn (ArchitectField $field): bool => $this->isVisible($user, $field->getVisibleTo())
        ));
    }

    /**
     * @return list<Column>
     */
    public function visibleColumns(?Authenticatable $user, ArchitectTableDefinition $def): array
    {
        return array_values(array_filter(
            $def->columns,
            fn (Column $column): bool => ! $column->isHiddenOnIndex() && $this->isVisible($user, $column->getVisibleTo())
        ));
    }

    /**
     * Strip keys the user may not see from a single row of forList() output.
     *
     * Convenience wrapper that recomputes the visible-column set every call.
     * Hot loops (render, exporters, print) should compute the column list
     * once via {@see visibleColumns()}, derive a flipped allow-key map via
     * {@see allowedKeysForRow()}, and call {@see stripRowUsingAllowed()} per
     * row to avoid the per-row PermissionResolver churn.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function stripRow(?Authenticatable $user, ArchitectTableDefinition $def, array $row): array
    {
        $columns = $this->visibleColumns($user, $def);

        return $this->stripRowUsingAllowed($row, $this->allowedKeysForRow($columns));
    }

    /**
     * Build the flipped allow-key lookup for a pre-resolved column list.
     *
     * Engine and exporters compute this once per render/export pass and
     * reuse it for every row, replacing the per-row recomputation that
     * stripRow() used to perform.
     *
     * @param  list<Column>  $columns  Already filtered by visibleColumns()
     * @return array<string, int> array_flip-style lookup keyed by column name
     */
    public function allowedKeysForRow(array $columns): array
    {
        // Always keep the primary key — the client needs it for row actions.
        $allowed = ['id' => 0, 'archived' => 0];

        foreach ($columns as $col) {
            $key = $col->getKey();
            $allowed[$key] = 0;

            // Keep edit keys (FK column names like activity_id, member_id) so
            // the inline row-edit blade can pre-populate rowEdit.values.
            $editKey = $col->getEditKey();
            if ($editKey !== $key) {
                $allowed[$editKey] = 0;
            }
        }

        return $allowed;
    }

    /**
     * Strip a row to the supplied allow-key lookup.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, int>  $allowedFlip
     * @return array<string, mixed>
     */
    public function stripRowUsingAllowed(array $row, array $allowedFlip): array
    {
        return array_intersect_key($row, $allowedFlip);
    }

    /**
     * Strip keys the user may not see from a forForm() payload.
     *
     * Most tables define their editable fields via ->column()->type()
     * rather than the legacy ->field() list (which the form-panel view
     * no longer renders), so the allow-list must also include visible
     * modify-mode columns — otherwise every column-only table would have
     * its edit payload reduced to just 'id'.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function stripForm(?Authenticatable $user, ArchitectTableDefinition $def, array $payload): array
    {
        $allowedKeys = array_map(
            fn (ArchitectField $f): string => $f->name(),
            $this->visibleFields($user, $def)
        );

        foreach ($def->getModifyColumns() as $column) {
            if ($this->isVisible($user, $column->visibilityNodeForMode(false))) {
                $allowedKeys[] = $column->getEditKey();
            }
        }

        $allowedKeys[] = 'id';

        return array_intersect_key($payload, array_flip($allowedKeys));
    }

    private function isVisible(?Authenticatable $user, ?string $node): bool
    {
        if ($node === null) {
            return true;
        }

        return $this->engine->can($user, $node);
    }
}
