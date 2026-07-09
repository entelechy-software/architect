<?php

declare(strict_types=1);

namespace Entelechy\Architect\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Ledger row tracking a single standalone/orphan file's progress through the
 * file-retention lifecycle: Active -> Soft-deleted -> Permanently removed.
 *
 * Populated explicitly by the developer via `Architect::trackUpload(...)` at
 * upload time — never auto-discovered. `last_accessed_at` is updated whenever
 * the file is served through the package's file-serving path; `architect:
 * storage:sweep` advances stages once a contract's `inactive`/`purge`
 * duration elapses since that timestamp.
 *
 * Table name and connection are resolved from
 * config('architect.file_retention.ledger') at call time, since both are
 * project-specific decisions made once via `architect:storage:init`.
 *
 * @property int $id
 * @property string $path
 * @property string $disk
 * @property string $contract_key
 * @property string $stage
 * @property Carbon|null $last_accessed_at
 * @property Carbon|null $soft_deleted_at
 * @property Carbon|null $purged_at
 */
class ArchitectUploads extends Model
{
    public const STAGE_ACTIVE = 'active';

    public const STAGE_SOFT_DELETED = 'soft_deleted';

    public const STAGE_PURGED = 'purged';

    /** @var list<string> */
    protected $fillable = [
        'path',
        'disk',
        'contract_key',
        'stage',
        'last_accessed_at',
        'soft_deleted_at',
        'purged_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'last_accessed_at' => 'datetime',
        'soft_deleted_at' => 'datetime',
        'purged_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return (string) config('architect.file_retention.ledger.table', 'architect_uploads');
    }

    public function getConnectionName(): ?string
    {
        $connection = config('architect.file_retention.ledger.connection');

        return $connection !== null && $connection !== '' ? $connection : config('database.default');
    }
}
