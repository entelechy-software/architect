<?php

declare(strict_types=1);

namespace Entelechy\Architect\Breadcrumbs;

use Entelechy\Architect\Table\ArchitectTableDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Builds a location-style breadcrumb trail from request path and table metadata.
 */
final class AutomaticBreadcrumbsResolver
{
    /**
     * @return list<array{title: string, url?: string|false, menu?: list<array{title: string, url?: string|false}>}>
     */
    public function forTable(ArchitectTableDefinition $definition, Request $request): array
    {
        if ($definition->breadcrumbMode !== 'automatic') {
            return BreadcrumbTrail::fromArray($definition->breadcrumbs)->toArray();
        }

        /** @var list<array{title: string, url: string|false}> $trail */
        $trail = [];
        $segments = $request->segments();

        if ($definition->breadcrumbAutoIncludeHome) {
            $trail[] = [
                'title' => $definition->breadcrumbAutoHomeTitle,
                'url' => $definition->breadcrumbAutoHomeUrl,
            ];
        }

        $path = '';
        foreach ($segments as $segment) {
            $path .= '/'.$segment;

            $trail[] = [
                'title' => Str::of($segment)->replace(['-', '_'], ' ')->title()->toString(),
                'url' => $path,
            ];
        }

        $currentTitle = $definition->pageTitle ?? $definition->title;
        if ($definition->breadcrumbAutoIncludeCurrent) {
            if ($currentTitle !== null && trim($currentTitle) !== '') {
                $lastTitle = $trail === [] ? null : $trail[array_key_last($trail)]['title'];

                if ($lastTitle === null || strcasecmp($lastTitle, $currentTitle) !== 0) {
                    $trail[] = ['title' => $currentTitle, 'url' => false];
                } elseif ($trail !== []) {
                    $last = array_pop($trail);
                    $trail[] = ['title' => $last['title'], 'url' => false];
                }
            } elseif ($trail !== []) {
                $last = array_pop($trail);
                $trail[] = ['title' => $last['title'], 'url' => false];
            }
        }

        return $trail;
    }
}
