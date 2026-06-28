<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Contracts;

/**
 * Marks an Eloquent model as supporting the TableBuilder "view all" panel.
 *
 * When an Engine table row has a 'view' RowAction and the bound Eloquent
 * model implements this interface, clicking "View Details" opens the
 * persistent panel in view-only mode rather than dispatching the generic
 * 'row-action:view' browser event.
 *
 * The developer controls which fields are shown and how values are
 * formatted — no column definitions or type mappings are required.
 *
 * Example:
 *   public function viewAll(): array
 *   {
 *       return [
 *           ['label' => 'Full Name',          'value' => $this->name],
 *           ['label' => 'Anonymous Member',   'value' => $this->anonymous_member ? 'Yes' : 'No'],
 *           ['label' => 'Joined',             'value' => $this->created_at->format('d M Y')],
 *       ];
 *   }
 */
interface HasViewAll
{
    /**
     * Return a flat list of label/value pairs for the view-only panel.
     *
     * Labels should be human-readable (not DB column names).
     * Values should already be formatted for display — cast booleans
     * to strings, format dates, resolve enum labels, etc.
     *
     * @return list<array{label: string, value: mixed}>
     */
    public function viewAll(): array;
}
