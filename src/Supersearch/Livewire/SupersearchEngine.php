<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\Livewire;

use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Supersearch\ArchitectSupersearchDefinition;
use Entelechy\Architect\Supersearch\Contracts\HasSupersearchHook;
use Entelechy\Architect\Supersearch\SearchSets\ModelSearchSet;
use Entelechy\Architect\Supersearch\SearchSets\NavigationSearchSet;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Architect Supersearch Livewire engine.
 *
 * Rendered once in the main layout. Orchestrates:
 *  - The global search overlay (keyboard shortcut, click-outside, etc.)
 *  - Search execution across registered SearchSets
 *  - Contextual hook registration from mounted Architect engines
 *  - Result selection and action dispatch
 *
 * Hook lifecycle:
 *   TableEngine / SpaTabsEngine call $this->dispatch('architect:supersearch:hook-mounted', [...])
 *   from their mount() method when their definition class implements HasSupersearchHook.
 *   Alpine's $cleanup in those engines' views dispatches a window event on DOM removal,
 *   which the Supersearch overlay turns into a $wire.onHookUnmounted() call.
 */
class SupersearchEngine extends Component
{
    /**
     * FQCN of the definition class whose static build() method returns
     * the ArchitectSupersearchDefinition driving this instance.
     */
    public string $definitionClass = '';

    /**
     * Active contextual hooks.
     * Keyed by Livewire component ID; values are definition class FQCNs.
     *
     * @var array<string, string>
     */
    public array $activeHooks = [];

    /**
     * Search results grouped by search-set.
     *
     * Each entry:
     * ```
     * [
     *   'groupKey'   => string,
     *   'groupLabel' => string,
     *   'priority'   => int,
     *   'items'      => list<array<string, mixed>>,
     * ]
     * ```
     *
     * @var list<array<string, mixed>>
     */
    public array $results = [];

    /** Whether the overlay is visible. Managed primarily via Alpine; this mirrors it. */
    public bool $open = false;

    /**
     * The last query string that was searched. Stored so the blade can
     * distinguish "no results for this query" from "nothing typed yet".
     */
    public string $lastQuery = '';

    /** Standard Engine error/loading contract — see ARCHITECT_PACKAGE_PLAN.md §0.6. */
    public bool $hasError = false;

    public string $errorMessage = '';

    public bool $isLoading = false;

    // -------------------------------------------------------------------------
    // Cached user resolution
    // -------------------------------------------------------------------------

    private ?Authenticatable $cachedUser = null;

    private bool $cachedUserResolved = false;

    // -------------------------------------------------------------------------
    // Mount
    // -------------------------------------------------------------------------

    public function mount(string $definitionClass): void
    {
        $this->definitionClass = $definitionClass;
    }

    // -------------------------------------------------------------------------
    // Hook lifecycle
    // -------------------------------------------------------------------------

    /**
     * Fired by TableEngine / SpaTabsEngine mount() when the definition
     * implements HasSupersearchHook.
     *
     * @param  array<string, mixed>  $params
     */
    #[On('architect:supersearch:hook-mounted')]
    public function onHookMounted(array $params): void
    {
        $componentId = $params['componentId'] ?? null;
        $definitionClass = $params['definitionClass'] ?? null;

        if ($componentId === null || $definitionClass === null) {
            return;
        }

        if (! is_string($definitionClass) || ! is_a($definitionClass, HasSupersearchHook::class, true)) {
            return;
        }

        $this->activeHooks[$componentId] = $definitionClass;
    }

    /**
     * Called from Alpine's $cleanup handler in hooked engine views when the
     * component is removed from the DOM (e.g. SPA navigation).
     */
    public function onHookUnmounted(string $componentId): void
    {
        unset($this->activeHooks[$componentId]);
    }

    // -------------------------------------------------------------------------
    // Search
    // -------------------------------------------------------------------------

