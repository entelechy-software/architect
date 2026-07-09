<?php

declare(strict_types=1);

namespace Entelechy\Architect\Console\Commands;

use Entelechy\Architect\Persistence\Models\ArchitectStorageLedger;
use Entelechy\Architect\Persistence\Models\ArchitectUploads;
use Entelechy\Architect\Support\DurationParser;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Daily sweep advancing every record/file through its storage/file-retention
 * contract lifecycle by one stage, once the relevant duration has elapsed.
 * See STORAGE_CONTRACTS_PLAN.md.
 *
 * Five independent phases, each gated by its own config `enabled` flag:
 *   1. DB records: Archived -> Retired            (bulk, reversible until here)
 *   2. DB records: Retired -> Cold Storage         (per-record: write, verify, force-delete)
 *   3. DB records: Cold Storage -> Purged          (per-record: delete cold file)
 *   4. Files:      Active -> Soft-deleted          (bulk, architect_uploads ledger only)
 *   5. Files:      Soft-deleted -> Permanently removed (per-record: delete from disk)
 *
 * KNOWN v1 LIMITATION: phases 4/5 only sweep the standalone architect_uploads
 * ledger (populated via Architect::trackUpload()). Model-attached files
 * (columns discovered/declared via HasStorageContract::fileRetentionContract())
 * are not yet swept — that requires last_accessed_at tracking through a
 * package file-serving path, which is not implemented in this pass. See
 * STORAGE_CONTRACTS_PLAN.md "Access tracking".
 */
class ArchitectStorageSweepCommand extends Command
{
    protected $signature = 'architect:storage:sweep';

    protected $description = 'Advance records/files through their storage and file-retention contract lifecycle.';

