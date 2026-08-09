<?php

declare(strict_types=1);

namespace Entelechy\Architect\Table;

use Entelechy\Architect\Contracts\TenantResolver;
use Entelechy\Architect\Table\Contracts\ArchitectDataModel;
use Entelechy\Architect\Table\Contracts\SupportsAutoRefreshFingerprint;
use Entelechy\Architect\Tenancy\Contracts\HasTenantScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Optional convenience base class for ArchitectDataModel implementations
 * backed by a single Eloquent model.
 *
 * Implementations need only override modelClass() and, when the table has
 * search/filtering, applyFilters(). Everything else has a sane Eloquent
 * default that can still be overridden where a module needs custom
 * behaviour (e.g. a non-standard archive column).
 */
abstract class AbstractEloquentDataModel implements ArchitectDataModel, SupportsAutoRefreshFingerprint
{
    /**
     * Return the Eloquent model class this data model operates on.
     *
     * @return class-string<Model>
     */
    abstract public function modelClass(): string;

    /**
     * Override to customise the base query further — eager loads, custom
     * excludes, etc. Always call parent::baseQuery() first (or replicate
     * its behaviour) to keep the automatic tenant scoping below.
     *
     * Tenant handling (see ARCHITECT_IMPROVEMENT_PLAN.md Phase 4): resolves
     * the current TenantContext once per query. A non-null
     * TenantContext::$connection switches the query to that database
     * connection (database-per-tenant). When the bound model additionally
     * implements Tenancy\Contracts\HasTenantScope, the query is also
     * filtered by tenantScopeColumn() = TenantContext::$identifier
     * (row-level scoping in a shared database). Both apply independently —
     * a host app can combine them per-model.
     *
     * @return Builder<Model>
     */
    protected function baseQuery(): Builder
    {
        $modelClass = $this->modelClass();
        $context = app(TenantResolver::class)->resolve();

        $query = $context->connection !== null
            ? $modelClass::on($context->connection)
            : $modelClass::query();

        if ($context->identifier !== '' && is_subclass_of($modelClass, HasTenantScope::class)) {
            $query->where($modelClass::tenantScopeColumn(), $context->identifier);
        }

        return $query;
    }

    /**
     * Override to add custom query constraints beyond the sane default —
     * e.g. joins, computed columns, or search logic that can't be expressed
     * as a plain LIKE across columns. Call parent::applyFilters() first to
     * keep the default named-filter and free-text search behaviour.
     *
     * The default applies every active named filter via
     * ModuleTableFilterPipeline, then — when $context->search is
     * non-empty — ORs a LIKE '%term%' across every column marked
     * ->searchable() on the table definition ($context->searchableColumns).
     *
     * @param  Builder<Model>  $query
     * @return Builder<Model>
     */
    protected function applyFilters(Builder $query, QueryContext $context): Builder
    {
        ModuleTableFilterPipeline::apply($query, $context);

        if ($context->search !== '' && $context->searchableColumns !== []) {
            $term = $context->search;
            $query->where(function (Builder $q) use ($term, $context): void {
                foreach ($context->searchableColumns as $column) {
                    $q->orWhere($column, 'like', '%'.$term.'%');
                }
            });
        }

        return $query;
    }

    public function refreshFingerprint(QueryContext $context, string $fingerprintOn): string|int|null
    {
        $query = $this->applyFilters($this->baseQuery(), $context);

        // Clone and strip ordering to keep aggregate query minimal.
        $aggregate = (clone $query)->getQuery()->cloneWithout(['orders', 'limit', 'offset']);

        $columns = $aggregate->columns;
        if ($columns !== null && $columns !== []) {
            $hasColumn = in_array($fingerprintOn, $columns, true)
                || in_array($fingerprintOn.' as '.$fingerprintOn, $columns, true);

            if (! $hasColumn) {
                return null;
            }
        }

        $max = (string) ((clone $aggregate)->max($fingerprintOn) ?? '');
        $count = (string) (clone $aggregate)->count();

        return sha1($max.'|'.$count);
    }

    public function forList(QueryContext $context): LengthAwarePaginator
    {
        $query = $this->applyFilters($this->baseQuery(), $context);

        if ($context->sortColumn !== null) {
            $direction = $context->sortDirection === 'desc' ? 'desc' : 'asc';
            $query->orderBy($context->sortColumn, $direction);
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

        if (in_array(SoftDeletes::class, class_uses_recursive($modelClass))) {
            // @phpstan-ignore staticMethod.notFound
            $modelClass::withTrashed()->findOrFail($id)->forceDelete();
        } else {
            $modelClass::findOrFail($id)->delete();
        }
    }

    /**
     * Default implementation backing TableBuilder::clonable(). Copies every
     * attribute except the primary key, timestamps, and unique IDs (all
     * excluded automatically by Eloquent's replicate()) plus any columns
     * named in $except (typically unique columns such as email/slug that
     * ->clonable(['email']) declared should not be duplicated verbatim).
     *
     * @param  list<string>  $except
     */
    public function duplicate(int $id, array $except = []): int
    {
        $copy = $this->baseQuery()->findOrFail($id)->replicate($except);
        $copy->save();

        return $copy->getKey();
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
