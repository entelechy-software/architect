<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Import\Models;

use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Throwable;

/**
 * Audit record for a single Architect CSV import attempt.
 *
 * One row per `Import` button click. Provides audit history and
 * enables reversals within the configured window.
 *
 * Connection and table name are configurable via:
 *   - config('architect.import.connection')  — default: config('database.default')
 *   - config('architect.import.table')       — default: 'architect_import_batches'
 *
 * Multi-tenant host apps should set architect.import.tenant_identifier_column
 * and ensure a scoped query resolves to the current tenant's batches only.
 * The recommended pattern is to override getConnectionName() and
 * newQuery() in an app-level subclass. See docs for examples.
 *
 * @property int $id
 * @property string $tenant_identifier
 * @property int $user_id
 * @property string $definition_class
 * @property string $filename
 * @property int $total_rows
 * @property int $imported_rows
 * @property int $failed_rows
 * @property string $status
 * @property Carbon|null $reversed_at
 * @property int|null $reversed_by
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ImportBatch extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'tenant_identifier',
        'user_id',
        'definition_class',
        'filename',
        'total_rows',
        'imported_rows',
        'failed_rows',
        'status',
        'reversed_at',
        'reversed_by',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'total_rows' => 'integer',
        'imported_rows' => 'integer',
        'failed_rows' => 'integer',
        'reversed_at' => 'datetime',
        'reversed_by' => 'integer',
    ];

    public function getTable(): string
    {
        return config('architect.import.table', 'architect_import_batches');
    }

    public function getConnectionName(): ?string
    {
        return config('architect.import.connection') ?: config('database.default');
    }

    /**
     * @return HasMany<ImportBatchItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ImportBatchItem::class, 'import_batch_id');
    }

    /**
     * Reverse this batch by archiving or deleting each imported record.
     *
     * @return int Number of items successfully reversed.
     */
    public function reverse(int $byUserId): int
    {
        if ($this->status === 'reversed') {
            return 0;
        }

        $reversed = 0;

        foreach ($this->items()->where('status', 'imported')->get() as $item) {
            try {
                /** @var ArchitectDataModel $dataModel */
                $dataModel = app($item->data_model_class);

                // ArchitectDataModel mandates archive() — every conforming
                // data model has it, so reversal always archives rather than
                // hard-deletes (delete() is reserved for explicit ->deletable()
                // actions, never invoked implicitly on import reversal).
                $dataModel->archive((int) $item->tenant_record_id, 'Reversed by import batch #'.$this->id);

                $item->update(['status' => 'reversed']);
                $reversed++;
            } catch (Throwable $e) {
                $item->update([
                    'errors' => array_merge((array) ($item->errors ?? []), ['reversal' => $e->getMessage()]),
                ]);
            }
        }

        $this->update([
            'status' => 'reversed',
            'reversed_at' => now(),
            'reversed_by' => $byUserId,
        ]);

        return $reversed;
    }

    /**
     * Count batches created by a specific user for a definition within the rolling window.
     */
    public static function countForUserWithin(
        int $userId,
        string $definitionClass,
        Carbon $since,
    ): int {
        return static::query()
            ->where('user_id', $userId)
            ->where('definition_class', $definitionClass)
            ->where('created_at', '>=', $since)
            ->count();
    }

    /**
     * Count batches across all users for a definition within the rolling window.
     *
     * Multi-tenant host apps should scope this query to the current tenant by
     * overriding this method in a subclass and adding a tenant_identifier filter,
     * or by applying a global scope to the model.
     */
    public static function countForTenantWithin(
        string $definitionClass,
        Carbon $since,
        string $tenantIdentifier = '',
    ): int {
        $query = static::query()
            ->where('definition_class', $definitionClass)
            ->where('created_at', '>=', $since);

        if ($tenantIdentifier !== '') {
            $query->where('tenant_identifier', $tenantIdentifier);
        }

        return $query->count();
    }
}
