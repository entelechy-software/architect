<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Persistence;

use Entelechy\Architect\Persistence\DatabaseStateStore;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseStateStoreTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('architect.state.mode', 'database');

        Schema::create('architect_user_states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('tenant_identifier')->default('')->index();
            $table->string('scope');
            $table->string('state_key');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tenant_identifier', 'scope', 'state_key'], 'architect_user_states_uniq_state');
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('architect_user_states');

        parent::tearDown();
    }

    public function test_put_then_get_round_trips_payload(): void
    {
        $store = new DatabaseStateStore;

        $store->put(1, '', 'table', 'abc:persisted_filters', ['value' => ['status' => 'active']]);

        $this->assertSame(
            ['value' => ['status' => 'active']],
            $store->get(1, '', 'table', 'abc:persisted_filters')
        );
    }

    public function test_get_returns_null_when_nothing_stored(): void
    {
        $store = new DatabaseStateStore;

        $this->assertNull($store->get(1, '', 'table', 'missing'));
    }

    public function test_put_overwrites_existing_payload_for_same_identity(): void
    {
        $store = new DatabaseStateStore;

        $store->put(1, '', 'table', 'abc:hidden_columns', ['value' => ['a']]);
        $store->put(1, '', 'table', 'abc:hidden_columns', ['value' => ['a', 'b']]);

        $this->assertSame(['value' => ['a', 'b']], $store->get(1, '', 'table', 'abc:hidden_columns'));
        $this->assertSame(1, DB::table('architect_user_states')->count());
    }

    public function test_forget_removes_payload(): void
    {
        $store = new DatabaseStateStore;

        $store->put(1, '', 'table', 'abc:bookmarked_filters', ['value' => []]);
        $store->forget(1, '', 'table', 'abc:bookmarked_filters');

        $this->assertNull($store->get(1, '', 'table', 'abc:bookmarked_filters'));
    }

    public function test_scoping_isolates_by_user_tenant_scope_and_key(): void
    {
        $store = new DatabaseStateStore;

        $store->put(1, 'tenant-a', 'table', 'k', ['value' => 'one']);
        $store->put(2, 'tenant-a', 'table', 'k', ['value' => 'two']);
        $store->put(1, 'tenant-b', 'table', 'k', ['value' => 'three']);
        $store->put(1, 'tenant-a', 'other-scope', 'k', ['value' => 'four']);

        $this->assertSame(['value' => 'one'], $store->get(1, 'tenant-a', 'table', 'k'));
        $this->assertSame(['value' => 'two'], $store->get(2, 'tenant-a', 'table', 'k'));
        $this->assertSame(['value' => 'three'], $store->get(1, 'tenant-b', 'table', 'k'));
        $this->assertSame(['value' => 'four'], $store->get(1, 'tenant-a', 'other-scope', 'k'));
    }
}
