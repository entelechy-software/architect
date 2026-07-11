<?php

declare(strict_types=1);

namespace Entelechy\Architect\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eloquent model for the host-generated state table (identity and
 * connection are chosen once, permanently, via `architect:init` — see
 * ArchitectInitCommand::generateStateMigration()).
 *
 * The table name and connection are resolved from config('architect.state')
 * at call time rather than hardcoded, since both are project-specific
 * decisions locked in during initialization.
 *
 * @property array<string, mixed>|null $payload
 */
class ArchitectUserState extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'tenant_identifier',
        'scope',
        'state_key',
        'payload',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'payload' => 'array',
    ];

    public function getTable(): string
    {
        return (string) config('architect.state.table', 'architect_user_states');
    }

    public function getConnectionName(): ?string
    {
        $connection = config('architect.state.connection');

        return $connection !== null && $connection !== '' ? $connection : config('database.default');
    }
}
