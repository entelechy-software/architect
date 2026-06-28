<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\SearchSets;

use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Supersearch\Actions\Contracts\SearchAction;
use Entelechy\Architect\Supersearch\ResultCard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * A search set backed by an Eloquent model.
 *
 * Subclasses should extend this and configure it in their constructor using
 * the fluent builder methods. Alternatively, a fully inline definition can be
 * produced via the static factory:
 *
 * ```php
 * ModelSearchSet::for(MyModel::class)
 *     ->fields(['name', 'email'])
 *     ->groupLabel('People')
 *     ->result(ResultCard::make()->title(fn ($r) => $r->name))
 *     ->action(HrefAction::make(fn ($r) => "/people/{$r->id}"));
 * ```
 */
class ModelSearchSet
{
    private string $modelClass = '';

    private string $driver = 'eloquent';

    /** @var list<string> */
    private array $fields = [];

    private string $groupLabel = '';

    /** Lower numbers appear first in results. */
    private int $priority = 50;

    private ?string $permission = null;

    /** @var callable(Authenticatable|null, mixed): bool|null */
    private $recordPermission = null;

    /**
     * Receives ($query, $user, $searchString) and returns a modified Builder.
     *
     * When provided alongside ->fields([...]), the scope is applied first and
     * the field LIKE search is added inside a nested WHERE group.
     *
     * When fields is empty and scope is provided, the scope is solely responsible
     * for filtering by the search string.
     *
     * @var (callable(Builder<Model>, Authenticatable|null, string): Builder<Model>)|null
     */
    private $scope = null;

    private ?ResultCard $resultCard = null;

    private ?SearchAction $action = null;

    private int $limit = 8;

    /**
     * Visibility scope controlling when results from this set are shown.
     *
     * - `'global'`        — always shown regardless of page context (default)
     * - `'local'`         — only shown when at least one SupersearchHook is
     *                       active on the current page
     * - `string[]`        — only shown when at least one active hook declares
     *                       one of the listed context keys via ->key(...)
     *
     * Hook-contributed sets (registered via HasSupersearchHook) are not
     * filtered by this property — the hook being active is their own gate.
     *
     * @var 'global'|'local'|list<string>
     */
    private string|array $access = 'global';

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    private function __construct() {}

    /** Inline factory — useful when a dedicated subclass is overkill. */
    public static function for(string $modelClass): self
    {
        $instance = new self;
        $instance->modelClass = $modelClass;

        return $instance;
    }

    // -------------------------------------------------------------------------
    // Fluent configuration
    // -------------------------------------------------------------------------

    /**
     * The fully-qualified Eloquent model class to search.
     * Not required when using ModelSearchSet::for().
     */
    protected function model(string $modelClass): static
    {
        $clone = clone $this;
        $clone->modelClass = $modelClass;

        return $clone;
    }

    /** Search driver. Currently only 'eloquent' is supported. */
    public function driver(string $driver): static
    {
        $clone = clone $this;
        $clone->driver = $driver;

        return $clone;
    }

    /**
     * Columns to search with LIKE '%query%'.
     *
     * When scope is also provided, the field LIKE clauses are added inside a
     * nested `AND (field1 LIKE ? OR field2 LIKE ?)` group appended to the scope.
     *
     * @param  list<string>  $fields
     */
    public function fields(array $fields): static
    {
        $clone = clone $this;
        $clone->fields = $fields;

        return $clone;
    }

    /** Label used as the group header in the results overlay. */
    public function groupLabel(string $label): static
    {
        $clone = clone $this;
        $clone->groupLabel = $label;

        return $clone;
    }

    /** Numeric sort priority — lower numbers appear first. Default: 50. */
    public function priority(int $priority): static
    {
        $clone = clone $this;
        $clone->priority = $priority;

        return $clone;
    }

