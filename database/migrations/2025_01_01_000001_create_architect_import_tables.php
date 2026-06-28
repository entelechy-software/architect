<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the architect_import_batches and architect_import_batch_items tables.
 *
 * These tables track CSV import audit history for all Architect-managed tables
 * that have ->importable() declared. They enable:
 *   - Post-import reversals (within the configured reversalWindowMinutes)
 *   - Per-user and per-tenant rate limiting
 *   - Audit history in the wizard history panel
 *
 * Host apps that wish to use an existing table (e.g. migrating from a
 * legacy import_batches table) can set the table name in config/architect.php:
 *   'import' => ['table' => 'import_batches', 'items_table' => 'import_batch_items']
 * and skip (or roll back) this migration.
 */
return new class extends Migration
{
    public function up(): void
    {
        $connection = config('architect.import.connection') ?: config('database.default');
        $table = config('architect.import.table', 'architect_import_batches');
        $itemsTable = config('architect.import.items_table', 'architect_import_batch_items');

        Schema::connection($connection)->create($table, static function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->string('tenant_identifier')->default('')->index();
            $blueprint->unsignedBigInteger('user_id')->index();
            $blueprint->string('definition_class');
            $blueprint->string('filename');
            $blueprint->unsignedInteger('total_rows')->default(0);
            $blueprint->unsignedInteger('imported_rows')->default(0);
            $blueprint->unsignedInteger('failed_rows')->default(0);
            $blueprint->string('status')->default('processing');
            $blueprint->timestamp('reversed_at')->nullable();
            $blueprint->unsignedBigInteger('reversed_by')->nullable();
            $blueprint->timestamps();

            $blueprint->index(['definition_class', 'created_at']);
            $blueprint->index(['user_id', 'definition_class', 'created_at']);
        });

        Schema::connection($connection)->create($itemsTable, static function (Blueprint $blueprint): void {
            $blueprint->id();
            $blueprint->unsignedBigInteger('import_batch_id')->index();
            $blueprint->unsignedInteger('row_number');
            $blueprint->json('raw_data');
            $blueprint->unsignedBigInteger('tenant_record_id')->nullable()->index();
            $blueprint->string('data_model_class');
            $blueprint->string('status')->default('imported');
            $blueprint->json('errors')->nullable();
            $blueprint->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        $connection = config('architect.import.connection') ?: config('database.default');

        Schema::connection($connection)->dropIfExists(
            config('architect.import.items_table', 'architect_import_batch_items')
        );

        Schema::connection($connection)->dropIfExists(
            config('architect.import.table', 'architect_import_batches')
        );
    }
};
