<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Concerns;

use Entelechy\Architect\Persistence\Models\ArchitectStorageLedger;
use Entelechy\Architect\Tests\Fixtures\DefaultContractTestModel;
use Entelechy\Architect\Tests\Fixtures\StorageContractTestModel;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class HasStorageContractTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('architect.storage_contracts.enabled', true);
        config()->set('architect.storage_contracts.default_contract', 'standard');

        Schema::create('storage_contract_test_models', function (Blueprint $table): void {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('default_contract_test_models', function (Blueprint $table): void {
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
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('storage_contract_test_models');
        Schema::dropIfExists('default_contract_test_models');
        Schema::dropIfExists('architect_storage_ledger');

        parent::tearDown();
    }

    public function test_soft_deleting_a_model_creates_a_ledger_row_at_archived_stage(): void
    {
        $model = StorageContractTestModel::query()->create();

        $model->delete();

        $ledger = ArchitectStorageLedger::query()->first();

        $this->assertNotNull($ledger);
        $this->assertSame(StorageContractTestModel::class, $ledger->model_type);
        $this->assertSame($model->getKey(), $ledger->model_id);
        $this->assertSame('finance', $ledger->contract_key);
        $this->assertSame(ArchitectStorageLedger::STAGE_ARCHIVED, $ledger->stage);
        $this->assertNotNull($ledger->archived_at);
    }

    public function test_model_without_explicit_contract_falls_back_to_default(): void
    {
        $model = DefaultContractTestModel::query()->create();

        $model->delete();

        $ledger = ArchitectStorageLedger::query()->first();

        $this->assertNotNull($ledger);
        $this->assertSame('standard', $ledger->contract_key);
    }

    public function test_restoring_a_model_removes_its_ledger_row(): void
    {
        $model = StorageContractTestModel::query()->create();
        $model->delete();

        $this->assertSame(1, ArchitectStorageLedger::query()->count());

        $model->restore();

        $this->assertSame(0, ArchitectStorageLedger::query()->count());
    }

    public function test_force_deleting_does_not_create_a_ledger_row(): void
    {
        $model = StorageContractTestModel::query()->create();

        $model->forceDelete();

        $this->assertSame(0, ArchitectStorageLedger::query()->count());
    }

    public function test_disabled_storage_contracts_config_skips_ledger_creation(): void
    {
        config()->set('architect.storage_contracts.enabled', false);

        $model = StorageContractTestModel::query()->create();
        $model->delete();

        $this->assertSame(0, ArchitectStorageLedger::query()->count());
    }
}
