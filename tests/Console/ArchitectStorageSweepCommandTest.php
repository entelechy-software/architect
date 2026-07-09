<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Console;

use Entelechy\Architect\Persistence\Models\ArchitectStorageLedger;
use Entelechy\Architect\Persistence\Models\ArchitectUploads;
use Entelechy\Architect\Tests\Fixtures\StorageContractTestModel;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ArchitectStorageSweepCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('architect.storage_contracts.enabled', true);
        config()->set('architect.storage_contracts.cold_disk', 'cold-test-disk');
        config()->set('architect.storage_contracts.default_contract', 'finance');
        config()->set('architect.storage_contracts.contracts', [
            'finance' => ['archived' => '2 years', 'retired' => '1 year', 'cold_storage' => '1 year'],
            'forever' => ['archived' => '2 years', 'retired' => '1 year', 'cold_storage' => null],
        ]);

        config()->set('architect.file_retention.enabled', true);
        config()->set('architect.file_retention.default_contract', 'standard-files');
        config()->set('architect.file_retention.contracts', [
            'standard-files' => ['inactive' => '1 year', 'purge' => '6 months'],
        ]);

        Schema::create('storage_contract_test_models', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('architect_storage_ledger', function (Blueprint $table): void {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('contract_key');
            $table->string('stage');
            $table->timestamp('archived_at')->nullable();
            $table->timestamp('retired_at')->nullable();
            $table->timestamp('cold_storage_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->string('cold_disk')->nullable();
            $table->string('cold_path')->nullable();
            $table->timestamps();
            $table->unique(['model_type', 'model_id']);
        });

        Schema::create('architect_uploads', function (Blueprint $table): void {
            $table->id();
            $table->string('path');
            $table->string('disk');
            $table->string('contract_key');
            $table->string('stage');
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamp('soft_deleted_at')->nullable();
            $table->timestamp('purged_at')->nullable();
            $table->timestamps();
        });

        Storage::fake('cold-test-disk');
        Storage::fake('uploads-test-disk');
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('storage_contract_test_models');
        Schema::dropIfExists('architect_storage_ledger');
        Schema::dropIfExists('architect_uploads');

        parent::tearDown();
    }

    public function test_archived_records_past_their_duration_move_to_retired(): void
    {
        ArchitectStorageLedger::query()->create([
            'model_type' => StorageContractTestModel::class,
            'model_id' => 1,
            'contract_key' => 'finance',
            'stage' => ArchitectStorageLedger::STAGE_ARCHIVED,
            'archived_at' => now()->subYears(3),
        ]);

        $this->artisan('architect:storage:sweep')->assertExitCode(0);

        $ledger = ArchitectStorageLedger::query()->first();
        $this->assertSame(ArchitectStorageLedger::STAGE_RETIRED, $ledger->stage);
        $this->assertNotNull($ledger->retired_at);
    }

    public function test_archived_records_not_yet_due_are_left_alone(): void
    {
        ArchitectStorageLedger::query()->create([
            'model_type' => StorageContractTestModel::class,
            'model_id' => 1,
            'contract_key' => 'finance',
            'stage' => ArchitectStorageLedger::STAGE_ARCHIVED,
            'archived_at' => now()->subDays(10),
        ]);

        $this->artisan('architect:storage:sweep')->assertExitCode(0);

        $this->assertSame(ArchitectStorageLedger::STAGE_ARCHIVED, ArchitectStorageLedger::query()->first()->stage);
    }

    public function test_retired_records_past_duration_are_moved_to_cold_storage_and_hard_deleted(): void
    {
        $model = StorageContractTestModel::query()->create();
        $model->delete();

        ArchitectStorageLedger::query()
            ->where('model_type', StorageContractTestModel::class)
            ->where('model_id', $model->getKey())
            ->update([
                'stage' => ArchitectStorageLedger::STAGE_RETIRED,
                'retired_at' => now()->subYears(2),
            ]);

        $this->artisan('architect:storage:sweep')->assertExitCode(0);

        $ledger = ArchitectStorageLedger::query()->first();
        $this->assertSame(ArchitectStorageLedger::STAGE_COLD_STORAGE, $ledger->stage);
        $this->assertNotNull($ledger->cold_storage_at);
        $this->assertSame('cold-test-disk', $ledger->cold_disk);
        $this->assertNotNull($ledger->cold_path);

        Storage::disk('cold-test-disk')->assertExists($ledger->cold_path);
        $this->assertNull(StorageContractTestModel::withTrashed()->find($model->getKey()));
    }

    public function test_cold_storage_records_past_duration_are_purged(): void
    {
        Storage::disk('cold-test-disk')->put('archive/1.json', '{"id":1}');

        ArchitectStorageLedger::query()->create([
            'model_type' => StorageContractTestModel::class,
            'model_id' => 1,
            'contract_key' => 'finance',
            'stage' => ArchitectStorageLedger::STAGE_COLD_STORAGE,
            'cold_storage_at' => now()->subYears(2),
            'cold_disk' => 'cold-test-disk',
            'cold_path' => 'archive/1.json',
        ]);

        $this->artisan('architect:storage:sweep')->assertExitCode(0);

        $ledger = ArchitectStorageLedger::query()->first();
        $this->assertSame(ArchitectStorageLedger::STAGE_PURGED, $ledger->stage);
        $this->assertNotNull($ledger->purged_at);

        Storage::disk('cold-test-disk')->assertMissing('archive/1.json');
    }

    public function test_cold_storage_duration_null_never_auto_purges(): void
    {
        Storage::disk('cold-test-disk')->put('archive/2.json', '{"id":2}');

        ArchitectStorageLedger::query()->create([
            'model_type' => StorageContractTestModel::class,
            'model_id' => 2,
            'contract_key' => 'forever',
            'stage' => ArchitectStorageLedger::STAGE_COLD_STORAGE,
            'cold_storage_at' => now()->subYears(10),
            'cold_disk' => 'cold-test-disk',
            'cold_path' => 'archive/2.json',
        ]);

        $this->artisan('architect:storage:sweep')->assertExitCode(0);

        $ledger = ArchitectStorageLedger::query()->first();
        $this->assertSame(ArchitectStorageLedger::STAGE_COLD_STORAGE, $ledger->stage);
        Storage::disk('cold-test-disk')->assertExists('archive/2.json');
    }

    public function test_inactive_files_past_duration_are_marked_soft_deleted(): void
    {
        ArchitectUploads::query()->create([
            'path' => 'uploads/report.pdf',
            'disk' => 'uploads-test-disk',
            'contract_key' => 'standard-files',
            'stage' => ArchitectUploads::STAGE_ACTIVE,
            'last_accessed_at' => now()->subYears(2),
        ]);

        $this->artisan('architect:storage:sweep')->assertExitCode(0);

        $upload = ArchitectUploads::query()->first();
        $this->assertSame(ArchitectUploads::STAGE_SOFT_DELETED, $upload->stage);
        $this->assertNotNull($upload->soft_deleted_at);
    }

    public function test_soft_deleted_files_past_purge_duration_are_removed_from_disk(): void
    {
        Storage::disk('uploads-test-disk')->put('uploads/old.pdf', 'content');

        ArchitectUploads::query()->create([
            'path' => 'uploads/old.pdf',
            'disk' => 'uploads-test-disk',
            'contract_key' => 'standard-files',
            'stage' => ArchitectUploads::STAGE_SOFT_DELETED,
            'soft_deleted_at' => now()->subMonths(8),
        ]);

        $this->artisan('architect:storage:sweep')->assertExitCode(0);

        $upload = ArchitectUploads::query()->first();
        $this->assertSame(ArchitectUploads::STAGE_PURGED, $upload->stage);
        $this->assertNotNull($upload->purged_at);

        Storage::disk('uploads-test-disk')->assertMissing('uploads/old.pdf');
    }

    public function test_disabled_features_are_skipped_entirely(): void
    {
        config()->set('architect.storage_contracts.enabled', false);
        config()->set('architect.file_retention.enabled', false);

        ArchitectStorageLedger::query()->create([
            'model_type' => StorageContractTestModel::class,
            'model_id' => 1,
            'contract_key' => 'finance',
            'stage' => ArchitectStorageLedger::STAGE_ARCHIVED,
            'archived_at' => now()->subYears(3),
        ]);

        $this->artisan('architect:storage:sweep')->assertExitCode(0);

        $this->assertSame(ArchitectStorageLedger::STAGE_ARCHIVED, ArchitectStorageLedger::query()->first()->stage);
    }
}