    /**
     * Execute a search across all active search sets and store results.
     *
     * Called from Alpine after debounce.
     */
    public function search(string $query): void
    {
        $query = trim($query);

        if (mb_strlen($query) < 2) {
            $this->results = [];
            $this->lastQuery = '';

            return;
        }

        $this->lastQuery = $query;
        $this->isLoading = true;
        $this->hasError = false;
        $this->errorMessage = '';

        try {
            $user = $this->currentUser();
            $resolver = app(PermissionResolver::class);
            $def = $this->definition();

            if ($def->permission !== null && ! $resolver->can($user, $def->permission)) {
                throw new AuthorizationException('You do not have permission to use search.');
            }

            // Build the set of active context keys from currently-mounted hooks.
            // Keys are declared on SupersearchHook via ->key('advice.cases').
            /** @var list<string> $activeContextKeys */
            $activeContextKeys = [];

            /** @var list<ModelSearchSet|NavigationSearchSet> $hookSets */
            $hookSets = [];

            foreach ($this->activeHooks as $definitionClass) {
                /** @var HasSupersearchHook $definitionClass */
                $hook = $definitionClass::supersearchHook();

                if ($hook->getKey() !== null) {
                    $activeContextKeys[] = $hook->getKey();
                }

                foreach ($hook->getSearchSets() as $set) {
                    $hookSets[] = $set;
                }
            }

            // Filter global definition sets by their access constraint.
            // Hook-contributed sets always run (the hook being active is their gate).
            $sets = [];

            foreach ($def->searchSets as $set) {
                if ($set instanceof ModelSearchSet) {
                    $access = $set->getAccess();

                    if ($access === 'local') {
                        // Only show when any hook is active on the current page
                        if (empty($activeContextKeys)) {
                            continue;
                        }
                    } elseif (is_array($access)) {
                        // Only show when a hook with one of the listed keys is active
                        if (empty(array_intersect($access, $activeContextKeys))) {
                            continue;
                        }
                    }
                    // 'global': always include — no filtering needed
                }

                $sets[] = $set;
            }

            // Append hook-contributed sets (no access filtering)
            foreach ($hookSets as $set) {
                $sets[] = $set;
            }

            // Deduplicate by object identity (same instance registered twice is harmless)
            /** @var list<array{groupKey: string, groupLabel: string, priority: int, items: list<array<string, mixed>>}> $groups */
            $groups = [];

            foreach ($sets as $set) {
                $items = $set->resolveResults($query, $user, $resolver);

                if (empty($items)) {
                    continue;
                }

                $groupKey = $this->groupKeyFor($set);
                $groupLabel = $set->getGroupLabel();
                $priority = $set->getPriority();

                // Merge into existing group if keys collide (e.g. hook adds to same set)
                $existingIndex = null;
                foreach ($groups as $i => $g) {
                    if ($g['groupKey'] === $groupKey) {
                        $existingIndex = $i;
                        break;
                    }
                }

                if ($existingIndex !== null) {
                    $groups[$existingIndex] = [
                        'groupKey' => $groups[$existingIndex]['groupKey'],
                        'groupLabel' => $groups[$existingIndex]['groupLabel'],
                        'priority' => $groups[$existingIndex]['priority'],
                        'items' => array_merge($groups[$existingIndex]['items'], $items),
                    ];
                } else {
                    $groups[] = [
                        'groupKey' => $groupKey,
                        'groupLabel' => $groupLabel,
                        'priority' => $priority,
                        'items' => $items,
                    ];
                }
            }

            // Sort groups ascending by priority
            /** @var list<array<string, mixed>> $sorted */
            $sorted = $groups;
            usort($sorted, fn (array $a, array $b): int => (int) $a['priority'] <=> (int) $b['priority']);

            $this->results = $sorted;
        } catch (AuthorizationException $e) {
            $this->hasError = true;
            $this->errorMessage = $e->getMessage();
            $this->results = [];
            $this->dispatch('architect:unauthorized');
        } catch (\Throwable $e) {
            $this->hasError = true;
            $this->errorMessage = 'An error occurred while searching. Please try again.';
            $this->results = [];
            report($e);
        } finally {
            $this->isLoading = false;
        }
    }

    // -------------------------------------------------------------------------
    // Action selection
    // -------------------------------------------------------------------------

    /**
     * Handle the user selecting a result.
     *
     * Actions that require server involvement (redirect, Livewire dispatch,
     * panel open) are processed here. Pure client-side actions (copy, email,
     * phone, wire) are serialised into the result payload and handled by Alpine.
     */
    public function selectResult(int $groupIndex, int $itemIndex): mixed
    {
        $item = $this->results[$groupIndex]['items'][$itemIndex] ?? null;

        if ($item === null) {
            return null;
        }

        $action = $item['action'] ?? null;

        if ($action === null) {
            return null;
        }

        return match ($action['type'] ?? '') {
            'href' => $this->redirect($action['url'] ?? '/'),
            'download' => $this->redirect($action['url'] ?? '/'),
            'dispatch' => $this->dispatch($action['event'], $action['payload'] ?? []),
            'open-tab' => $this->dispatch('architect:open-spa-tab', [
                'type' => $action['tabType'],
                'props' => $action['props'] ?? [],
            ]),
            'panel' => $this->dispatch('architect:open-panel-'.($action['mode'] ?? 'edit'), [
                'definitionClass' => $action['definitionClass'],
                'recordId' => $action['recordId'] ?? null,
            ]),
            // copy, email, phone, wire are fully client-side — Alpine handles them
            default => null,
        };
    }

    // -------------------------------------------------------------------------
    // Overlay state
    // -------------------------------------------------------------------------

    public function openOverlay(): void
    {
        $this->open = true;
    }

    public function closeOverlay(): void
    {
        $this->open = false;
        $this->results = [];
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    public function render(): View
    {
        return view('architect::supersearch.engine', [
            'definition' => $this->definition(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Internals
    // -------------------------------------------------------------------------

    private function definition(): ArchitectSupersearchDefinition
    {
        /** @var class-string $class */
        $class = $this->definitionClass;

        return $class::build();
    }

    private function currentUser(): ?Authenticatable
    {
        if (! $this->cachedUserResolved) {
            $this->cachedUser = auth()->user();
            $this->cachedUserResolved = true;
        }

        return $this->cachedUser;
    }

    private function groupKeyFor(ModelSearchSet|NavigationSearchSet $set): string
    {
        // Use the short class name as a stable group key
        $parts = explode('\\', get_class($set));

        return strtolower(preg_replace('/SearchSet$/', '', end($parts)) ?? end($parts));
    }
}
