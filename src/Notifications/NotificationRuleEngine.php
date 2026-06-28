<?php

declare(strict_types=1);

namespace Entelechy\Architect\Notifications;

use Entelechy\Architect\Notifications\Models\ArchitectAnnouncement;
use Entelechy\Architect\Notifications\Models\ArchitectNotification;
use Entelechy\Architect\Notifications\Models\NotificationRule;

/**
 * Evaluates all enabled notification rules for a given trigger and fires
 * the appropriate notification delivery.
 *
 * Bound as a singleton in the service container.
 *
 * Usage (e.g. after approving a member):
 *
 *   app(NotificationRuleEngine::class)->fire('member.approved', [
 *       'memberId'   => $member->id,
 *       'memberName' => $member->name,
 *   ]);
 */
final class NotificationRuleEngine
{
    /**
     * @param  string  $trigger  Registered trigger key.
     * @param  array<string, mixed>  $context  Variables to be substituted in message templates.
     */
    public function fire(string $trigger, array $context = []): void
    {
        $rules = NotificationRule::query()
            ->forTrigger($trigger)
            ->get();

        foreach ($rules as $rule) {
            $this->deliver($rule, $context);
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function deliver(NotificationRule $rule, array $context): void
    {
        $message = $this->interpolate((string) $rule->message_template, $context);

        match ($rule->notification_type) {
            'toast' => $this->deliverToast($rule, $message, $context),
            'alert' => $this->deliverAlert($rule, $message, $context),
            'inbox' => $this->deliverInbox($rule, $message, $context),
            'announcement' => $this->deliverAnnouncement($rule, $message, $context),
            default => null,
        };
    }

    /** @param array<string, mixed> $context */
    private function deliverToast(NotificationRule $rule, string $message, array $context): void
    {
        unset($context); // reserved for future envelope metadata
        session()->flash('architect_toast', ['message' => $message, 'severity' => $rule->severity]);
    }

    /** @param array<string, mixed> $context */
    private function deliverAlert(NotificationRule $rule, string $message, array $context): void
    {
        unset($context);
        $alerts = session('architect_alerts', []);
        $alerts[] = ['message' => $message, 'severity' => $rule->severity];
        session()->put('architect_alerts', $alerts);
    }

    /** @param array<string, mixed> $context */
    private function deliverAnnouncement(NotificationRule $rule, string $message, array $context): void
    {
        unset($context);
        ArchitectAnnouncement::create([
            'severity' => $rule->severity,
            'message' => $message,
            'expires_at' => null,
            'enabled' => true,
        ]);
    }

    /** @param array<string, mixed> $context */
    private function deliverInbox(NotificationRule $rule, string $message, array $context): void
    {
        // Recipient resolution is app-specific; a null recipient_resolver skips delivery.
        if ($rule->recipient_resolver === null) {
            return;
        }

        // Host apps can bind a custom resolver class; this provides a basic default.
        ArchitectNotification::create([
            'recipient_id' => $context['recipientId'] ?? 0,
            'type' => $rule->trigger,
            'data' => ['message' => $message] + $context,
            'action_url' => null,
        ]);
    }

    /**
     * Simple {{variable}} interpolation for message templates.
     *
     * @param  array<string, mixed>  $context
     */
    private function interpolate(string $template, array $context): string
    {
        foreach ($context as $key => $value) {
            $template = str_replace('{{'.$key.'}}', (string) $value, $template);
        }

        return $template;
    }
}
