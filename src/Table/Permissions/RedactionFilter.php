<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Permissions;

use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Table\Column;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Applies column-level redaction (Column::redact()) to rendered rows.
 *
 * Runs after {@see FieldVisibilityFilter} in every rendering path (index
 * table, single-row refresh, print, and every export format): visibility
 * decides whether a column's key is present at all; redaction decides
 * whether the value under a still-visible key is the real value or a
 * masked stand-in.
 *
 * Security note: masking happens here, server-side, before the row array
 * ever reaches Blade or a Livewire response payload. There is no
 * client-side hide-then-reveal of the true value — reveal (see
 * {@see canReveal}) is a fresh authorised fetch of that single cell,
 * never a client-side unmask of data already sent to the browser.
 */
final readonly class RedactionFilter
{
    public function __construct(private PermissionResolver $engine) {}

    /**
     * @param  list<Column>  $columns
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function redactRow(?Authenticatable $user, array $columns, array $row): array
    {
        foreach ($columns as $column) {
            if (! $column->isRedacted()) {
                continue;
            }

            $key = $column->getKey();
            if (! array_key_exists($key, $row) || $row[$key] === null) {
                continue;
            }

            $bypass = $column->getRedactUnlessPermission();
            if ($bypass !== null && $this->engine->can($user, $bypass)) {
                continue;
            }

            $row[$key] = $column->applyRedaction($row[$key]);
        }

        return $row;
    }

    /**
     * Whether $user may reveal $column's real value on demand (click-to-reveal).
     * Never true for a column that isn't redacted or wasn't marked revealable().
     */
    public function canReveal(?Authenticatable $user, Column $column): bool
    {
        if (! $column->isRedacted() || ! $column->isRevealable()) {
            return false;
        }

        $permission = $column->getRevealPermission();

        return $permission !== null && $this->engine->can($user, $permission);
    }
}
