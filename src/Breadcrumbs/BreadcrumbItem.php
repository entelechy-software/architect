<?php

declare(strict_types=1);

namespace Entelechy\Architect\Breadcrumbs;

/**
 * Immutable breadcrumb node used by core breadcrumb flows.
 */
final class BreadcrumbItem
{
    /**
     * @param  list<BreadcrumbItem>  $menu
     */
    public function __construct(
        public readonly string $title,
        public readonly string|false|null $url = null,
        public readonly array $menu = [],
    ) {
        if (trim($title) === '') {
            throw new \InvalidArgumentException('Breadcrumb item title must be a non-empty string.');
        }
    }

    /**
     * @param  array{title: string, url?: string|false|null, menu?: array<int, array{title: string, url?: string|false|null}>}  $item
     */
    public static function fromArray(array $item): self
    {
        $menu = [];
        foreach ($item['menu'] ?? [] as $menuItem) {
            $menu[] = self::fromArray($menuItem);
        }

        return new self(
            title: $item['title'],
            url: $item['url'] ?? null,
            menu: $menu,
        );
    }

    /**
     * @return array{title: string, url?: string|false, menu?: list<array{title: string, url?: string|false}>}
     */
    public function toArray(): array
    {
        $payload = ['title' => $this->title];

        if ($this->url !== null) {
            $payload['url'] = $this->url;
        }

        if ($this->menu !== []) {
            $payload['menu'] = array_map(
                static fn (BreadcrumbItem $item): array => $item->toArray(),
                $this->menu,
            );
        }

        return $payload;
    }
}
