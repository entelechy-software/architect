<?php

declare(strict_types=1);

namespace Entelechy\Architect\Supersearch\SearchSets;

use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Supersearch\Actions\Contracts\SearchAction;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * A search set built from a static list of navigation entries.
 *
 * Entries are matched by a simple case-insensitive substring check against
 * the label and any registered aliases. Results are not paginated — all
 * matching entries are returned up to the configured limit.
 *
 * Usage:
 * ```php
 * NavigationSearchSet::make()
 *     ->groupLabel('Navigation')
 *     ->priority(100)
 *     ->add('cases',    'Advice Cases',   'fas fa-folder-open', HrefAction::make('/advice/cases'))
 *     ->add('new-case', 'New Advice Case', 'fas fa-plus',       HrefAction::make('/advice/cases/create'))
 *     ->aliases('new-case', ['create case', 'open case'])
 * ```
 */
final class NavigationSearchSet
{
    private string $groupLabel = 'Navigation';

    /** Lower numbers appear first. */
    private int $priority = 100;

    private ?string $permission = null;

    private int $limit = 12;

    /**
     * @var list<array{key: string, label: string, icon: string, action: SearchAction, permission: string|null, aliases: list<string>}>
     */
    private array $entries = [];

    private function __construct() {}

    public static function make(): self
    {
        return new self;
    }

    // -------------------------------------------------------------------------
    // Fluent configuration
    // -------------------------------------------------------------------------

    public function groupLabel(string $label): self
    {
        $clone = clone $this;
        $clone->groupLabel = $label;

        return $clone;
    }

    public function priority(int $priority): self
    {
        $clone = clone $this;
        $clone->priority = $priority;

        return $clone;
    }

    /**
     * Permission node that the current user must hold to see this group at all.
     * Per-entry permissions are configured via the `$permission` parameter in add().
     */
    public function permission(string $node): self
    {
        $clone = clone $this;
        $clone->permission = $node;

        return $clone;
    }

    public function limit(int $limit): self
    {
        $clone = clone $this;
        $clone->limit = $limit;

        return $clone;
    }

    /**
     * Register a navigation entry.
     *
     * @param  string  $key  Unique identifier within this set
     * @param  string  $label  Human-readable name (matched during search)
     * @param  string  $icon  Font-Awesome class (e.g. 'fas fa-home')
     * @param  SearchAction  $action  Action executed on selection
     * @param  string|null  $permission  Permission node required to see this entry
     */
    public function add(
        string $key,
        string $label,
        string $icon,
        SearchAction $action,
        ?string $permission = null,
    ): self {
        $clone = clone $this;
        $clone->entries[] = [
            'key' => $key,
            'label' => $label,
            'icon' => $icon,
            'action' => $action,
            'permission' => $permission,
            'aliases' => [],
        ];

        return $clone;
    }

    /**
     * Register additional search aliases for the entry identified by $key.
     * Aliases are matched the same way as the label.
     *
     * @param  list<string>  $aliases
     */
    public function aliases(string $key, array $aliases): self
    {
        $clone = clone $this;
        foreach ($clone->entries as &$entry) {
            if ($entry['key'] === $key) {
                $entry['aliases'] = array_values(array_unique(array_merge($entry['aliases'], $aliases)));
                break;
            }
        }
        unset($entry);

        return $clone;
    }

    // -------------------------------------------------------------------------
    // Introspection
    // -------------------------------------------------------------------------

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

    // -------------------------------------------------------------------------
    // Execution
    // -------------------------------------------------------------------------

    /**
     * Match entries against the query and return plain result arrays.
     *
     * @return list<array<string, mixed>>
     */
    public function resolveResults(string $query, ?Authenticatable $user, PermissionResolver $resolver): array
    {
        if ($this->permission !== null && ! $resolver->can($user, $this->permission)) {
            return [];
        }

        $lower = mb_strtolower($query);
        $results = [];

        foreach ($this->entries as $entry) {
            if ($entry['permission'] !== null && ! $resolver->can($user, $entry['permission'])) {
                continue;
            }

            if (! $this->matchesQuery($lower, $entry)) {
                continue;
            }

            $results[] = [
                'icon' => $entry['icon'],
                'iconColour' => null,
                'avatar' => null,
                'eyebrow' => null,
                'title' => $entry['label'],
                'badge' => null,
                'badgeColour' => null,
                'meta' => null,
                'timestamp' => null,
                'tags' => [],
                'dim' => false,
                'action' => $entry['action']->toArray(),
            ];

            if (count($results) >= $this->limit) {
                break;
            }
        }

        return $results;
    }

    /** @param array{label: string, aliases: list<string>} $entry */
    private function matchesQuery(string $lowerQuery, array $entry): bool
    {
        if (str_contains(mb_strtolower($entry['label']), $lowerQuery)) {
            return true;
        }

        foreach ($entry['aliases'] as $alias) {
            if (str_contains(mb_strtolower($alias), $lowerQuery)) {
                return true;
            }
        }

        return false;
    }
}
