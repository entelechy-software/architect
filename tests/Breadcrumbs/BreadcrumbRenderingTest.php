<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Breadcrumbs;

use Entelechy\Architect\Tests\TestCase;
use Illuminate\Support\Facades\Blade;

class BreadcrumbRenderingTest extends TestCase
{
    public function test_breadcrumb_component_renders_dropdown_menu_items(): void
    {
        $html = Blade::render(<<<'BLADE'
    <x-architect::breadcrumbs :items="$items" />
BLADE, [
            'items' => [
                [
                    'title' => 'Admin',
                    'url' => '/admin',
                    'menu' => [
                        ['title' => 'Dashboard', 'url' => '/admin/dashboard'],
                        ['title' => 'Users', 'url' => '/admin/users'],
                    ],
                ],
                ['title' => 'Settings'],
            ],
        ]);

        $this->assertStringContainsString('arch-breadcrumb-item__toggle', $html);
        $this->assertStringContainsString('aria-haspopup="menu"', $html);
        $this->assertStringContainsString('Dashboard', $html);
        $this->assertStringContainsString('Users', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
    }
}