    public function handle(): int
    {
        if ((bool) config('architect.storage_contracts.enabled', false)) {
            $this->sweepArchivedToRetired();
            $this->sweepRetiredToColdStorage();
            $this->sweepColdStorageToPurged();
        } else {
            $this->line('Storage Contracts disabled — skipping DB record phases.');
        }

        if ((bool) config('architect.file_retention.enabled', false)) {
            $this->sweepActiveToSoftDeleted();
            $this->sweepSoftDeletedToPurged();
        } else {
            $this->line('File Retention disabled — skipping file phases.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function storageContracts(): array
    {
        return (array) config('architect.storage_contracts.contracts', []);
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function fileRetentionContracts(): array
    {
        return (array) config('architect.file_retention.contracts', []);
    }

    private function sweepArchivedToRetired(): void
    {
        $moved = 0;

        foreach ($this->storageContracts() as $contractKey => $durations) {
            $cutoff = DurationParser::cutoff($durations['archived'] ?? null);
            if ($cutoff === null) {
                continue;
            }

            $moved += ArchitectStorageLedger::query()
                ->where('contract_key', $contractKey)
                ->where('stage', ArchitectStorageLedger::STAGE_ARCHIVED)
                ->where('archived_at', '<=', $cutoff)
                ->update([
                    'stage' => ArchitectStorageLedger::STAGE_RETIRED,
                    'retired_at' => now(),
                ]);
        }

        $this->info("Archived -> Retired: {$moved} record(s).");
    }

    private function sweepRetiredToColdStorage(): void
    {
        $coldDisk = config('architect.storage_contracts.cold_disk');
        $moved = 0;
        $failed = 0;

        foreach ($this->storageContracts() as $contractKey => $durations) {
            $cutoff = DurationParser::cutoff($durations['retired'] ?? null);
            if ($cutoff === null) {
                continue;
            }

            ArchitectStorageLedger::query()
                ->where('contract_key', $contractKey)
                ->where('stage', ArchitectStorageLedger::STAGE_RETIRED)
                ->where('retired_at', '<=', $cutoff)
                ->chunkById(100, function ($ledgerRows) use ($coldDisk, &$moved, &$failed): void {
                    foreach ($ledgerRows as $ledger) {
                        try {
                            if ($this->moveToColdStorage($ledger, (string) $coldDisk)) {
                                $moved++;
                            }
                        } catch (Throwable $e) {
                            $failed++;
                            $this->warn("Failed to move ledger #{$ledger->id} ({$ledger->model_type}#{$ledger->model_id}) to cold storage: ".$e->getMessage());
                        }
                    }
                });
        }

        $this->info("Retired -> Cold Storage: {$moved} record(s), {$failed} failure(s).");
    }

    private function moveToColdStorage(ArchitectStorageLedger $ledger, string $coldDisk): bool
    {
        $modelType = $ledger->model_type;

        if (! class_exists($modelType)) {
            $this->warn("Skipping ledger #{$ledger->id}: model class {$modelType} no longer exists.");

            return false;
        }

        /** @var Model|null $model */
        $model = $modelType::withTrashed()->find($ledger->model_id);

        if ($model === null) {
            $this->warn("Skipping ledger #{$ledger->id}: {$modelType}#{$ledger->model_id} no longer exists.");

            return false;
        }

        $relations = method_exists($model, 'coldStorageRelations') ? $model->coldStorageRelations() : [];
        if ($relations !== []) {
            $model->load($relations);
        }

        $payload = [
            'model_type' => $modelType,
            'model_id' => $ledger->model_id,
            'contract_key' => $ledger->contract_key,
            'archived_at' => optional($ledger->archived_at)->toIso8601String(),
            'retired_at' => optional($ledger->retired_at)->toIso8601String(),
            'cold_stored_at' => now()->toIso8601String(),
            'attributes' => $model->getAttributes(),
            'relations' => $relations !== [] ? $model->only($relations) : [],
        ];

        $path = 'architect-storage-contracts/'.str_replace('\\', '_', $modelType).'/'.$ledger->model_id.'.json';

        // Write -> verify -> force-delete, never delete before a confirmed
        // write (STORAGE_CONTRACTS_PLAN.md safety note) — Cold Storage is
        // the point of no return for the DB row.
        Storage::disk($coldDisk)->put($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

        if (! Storage::disk($coldDisk)->exists($path)) {
            throw new \RuntimeException("Cold storage write for {$path} could not be verified.");
        }

        $model->forceDelete();

        $ledger->update([
            'stage' => ArchitectStorageLedger::STAGE_COLD_STORAGE,
            'cold_storage_at' => now(),
            'cold_disk' => $coldDisk,
            'cold_path' => $path,
        ]);

        return true;
    }

    private function sweepColdStorageToPurged(): void
    {
        $purged = 0;

        foreach ($this->storageContracts() as $contractKey => $durations) {
            $cutoff = DurationParser::cutoff($durations['cold_storage'] ?? null);
            if ($cutoff === null) {
                // Absent/null cold_storage duration = never auto-purge for
                // this contract (STORAGE_CONTRACTS_PLAN.md).
                continue;
            }

            ArchitectStorageLedger::query()
                ->where('contract_key', $contractKey)
                ->where('stage', ArchitectStorageLedger::STAGE_COLD_STORAGE)
                ->where('cold_storage_at', '<=', $cutoff)
                ->chunkById(100, function ($ledgerRows) use (&$purged): void {
                    foreach ($ledgerRows as $ledger) {
                        if ($ledger->cold_disk !== null && $ledger->cold_path !== null) {
                            Storage::disk($ledger->cold_disk)->delete($ledger->cold_path);
                        }

                        $ledger->update([
                            'stage' => ArchitectStorageLedger::STAGE_PURGED,
                            'purged_at' => now(),
                        ]);

                        $purged++;
                    }
                });
        }

        $this->info("Cold Storage -> Purged: {$purged} record(s).");
    }

    private function sweepActiveToSoftDeleted(): void
    {
        $moved = 0;

        foreach ($this->fileRetentionContracts() as $contractKey => $durations) {
            $cutoff = DurationParser::cutoff($durations['inactive'] ?? null);
            if ($cutoff === null) {
                continue;
            }

            $moved += ArchitectUploads::query()
                ->where('contract_key', $contractKey)
                ->where('stage', ArchitectUploads::STAGE_ACTIVE)
                ->where('last_accessed_at', '<=', $cutoff)
                ->update([
                    'stage' => ArchitectUploads::STAGE_SOFT_DELETED,
                    'soft_deleted_at' => now(),
                ]);
        }

        $this->info("Active -> Soft-deleted: {$moved} file(s).");
    }

    private function sweepSoftDeletedToPurged(): void
    {
        $purged = 0;

        foreach ($this->fileRetentionContracts() as $contractKey => $durations) {
            $cutoff = DurationParser::cutoff($durations['purge'] ?? null);
            if ($cutoff === null) {
                continue;
            }

            ArchitectUploads::query()
                ->where('contract_key', $contractKey)
                ->where('stage', ArchitectUploads::STAGE_SOFT_DELETED)
                ->where('soft_deleted_at', '<=', $cutoff)
                ->chunkById(100, function ($uploads) use (&$purged): void {
                    foreach ($uploads as $upload) {
                        Storage::disk($upload->disk)->delete($upload->path);

                        $upload->update([
                            'stage' => ArchitectUploads::STAGE_PURGED,
                            'purged_at' => now(),
                        ]);

                        $purged++;
                    }
                });
        }

        $this->info("Soft-deleted -> Permanently removed: {$purged} file(s).");
    }
}
