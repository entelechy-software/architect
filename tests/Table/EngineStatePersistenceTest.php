<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Table;

use Entelechy\Architect\Table\ArchitectTableDefinition;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Livewire\Engine;
use Entelechy\Architect\Table\QueryContext;
use Entelechy\Architect\Table\TableBuilder;
use Entelechy\Architect\Tests\TestCase;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

class EngineStatePersistenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists(config('architect.state.table', 'architect_user_states'));

        parent::tearDown();
    }

    public function test_state_methods_are_inert_in_local_storage_mode(): void
    {
        config()->set('architect.state.mode', 'localStorage');
        $this->actingAs(new StatePersistenceStubUser(1));

        Livewire::test(Engine::class, ['definitionClass' => StatePersistenceTableDefinition::class])
            ->call('saveTableState', 'persisted_filters', ['status' => 'active'])
            ->call('loadTableState', 'persisted_filters')
            ->assertReturned(null);
    }

    public function test_save_load_and_forget_round_trip_in_database_mode(): void
    {
        $this->createStateTable();
        config()->set('architect.state.mode', 'database');
        $this->actingAs(new StatePersistenceStubUser(42));

        Livewire::test(Engine::class, ['definitionClass' => StatePersistenceTableDefinition::class])
            ->call('saveTableState', 'persisted_filters', ['status' => 'active'])
            ->call('loadTableState', 'persisted_filters')
            ->assertReturned(['value' => ['status' => 'active']]);

        Livewire::test(Engine::class, ['definitionClass' => StatePersistenceTableDefinition::class])
            ->call('forgetTableState', 'persisted_filters')
            ->call('loadTableState', 'persisted_filters')
            ->assertReturned(null);
    }

    public function test_state_does_not_leak_between_users(): void
    {
        $this->createStateTable();
        config()->set('architect.state.mode', 'database');

        $this->actingAs(new StatePersistenceStubUser(1));
        Livewire::test(Engine::class, ['definitionClass' => StatePersistenceTableDefinition::class])
            ->call('saveTableState', 'hidden_columns', ['name']);

        $this->actingAs(new StatePersistenceStubUser(2));
        Livewire::test(Engine::class, ['definitionClass' => StatePersistenceTableDefinition::class])
            ->call('loadTableState', 'hidden_columns')
            ->assertReturned(null);
    }

    public function test_state_does_not_leak_between_table_definitions(): void
    {
        $this->createStateTable();
        config()->set('architect.state.mode', 'database');
        $this->actingAs(new StatePersistenceStubUser(1));

        Livewire::test(Engine::class, ['definitionClass' => StatePersistenceTableDefinition::class])
            ->call('saveTableState', 'hidden_columns', ['name']);

        Livewire::test(Engine::class, ['definitionClass' => OtherStatePersistenceTableDefinition::class])
            ->call('loadTableState', 'hidden_columns')
            ->assertReturned(null);
    }

    public function test_load_and_save_are_inert_without_an_authenticated_user(): void
    {
        $this->createStateTable();
        config()->set('architect.state.mode', 'database');

        Livewire::test(Engine::class, ['definitionClass' => StatePersistenceTableDefinition::class])
            ->call('saveTableState', 'persisted_filters', ['status' => 'active'])
            ->call('loadTableState', 'persisted_filters')
            ->assertReturned(null);
    }

    private function createStateTable(): void
    {
        Schema::create(config('architect.state.table', 'architect_user_states'), function (Blueprint $table) {
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
}

final class StatePersistenceStubUser implements Authenticatable
{
    public function __construct(private int $id) {}

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }
}

final class StatePersistenceTableDefinition
{
    public static function definition(): ArchitectTableDefinition
    {
        return TableBuilder::make()
            ->title('State Persistence Widgets')
            ->model(StatePersistenceStubDataModel::class)
            ->permissions(
                read: 'state.widgets.read',
                create: 'state.widgets.create',
                modify: 'state.widgets.modify',
                remove: 'state.widgets.remove',
            )
            ->build();
    }
}

final class OtherStatePersistenceTableDefinition
{
    public static function definition(): ArchitectTableDefinition
    {
        return TableBuilder::make()
            ->title('Other State Persistence Widgets')
            ->model(StatePersistenceStubDataModel::class)
            ->permissions(
                read: 'state.other-widgets.read',
                create: 'state.other-widgets.create',
                modify: 'state.other-widgets.modify',
                remove: 'state.other-widgets.remove',
            )
            ->build();
    }
}

final class StatePersistenceStubDataModel implements ArchitectDataModel
{
    public function forList(QueryContext $context): LengthAwarePaginator
    {
        $row = ['id' => 1, 'name' => 'Widget 1'];

        return new ConcreteLengthAwarePaginator([$row], 1, 25, 1);
    }

    /** @return array<string, mixed>|null */
    public function forForm(int $id): ?array
    {
        return ['id' => $id, 'name' => 'Widget '.$id];
    }

    /** @param array<string, mixed> $input */
    public function create(array $input): int
    {
        return 1;
    }

    /** @param array<string, mixed> $input */
    public function modify(int $id, array $input): void
    {
        // no-op — not exercised by these tests
    }

    public function archive(int $id, ?string $reason = null): void
    {
        // no-op — not exercised by these tests
    }

    public function restore(int $id): void
    {
        // no-op — not exercised by these tests
    }

    public function delete(int $id, ?string $reason = null): void
    {
        // no-op — not exercised by these tests
    }

    public function canActOn(Model $user, int $id): bool
    {
        return true;
    }

    public function modelClass(): string
    {
        return Model::class;
    }
}
