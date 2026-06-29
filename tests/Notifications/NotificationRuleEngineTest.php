<?php

declare(strict_types=1);

namespace Entelechy\Architect\Tests\Notifications;

use Entelechy\Architect\Notifications\Models\NotificationRule;
use Entelechy\Architect\Notifications\NotificationRuleEngine;
use Entelechy\Architect\Tests\TestCase;

class NotificationRuleEngineTest extends TestCase
{
    public function test_fire_with_no_matching_rules_does_not_throw(): void
    {
        $engine = app(NotificationRuleEngine::class);

        // No rules exist — should be a no-op
        $engine->fire('nonexistent.trigger', ['foo' => 'bar']);

        $this->assertTrue(true);
    }

    public function test_fire_with_disabled_rule_skips_delivery(): void
    {
        NotificationRule::create([
            'trigger'           => 'test.skipped',
            'notification_type' => 'alert',
            'severity'          => 'info',
            'message_template'  => 'Should not appear',
            'recipient_resolver' => null,
            'enabled'           => false,
        ]);

        app(NotificationRuleEngine::class)->fire('test.skipped');

        $this->assertNull(session('architect_alerts'));
    }

    public function test_fire_alert_rule_writes_to_session(): void
    {
        NotificationRule::create([
            'trigger'           => 'member.approved',
            'notification_type' => 'alert',
            'severity'          => 'success',
            'message_template'  => 'Member {{name}} was approved.',
            'recipient_resolver' => null,
            'enabled'           => true,
        ]);

        app(NotificationRuleEngine::class)->fire('member.approved', ['name' => 'Alice']);

        $alerts = session('architect_alerts');

        $this->assertIsArray($alerts);
        $this->assertCount(1, $alerts);
        $this->assertSame('Member Alice was approved.', $alerts[0]['message']);
        $this->assertSame('success', $alerts[0]['severity']);
    }

    public function test_template_interpolation_replaces_multiple_variables(): void
    {
        NotificationRule::create([
            'trigger'           => 'order.placed',
            'notification_type' => 'alert',
            'severity'          => 'info',
            'message_template'  => 'Order #{{orderId}} placed by {{customerName}}.',
            'recipient_resolver' => null,
            'enabled'           => true,
        ]);

        app(NotificationRuleEngine::class)->fire('order.placed', [
            'orderId'      => 42,
            'customerName' => 'Bob',
        ]);

        $alerts = session('architect_alerts');
        $this->assertSame('Order #42 placed by Bob.', $alerts[0]['message']);
    }

    public function test_template_interpolation_leaves_unmatched_placeholders(): void
    {
        NotificationRule::create([
            'trigger'           => 'test.unmatched',
            'notification_type' => 'alert',
            'severity'          => 'info',
            'message_template'  => 'Hello {{unknown}}.',
            'recipient_resolver' => null,
            'enabled'           => true,
        ]);

        app(NotificationRuleEngine::class)->fire('test.unmatched', []);

        $alerts = session('architect_alerts');
        $this->assertSame('Hello {{unknown}}.', $alerts[0]['message']);
    }
}
