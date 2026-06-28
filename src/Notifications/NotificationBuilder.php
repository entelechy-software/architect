<?php

declare(strict_types=1);

namespace Entelechy\Architect\Notifications;

use Carbon\Carbon;
use Entelechy\Architect\Notifications\Models\ArchitectAnnouncement;
use Entelechy\Architect\Notifications\Models\ArchitectNotification;

/**
 * Fluent builder for dispatching Architect notifications.
 *
 * Accessed via the Architect facade:
 *
 *   Architect::toast()->success('Record saved successfully.')->send();
 *   Architect::notify($user)->inbox('member.welcome', ['name' => $user->name])->send();
 *   Architect::announce()->message('System maintenance at midnight.')->severity('warning')->send();
 *
 * Dispatching happens immediately on send(). For toast and alert types the
 * notification is stored in the session flash and picked up by the Livewire
 * components on the next page render.
 */
final class NotificationBuilder
{
    private string $notificationType = 'toast';

    private string $severity = 'info';

    private string $message = '';

    private bool $dismissible = true;

    private ?int $dismissAfterMs = null;

    private mixed $forUser = null;

    private ?string $inboxType = null;

    /** @var array<string, mixed> */
    private array $eventData = [];

    private ?string $actionUrl = null;

    private ?Carbon $expires = null;

    public function as(string $type): static
    {
        $clone = clone $this;
        $clone->notificationType = $type;

        return $clone;
    }

    public function success(string $msg = ''): static
    {
        $clone = clone $this;
        $clone->severity = 'success';
        if ($msg !== '') {
            $clone->message = $msg;
        }

        return $clone;
    }

    public function error(string $msg = ''): static
    {
        $clone = clone $this;
        $clone->severity = 'danger';
        if ($msg !== '') {
            $clone->message = $msg;
        }

        return $clone;
    }

    public function warning(string $msg = ''): static
    {
        $clone = clone $this;
        $clone->severity = 'warning';
        if ($msg !== '') {
            $clone->message = $msg;
        }

        return $clone;
    }

    public function info(string $msg = ''): static
    {
        $clone = clone $this;
        $clone->severity = 'info';
        if ($msg !== '') {
            $clone->message = $msg;
        }

        return $clone;
    }

    public function critical(string $msg = ''): static
    {
        $clone = clone $this;
        $clone->severity = 'critical';
        if ($msg !== '') {
            $clone->message = $msg;
        }

        return $clone;
    }

    public function message(string $msg): static
    {
        $clone = clone $this;
        $clone->message = $msg;

        return $clone;
    }

    public function severity(string $severity): static
    {
        $clone = clone $this;
        $clone->severity = $severity;

        return $clone;
    }

    public function dismissible(bool $value = true): static
    {
        $clone = clone $this;
        $clone->dismissible = $value;

        return $clone;
    }

    public function dismissAfter(int $milliseconds): static
    {
        $clone = clone $this;
        $clone->dismissAfterMs = $milliseconds;

        return $clone;
    }

    /** Mark this notification as persistent (not auto-dismissed). Stored in configuration. */
    public function persistent(bool $value = true): static
    {
        $clone = clone $this;
        $clone->dismissAfterMs = $value ? 0 : null;

        return $clone;
    }

    public function for(mixed $user): static
    {
        $clone = clone $this;
        $clone->forUser = $user;
        $clone->notificationType = 'inbox';

        return $clone;
    }

    /**
     * Configure this notification as an inbox message.
     *
     * @param  array<string, mixed>  $data
     */
    public function inbox(string $type, array $data = []): static
    {
        $clone = clone $this;
        $clone->notificationType = 'inbox';
        $clone->inboxType = $type;
        $clone->eventData = $data;

        return $clone;
    }

    public function actionUrl(string $url): static
    {
        $clone = clone $this;
        $clone->actionUrl = $url;

        return $clone;
    }

    public function expires(Carbon $at): static
    {
        $clone = clone $this;
        $clone->expires = $at;

        return $clone;
    }

    /**
     * Register a trigger with the TriggerRegistry singleton.
     *
     * @param  array<string, string>  $context
     */
    public function registerTrigger(string $key, string $label, array $context = []): static
    {
        app(TriggerRegistry::class)->register($key, $label, $context);

        return $this;
    }

    /** Dispatch the notification. */
    public function send(): void
    {
        match ($this->notificationType) {
            'toast' => $this->dispatchToast(),
            'alert' => $this->dispatchAlert(),
            'inbox' => $this->dispatchInbox(),
            'announcement' => $this->dispatchAnnouncement(),
            default => $this->dispatchToast(),
        };
    }

    private function dispatchToast(): void
    {
        session()->flash('architect_toast', [
            'message' => $this->message,
            'severity' => $this->severity,
            'dismissAfter' => $this->dismissAfterMs ?? config('architect.toast.duration', 4000),
            'dismissible' => $this->dismissible,
        ]);
    }

    private function dispatchAlert(): void
    {
        $alerts = session('architect_alerts', []);
        $alerts[] = [
            'message' => $this->message,
            'severity' => $this->severity,
            'dismissible' => $this->dismissible,
        ];
        session()->put('architect_alerts', $alerts);
    }

    private function dispatchInbox(): void
    {
        if ($this->forUser === null) {
            return;
        }

        $recipientId = is_object($this->forUser) && method_exists($this->forUser, 'getKey')
            ? $this->forUser->getKey()
            : (int) $this->forUser;

        ArchitectNotification::create([
            'recipient_id' => $recipientId,
            'type' => $this->inboxType ?? 'notification',
            'data' => array_merge(['message' => $this->message, 'severity' => $this->severity], $this->eventData),
            'action_url' => $this->actionUrl,
        ]);
    }

    private function dispatchAnnouncement(): void
    {
        ArchitectAnnouncement::create([
            'severity' => $this->severity,
            'message' => $this->message,
            'expires_at' => $this->expires,
            'enabled' => true,
        ]);
    }
}
