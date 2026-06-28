<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table\Import\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One row per CSV line ingested by an ImportBatch.
 *
 * Stores the sanitised raw payload alongside the resulting record ID so
 * reversals can target the exact records created by this import without
 * re-parsing the original file.
 *
 * @property int $id
 * @property int $import_batch_id
 * @property int $row_number
 * @property array<string, mixed> $raw_data
 * @property int|null $tenant_record_id
 * @property string $data_model_class
 * @property string $status
 * @property array<string, mixed>|null $errors
 * @property Carbon $created_at
 */
class ImportBatchItem extends Model
{
    /** @var bool Disable auto-update timestamp — only created_at exists. */
    public $timestamps = false;

    /** @var list<string> */
    protected $fillable = [
        'import_batch_id',
        'row_number',
        'raw_data',
        'tenant_record_id',
        'data_model_class',
        'status',
        'errors',
        'created_at',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'row_number' => 'integer',
        'raw_data' => 'array',
        'errors' => 'array',
        'tenant_record_id' => 'integer',
        'created_at' => 'datetime',
    ];

    public function getTable(): string
    {
        return config('architect.import.items_table', 'architect_import_batch_items');
    }

    public function getConnectionName(): ?string
    {
        return config('architect.import.connection') ?: config('database.default');
    }

    /**
     * @return BelongsTo<ImportBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }
}
