<?php

declare(strict_types=1);

namespace Entelechy\Architect\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Ledger row tracking a single Eloquent record's progress through the
 * storage contract lifecycle: Archived -> Retired -> Cold Storage -> Purged.
 *
 * One row per (model_type, model_id) pair. Created the moment a
 * HasStorageContract model is soft-deleted (see
 * \Entelechy\Architect\Concerns\HasStorageContract) and advanced in place by
 * `architect:storage:sweep` as each stage's duration elapses. Removed
 * outright if the record is restored before reaching Cold Storage.
 *
 * Table name and connection are resolved from
 * config('architect.storage_contracts.ledger') at call time, since both are
 * project-specific decisions made once via `architect:storage:init`.
 *
 * @property int $id
 * @property string $model_type
 * @property int $model_id
 * @property string $contract_key
 * @property string $stage
 * @property Carbon|null $archived_at
 * @property Carbon|null $retired_at
 * @property Carbon|null $cold_storage_at
 * @property Carbon|null $purged_at
 * @property string|null $cold_disk
 * @property string|null $cold_path
 */
class ArchitectStorageLedger extends Model
{
    public const STAGE_ARCHIVED = 'archived';

    public const STAGE_RETIRED = 'retired';

    public const STAGE_COLD_STORAGE = 'cold_storage';

    public const STAGE_PURGED = 'purged';

    /** @var list<string> */
    protected $fillable = [
        'model_type',
        'model_id',
        'contract_key',
        'stage',
        'archived_at',
        'retired_at',
        'cold_storage_at',
        'purged_at',
        'cold_disk',
        'cold_path',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'archived_at' => 'datetime',
        'retired_at' => 'datetime',
        'cold_storage_at' => 'datetime',
        'purged_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return (string) config('architect.storage_contracts.ledger.table', 'architect_storage_ledger');
    }

    public function getConnectionName(): ?string
    {
        $connection = config('architect.storage_contracts.ledger.connection');

        return $connection !== null && $connection !== '' ? $connection : config('database.default');
    }
}