    /**
     * Permission node that the current user must hold to see this group.
     * Leave unset to show to all authenticated users.
     */
    public function permission(string $node): static
    {
        $clone = clone $this;
        $clone->permission = $node;

        return $clone;
    }

    /**
     * Per-record permission check.
     *
     * @param  callable(Authenticatable|null, mixed): bool  $callable
     */
    public function recordPermission(callable $callable): static
    {
        $clone = clone $this;
        $clone->recordPermission = $callable;

        return $clone;
    }

    /**
     * Additional query constraints.
     *
     * @param  callable(Builder<Model>, Authenticatable|null, string): Builder<Model>  $callable
     */
    public function scope(callable $callable): static
    {
        $clone = clone $this;
        $clone->scope = $callable;

        return $clone;
    }

    /** Defines how each matching record should be rendered. */
    public function result(ResultCard $card): static
    {
        $clone = clone $this;
        $clone->resultCard = $card;

        return $clone;
    }

    /** The action performed when the user selects a result in this group. */
    public function action(SearchAction $action): static
    {
        $clone = clone $this;
        $clone->action = $action;

        return $clone;
    }

    /** Maximum records returned for this group. Default: 8. */
    public function limit(int $limit): static
    {
        $clone = clone $this;
        $clone->limit = $limit;

        return $clone;
    }

    /**
     * Control when results from this set appear in Supersearch.
     *
     * Pass `'global'` (default) to always show, `'local'` to only show when
     * any hook is active on the current page, or an array of hook context keys
     * to restrict to specific pages:
     *
     * ```php
     * ->access('local')
     * ->access(['advice.cases', 'advice.settings'])
     * ```
     *
     * @param  'global'|'local'|list<string>  $access
     */
    public function access(string|array $access): static
    {
        $clone = clone $this;
        $clone->access = $access;

        return $clone;
    }

    // -------------------------------------------------------------------------
    // Introspection (read by SupersearchEngine)
    // -------------------------------------------------------------------------

    public function getModelClass(): string
    {
        return $this->modelClass;
    }

    public function getDriver(): string
    {
        return $this->driver;
    }

    public function getGroupLabel(): string
    {
        return $this->groupLabel;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getPermission(): ?string
    {
        return $this->permission;
    }

    /** @return 'global'|'local'|list<string> */
    public function getAccess(): string|array
    {
        return $this->access;
    }

    // -------------------------------------------------------------------------
    // Execution
    // -------------------------------------------------------------------------

    /**
     * Run the search and return a plain array of rendered result items.
     *
     * @return list<array<string, mixed>>
     */
    public function resolveResults(string $query, ?Authenticatable $user, PermissionResolver $resolver): array
    {
        if ($this->permission !== null && ! $resolver->can($user, $this->permission)) {
            return [];
        }

        /** @var Builder<Model> $q */
        $q = $this->modelClass::query();

        if ($this->scope !== null) {
            $q = ($this->scope)($q, $user, $query);
        }

        if (! empty($this->fields)) {
            $fields = $this->fields;
            $q->where(function (Builder $inner) use ($query, $fields): void {
                foreach ($fields as $i => $field) {
                    $method = $i === 0 ? 'where' : 'orWhere';
                    $inner->$method($field, 'LIKE', '%'.$query.'%');
                }
            });
        }

        // Fetch slightly over the limit before permission filtering to avoid
        // returning too few results when some records are permission-gated.
        $overscanLimit = $this->recordPermission !== null
            ? min($this->limit * 3, 50)
            : $this->limit;

        $records = $q->limit($overscanLimit)->get();

        if ($this->recordPermission !== null) {
            $check = $this->recordPermission;
            $records = $records->filter(fn ($r) => $check($user, $r));
        }

        /** @var list<array<string, mixed>> $results */
        $results = $records
            ->take($this->limit)
            ->map(fn ($r) => $this->resultCard?->renderFor($r, $this->action) ?? [])
            ->values()
            ->all();

        return $results;
    }
}
