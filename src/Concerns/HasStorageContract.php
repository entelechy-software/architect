<?php

declare(strict_types=1);

namespace Entelechy\Architect\Concerns;

use Entelechy\Architect\Persistence\Models\ArchitectStorageLedger;
use Illuminate\Database\Eloquent\Model;

/**
 * Assigns a named storage contract (see config('architect.storage_contracts'))
 * to an Eloquent model and keeps `architect_storage_ledger` in sync with the
 * model's own SoftDeletes lifecycle.
 *
 * Models using this trait MUST also use \Illuminate\Database\Eloquent\SoftDeletes
 * — the "Archived" stage of a storage contract IS SoftDeletes' `deleted_at`,
 * unchanged; this trait only starts tracking a record once it has been
 * soft-deleted, and stops (removes the ledger row) if it is restored.
 *
 * Contract assignment is a fluent static call inside the model's own
 * booted() hook, not a property, not a central registry:
 *
 *   class Invoice extends Model
 *   {
 *       use SoftDeletes, HasStorageContract;
 *
 *       protected static function booted(): void
 *       {
 *           static::storageContract('finance');
 *       }
 *   }
 *
 * A model that never calls storageContract() falls back to whichever
 * contract is flagged `default_contract` in config.
 */
trait HasStorageContract
{
    private static ?string $storageContractKey = null;

    private static ?string $fileRetentionContractKey = null;

    public static function storageContract(string $key): void
    {
        static::$storageContractKey = $key;
    }

    public static function getStorageContractKey(): string
    {
        return static::$storageContractKey ?? (string) config('architect.storage_contracts.default_contract');
    }

    public static function fileRetentionContract(string $key): void
    {
        static::$fileRetentionContractKey = $key;
    }

    public static function getFileRetentionContractKey(): string
    {
        return static::$fileRetentionContractKey ?? (string) config('architect.file_retention.default_contract');
    }

    protected static function bootHasStorageContract(): void
    {
        // The `enabled` check happens inside each listener rather than as a
        // guard around registering them: Eloquent only boots a model class
        // once per request, so a guard here would freeze whatever the flag
        // was at first-boot time and ignore later config changes (e.g. a
        // config cache reload) for the rest of the process.
        // Typed as the plain Model here — not Model&SoftDeletes — because
        // traits (unlike interfaces) have no runtime type identity: PHP
        // cannot check "instanceof SoftDeletes" for an intersection type, so
        // that declaration would TypeError on every real model instance.
        // isForceDeleting()/getDeletedAtColumn() are guarded via
        // method_exists() instead; models using this trait are documented
        // (see class docblock) to always also use SoftDeletes, so both exist
        // in practice — see phpstan.neon.dist for the matching suppression.
        static::deleted(function (Model $model): void {
            if (! (bool) config('architect.storage_contracts.enabled', false)) {
                return;
            }

            if (method_exists($model, 'isForceDeleting') && $model->isForceDeleting()) {
                // An explicit forceDelete() is the developer opting out of
                // the contract lifecycle entirely, not a stage transition —
                // nothing to ledger.
                return;
            }

            $deletedAtColumn = method_exists($model, 'getDeletedAtColumn')
                ? $model->getDeletedAtColumn()
                : 'deleted_at';

            ArchitectStorageLedger::query()->updateOrCreate(
                ['model_type' => $model::class, 'model_id' => $model->getKey()],
                [
                    'contract_key' => static::getStorageContractKey(),
                    'stage' => ArchitectStorageLedger::STAGE_ARCHIVED,
                    'archived_at' => $model->{$deletedAtColumn} ?? now(),
                ]
            );
        });

        // A record restored before reaching Cold Storage is live again — the
        // ledger entry no longer reflects reality, so drop it rather than
        // let it keep aging toward Retired/Cold Storage in the background.
        static::restored(function (Model $model): void {
            if (! (bool) config('architect.storage_contracts.enabled', false)) {
                return;
            }

            ArchitectStorageLedger::query()
                ->where('model_type', $model::class)
                ->where('model_id', $model->getKey())
                ->delete();
        });
    }
}
