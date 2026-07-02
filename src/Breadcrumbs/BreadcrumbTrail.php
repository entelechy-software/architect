<?php

declare(strict_types=1);

namespace Entelechy\Architect\Breadcrumbs;

/**
 * Immutable collection of breadcrumb items.
 */
final class BreadcrumbTrail
{
    /**
     * @param  list<BreadcrumbItem>  $items
     */
    private function __construct(private readonly array $items) {}

    /**
     * @param  array<int, array{title: string, url?: string|false|null, menu?: array<int, array{title: string, url?: string|false|null}>}>  $items
     */
    public static function fromArray(array $items): self
    {
        $normalized = [];
        foreach ($items as $item) {
            $normalized[] = BreadcrumbItem::fromArray($item);
        }

        return new self($normalized);
    }

    /**
     * @return list<array{title: string, url?: string|false, menu?: list<array{title: string, url?: string|false}>}>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (BreadcrumbItem $item): array => $item->toArray(),
            $this->items,
        );
    }
}
