<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests;

use Entelechy\Architect\Contracts\PermissionResolver;
use Entelechy\Architect\Notifications\NotificationRuleEngine;
use Entelechy\Architect\Permissions\AllowAllPermissionResolver;
use Illuminate\Support\Facades\Schema;

class ServiceProviderTest extends TestCase
{
    public function test_config_is_merged(): void
    {
        $this->assertSame(25, config('architect.table.per_page'));
    }

    public function test_feature_flags_are_present(): void
    {
        $this->assertTrue(config('architect.features.tables'));
        $this->assertTrue(config('architect.features.forms'));
        $this->assertTrue(config('architect.features.notifications'));
    }

    public function test_permission_resolver_is_bound(): void
    {
        $this->assertInstanceOf(
            AllowAllPermissionResolver::class,
            app(PermissionResolver::class)
        );
    }

    public function test_notification_rule_engine_is_singleton(): void
    {
        $this->assertSame(
            app(NotificationRuleEngine::class),
            app(NotificationRuleEngine::class)
        );
    }

    public function test_lookup_route_is_registered(): void
    {
        $this->assertTrue(
            app('router')->getRoutes()->hasNamedRoute('architect.lookup')
        );
    }

    public function test_architect_styles_directive_renders_link_tag(): void
    {
        $output = app('blade.compiler')->compileString('@architectStyles');

        $this->assertStringContainsString('link', $output);
        $this->assertStringContainsString('architect.css', $output);
    }

    public function test_architect_scripts_directive_renders_script_tag(): void
    {
        $output = app('blade.compiler')->compileString('@architectScripts');

        $this->assertStringContainsString('script', $output);
        $this->assertStringContainsString('architect.js', $output);
    }

    public function test_import_batches_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('architect_import_batches'));
    }

    public function test_notification_rules_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('architect_notification_rules'));
    }

    public function test_announcements_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('architect_announcements'));
    }
}
