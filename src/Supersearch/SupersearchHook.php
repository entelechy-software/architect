<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch;

use Entelechy\Architect\Supersearch\SearchSets\ModelSearchSet;
use Entelechy\Architect\Supersearch\SearchSets\NavigationSearchSet;

/**
 * A contextual hook that injects additional search sets into the Supersearch
 * overlay while a particular Architect engine (Table, SPA tabs, etc.) is
 * mounted on the page.
 *
 * The hook is registered by implementing HasSupersearchHook on a
 * table/navigator definition class. The engine dispatches a Livewire event
 * with the definition class name on mount; SupersearchEngine stores the class
 * name and calls `$definitionClass::supersearchHook()` at query time, keeping
 * callables server-side and avoiding serialisation problems.
 *
 * Usage:
 * ```php
 * public static function supersearchHook(): SupersearchHook
 * {
 *     return SupersearchHook::make()
 *         ->searchSet(AdviceSearchSet::cases())
 *         ->searchSet(AdviceSearchSet::navigation());
 * }
 * ```
 */
final class SupersearchHook
{
    /** @var list<ModelSearchSet|NavigationSearchSet> */
    private array $searchSets = [];

    /**
     * Context key for this hook.
     *
     * Used by ModelSearchSet->access([...]) to restrict a search set to pages
     * where a hook with a matching key is mounted. Should be a stable, namespaced
     * string — e.g. 'advice.cases', 'activities.committees'.
     */
    private ?string $key = null;

    private function __construct() {}

    public static function make(): self
    {
        return new self;
    }

    /**
     * Declare a stable context key for this hook.
     *
     * This key is collected by SupersearchEngine and used to satisfy
     * ModelSearchSet->access([...]) constraints. Use a short namespaced string:
     *
     * ```php
     * SupersearchHook::make()->key('advice.cases')->searchSets(AdviceSearchSet::all());
     * ```
     */
    public function key(string $key): self
    {
        $clone = clone $this;
        $clone->key = $key;

        return $clone;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    /**
     * Add a search set that will be active while this engine is mounted.
     */
    public function searchSet(ModelSearchSet|NavigationSearchSet $set): self
    {
        $clone = clone $this;
        $clone->searchSets[] = $set;

        return $clone;
    }

    /**
     * @return list<ModelSearchSet|NavigationSearchSet>
     */
    public function getSearchSets(): array
    {
        return $this->searchSets;
    }
}
