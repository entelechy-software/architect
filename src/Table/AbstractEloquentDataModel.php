<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table;

use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Optional convenience base class for ArchitectDataModel implementations
 * backed by a single Eloquent model.
 *
 * Implementations need only override modelClass() and, when the table has
 * search/filtering, applyFilters(). Everything else has a sane Eloquent
 * default that can still be overridden where a module needs custom
 * behaviour (e.g. a non-standard archive column).
 */
abstract class AbstractEloquentDataModel implements ArchitectDataModel
{
    /**
     * Return the Eloquent model class this data model operates on.
     *
     * @return class-string<Model>
     */
    abstract public function modelClass(): string;

    /**
     * Override to customise the base query — eager loads, tenant scoping
     * (only if the host app does not already switch connections via
     * middleware — see Phase 3.2's tenancy notes), excluding soft-deleted
     * rows by default, etc.
     *
     * @return Builder<Model>
     */
    protected function baseQuery(): Builder
    {
        return $this->modelClass()::query();
    }

    /**
     * Override to translate QueryContext::$search and ::$filters into query
     * constraints. The default is a no-op — the engine still applies
     * column-level filters via ModuleTableFilterPipeline regardless of what
     * this hook does, so only override when a module needs custom search
     * behaviour beyond per-column filters.
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function applyFilters(Builder $query, QueryContext $context): Builder
    {
        return $query;
    }

    public function forList(QueryContext $context): LengthAwarePaginator
    {
        $query = $this->applyFilters($this->baseQuery(), $context);

        if ($context->sortColumn !== null) {
            $query->orderBy($context->sortColumn, $context->sortDirection);
        }

        $paginator = $query->paginate($context->perPage, ['*'], 'page', $context->page);

        /** @var LengthAwarePaginator<int, array<string, mixed>> */
        return $paginator->through(fn (Model $model): array => $model->toArray());
    }

    public function forForm(int $id): ?array
    {
        $record = $this->baseQuery()->find($id);

        return $record?->toArray();
    }

    public function create(array $input): int
    {
        return $this->baseQuery()->create($input)->getKey();
    }

    public function modify(int $id, array $input): void
    {
        $this->baseQuery()->findOrFail($id)->update($input);
    }

    public function archive(int $id, ?string $reason = null): void
    {
        $this->baseQuery()->findOrFail($id)->delete();
    }

    /**
     * Assumes the model uses Illuminate\Database\Eloquent\SoftDeletes — true
     * for any model an ->archivable() definition's archive()/restore() pair
     * is meaningful for. PHPStan can't verify the trait statically through
     * a dynamic class-string, hence the ignores below.
     */
    public function restore(int $id): void
    {
        // @phpstan-ignore staticMethod.notFound
        $this->modelClass()::withTrashed()->findOrFail($id)->restore();
    }

    public function delete(int $id, ?string $reason = null): void
    {
        $modelClass = $this->modelClass();

        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($modelClass))) {
            // @phpstan-ignore staticMethod.notFound
            $modelClass::withTrashed()->findOrFail($id)->forceDelete();
        } else {
            $modelClass::findOrFail($id)->delete();
        }
    }

    /**
     * Default: allow any authenticated user (no extra data-scope gate).
     * Override for record-level scoping, e.g. tenant ownership checks.
     */
    public function canActOn(Model $user, int $id): bool
    {
        return true;
    }
}
