<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Tenancy;

use Entelechy\Architect\Contracts\TenantResolver;
use Entelechy\Architect\Table\AbstractEloquentDataModel;
use Entelechy\Architect\Table\QueryContext;
use Entelechy\Architect\Tenancy\Concerns\ScopedToTenant;
use Entelechy\Architect\Tenancy\Contracts\HasTenantScope;
use Entelechy\Architect\Tenancy\TenantContext;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 (see ARCHITECT_IMPROVEMENT_PLAN.md): row-level tenant scoping —
 * a single shared database/connection, isolated via a HasTenantScope
 * column filter added automatically by AbstractEloquentDataModel::baseQuery().
 */
class TenantScopingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tenant_scoped_widgets', function (Blueprint $table): void {
            $table->id();
            $table->string('tenant_id')->nullable();
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('tenant_scoped_widgets');

        parent::tearDown();
    }

    public function test_base_query_scopes_to_the_current_tenant_when_model_implements_has_tenant_scope(): void
    {
        TenantScopedWidgetModel::query()->create(['tenant_id' => 'tenant-a', 'name' => 'A widget']);
        TenantScopedWidgetModel::query()->create(['tenant_id' => 'tenant-b', 'name' => 'B widget']);

        $this->app->instance(TenantResolver::class, new FakeTenantResolver(new TenantContext(identifier: 'tenant-a')));

        $names = (new TenantScopedWidgetDataModel)->forList(new QueryContext)->pluck('name')->all();

        $this->assertSame(['A widget'], $names);
    }

    public function test_base_query_is_unscoped_when_tenant_identifier_is_empty(): void
    {
        TenantScopedWidgetModel::query()->create(['tenant_id' => 'tenant-a', 'name' => 'A widget']);
        TenantScopedWidgetModel::query()->create(['tenant_id' => 'tenant-b', 'name' => 'B widget']);

        // Default binding is NullTenantResolver — identifier is always ''.
        $names = (new TenantScopedWidgetDataModel)->forList(new QueryContext)->pluck('name')->all();

        $this->assertCount(2, $names);
    }

    public function test_base_query_does_not_scope_models_that_do_not_implement_has_tenant_scope(): void
    {
        Schema::create('unscoped_widgets', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        UnscopedWidgetModel::query()->create(['name' => 'Any widget']);

        $this->app->instance(TenantResolver::class, new FakeTenantResolver(new TenantContext(identifier: 'tenant-a')));

        $names = (new UnscopedWidgetDataModel)->forList(new QueryContext)->pluck('name')->all();

        $this->assertSame(['Any widget'], $names);

        Schema::dropIfExists('unscoped_widgets');
    }
}

final class TenantScopedWidgetModel extends Model implements HasTenantScope
{
    use ScopedToTenant;

    protected $table = 'tenant_scoped_widgets';

    protected $guarded = [];
}

final class TenantScopedWidgetDataModel extends AbstractEloquentDataModel
{
    public function modelClass(): string
    {
        return TenantScopedWidgetModel::class;
    }
}

final class UnscopedWidgetModel extends Model
{
    protected $table = 'unscoped_widgets';

    protected $guarded = [];
}

final class UnscopedWidgetDataModel extends AbstractEloquentDataModel
{
    public function modelClass(): string
    {
        return UnscopedWidgetModel::class;
    }
}
