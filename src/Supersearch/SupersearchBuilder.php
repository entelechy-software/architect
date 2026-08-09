<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch;

use Entelechy\Architect\Supersearch\SearchSets\ModelSearchSet;
use Entelechy\Architect\Supersearch\SearchSets\NavigationSearchSet;

/**
 * Fluent builder for an Architect Supersearch definition.
 *
 * Usage:
 * ```php
 * Architect::supersearch()
 *     ->key('global')
 *     ->placeholder('Search or jump to…')
 *     ->shortcut('cmd+k')
 *     ->searchSet(MyNavSet::make())
 *     ->searchSet(MyModelSet::for(Member::class)->fields(['name'])->...)
 *     ->build();
 * ```
 *
 * For use as a definition class (the recommended pattern), implement a class:
 *
 *   class MySupersearchDefinition implements \Entelechy\Architect\Supersearch\Contracts\ProvidesSupersearchDefinition {
 *       public static function definition(): ArchitectSupersearchDefinition {
 *           return Architect::supersearch()->key('global')->searchSet(...)->build();
 *       }
 *   }
 *
 * And pass it as: <livewire:architect-supersearch definition-class="MySupersearchDefinition" />
 */
final class SupersearchBuilder
{
    private string $key = 'global';

    private string $placeholder = 'Search or jump to…';

    private string $shortcut = 'cmd+k';

    /** @var list<ModelSearchSet|NavigationSearchSet> */
    private array $searchSets = [];

    private ?string $permission = null;

    private function __construct() {}

    public static function make(): self
    {
        return new self;
    }

    // -------------------------------------------------------------------------
    // Fluent configuration
    // -------------------------------------------------------------------------

    /**
     * Unique key for this supersearch instance.
     * Used to scope localStorage recent-searches.
     */
    public function key(string $key): self
    {
        $clone = clone $this;
        $clone->key = $key;

        return $clone;
    }

    /** Placeholder shown in the search input before the user types. */
    public function placeholder(string $placeholder): self
    {
        $clone = clone $this;
        $clone->placeholder = $placeholder;

        return $clone;
    }

    /**
     * Keyboard shortcut that opens the overlay.
     * Supports 'cmd+k', 'ctrl+k', or any single modifier+key combination.
     * Default: 'cmd+k' (Meta+K on Mac, Ctrl+K elsewhere).
     */
    public function shortcut(string $shortcut): self
    {
        $clone = clone $this;
        $clone->shortcut = $shortcut;

        return $clone;
    }

    /**
     * Register a search set.
     * Sets are displayed in ascending priority order (lower number = higher up).
     */
    public function searchSet(ModelSearchSet|NavigationSearchSet $set): self
    {
        $clone = clone $this;
        $clone->searchSets[] = $set;

        return $clone;
    }

    /**
     * Permission node gating the whole supersearch feature. When set,
     * SupersearchEngine checks it on every search and dispatches
     * `architect:unauthorized` if the current user lacks it. Individual
     * search sets still self-filter results via their own permission checks.
     */
    public function permission(?string $node): self
    {
        $clone = clone $this;
        $clone->permission = $node;

        return $clone;
    }

    // -------------------------------------------------------------------------
    // Build
    // -------------------------------------------------------------------------

    public function build(): ArchitectSupersearchDefinition
    {
        return new ArchitectSupersearchDefinition(
            key: $this->key,
            placeholder: $this->placeholder,
            shortcut: $this->shortcut,
            searchSets: $this->searchSets,
            permission: $this->permission,
        );
    }
}
