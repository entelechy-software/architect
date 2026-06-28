<?php

declare(strict_types=1);

namespace Entelechy\Architect\Toolbar;

use Entelechy\Architect\Toolbar\Items\Contracts\ToolbarItem;

/**
 * Immutable value object produced by ToolbarBuilder.
 *
 * Consumed by ToolbarEngine to render the toolbar and manage state.
 * Never instantiated directly — always via ToolbarBuilder::build().
 */
final class ArchitectToolbarDefinition
{
    /**
     * @param  list<ToolbarItem>  $items
     * @param  array<string, array<string, mixed>>  $persistConfig  keyed by item key
     */
    public function __construct(
        private readonly string $key,
        private readonly array $items,
        private readonly array $persistConfig,
        private readonly ?string $boundTarget,
        private readonly string $size,
        private readonly bool $bordered,
        private readonly bool $sticky,
        private readonly ?string $permission = null,
    ) {}

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Permission node gating the whole toolbar (e.g. 'members.toolbar').
     * Null means no top-level gate — individual items still self-filter
     * via their own visibleTo()/permission checks.
     */
    public function getPermission(): ?string
    {
        return $this->permission;
    }

    /** @return list<ToolbarItem> */
    public function getItems(): array
    {
        return $this->items;
    }

    /** @return array<string, array<string, mixed>> */
    public function getPersistConfig(): array
    {
        return $this->persistConfig;
    }

    public function getBoundTarget(): ?string
    {
        return $this->boundTarget;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    public function isBordered(): bool
    {
        return $this->bordered;
    }

    public function isSticky(): bool
    {
        return $this->sticky;
    }

    /**
     * Split items by position, preserving original order within each group.
     *
     * @return array{left: list<ToolbarItem>, center: list<ToolbarItem>, right: list<ToolbarItem>}
     */
    public function itemsByPosition(): array
    {
        $groups = ['left' => [], 'center' => [], 'right' => []];

        foreach ($this->items as $item) {
            $pos = $item->getPosition();
            if (! array_key_exists($pos, $groups)) {
                $pos = 'left';
            }
            $groups[$pos][] = $item;
        }

        /** @var array{left: list<ToolbarItem>, center: list<ToolbarItem>, right: list<ToolbarItem>} */
        return $groups;
    }
}
