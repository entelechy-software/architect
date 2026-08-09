<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Tenancy;

use Entelechy\Architect\Contracts\TenantResolver;
use Entelechy\Architect\Table\AbstractEloquentDataModel;
use Entelechy\Architect\Table\QueryContext;
use Entelechy\Architect\Tenancy\TenantContext;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 4 (see ARCHITECT_IMPROVEMENT_PLAN.md): database-per-tenant
 * isolation — TenantContext::$connection switches AbstractEloquentDataModel
 * to a different database connection per tenant. Two independent in-memory
 * SQLite connections stand in for two tenants' real databases; this is the
 * only way to actually prove isolation, rather than asserting on generated
 * SQL.
 */
class TenantConnectionIsolationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach (['tenant_a', 'tenant_b'] as $connection) {
            Config::set("database.connections.{$connection}", [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ]);

            Schema::connection($connection)->create('per_tenant_widgets', function (Blueprint $table): void {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }
    }

    public function test_a_query_against_one_tenant_connection_never_returns_the_other_tenants_rows(): void
    {
        PerTenantWidgetModel::on('tenant_a')->create(['name' => 'Tenant A widget']);
        PerTenantWidgetModel::on('tenant_b')->create(['name' => 'Tenant B widget']);

        $dataModel = new PerTenantWidgetDataModel;

        $this->app->instance(TenantResolver::class, new FakeTenantResolver(new TenantContext(connection: 'tenant_a')));
        $tenantANames = $dataModel->forList(new QueryContext)->pluck('name')->all();

        $this->app->instance(TenantResolver::class, new FakeTenantResolver(new TenantContext(connection: 'tenant_b')));
        $tenantBNames = $dataModel->forList(new QueryContext)->pluck('name')->all();

        $this->assertSame(['Tenant A widget'], $tenantANames);
        $this->assertSame(['Tenant B widget'], $tenantBNames);
    }
}

final class PerTenantWidgetModel extends Model
{
    protected $table = 'per_tenant_widgets';

    protected $guarded = [];
}

final class PerTenantWidgetDataModel extends AbstractEloquentDataModel
{
    public function modelClass(): string
    {
        return PerTenantWidgetModel::class;
    }
}
