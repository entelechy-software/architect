<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Fixtures\Discovery;

use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\QueryContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator as ConcreteLengthAwarePaginator;

/**
 * Minimal ArchitectDataModel fixture for ArchitectStorageDiscoverCommandTest —
 * only modelClass() is exercised by discovery, the rest are unreachable stubs.
 */
class SampleDiscoveryDataModel implements ArchitectDataModel
{
    public function forList(QueryContext $context): LengthAwarePaginator
    {
        return new ConcreteLengthAwarePaginator([], 0, 25);
    }

    public function forForm(int $id): ?array
    {
        return null;
    }

    public function create(array $input): int
    {
        return 0;
    }

    public function modify(int $id, array $input): void {}

    public function archive(int $id, ?string $reason = null): void {}

    public function restore(int $id): void {}

    public function delete(int $id, ?string $reason = null): void {}

    public function canActOn(Model $user, int $id): bool
    {
        return true;
    }

    public function modelClass(): string
    {
        return SampleDiscoveryModel::class;
    }
}
