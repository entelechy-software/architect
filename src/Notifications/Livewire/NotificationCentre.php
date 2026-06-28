<?php

declare(strict_types=1);

namespace Entelechy\Architect\Notifications\Livewire;

use Entelechy\Architect\Notifications\Models\ArchitectNotification;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Notification inbox — shows per-recipient notifications with read/unread state.
 *
 * Polls for new notifications at the interval configured in
 * config('architect.notifications.polling_interval', '30s').
 *
 * Place once per authenticated layout — recipientId defaults to the
 * current authenticated user, so the zero-prop form is the common case:
 *   <livewire:architect-notification-centre />
 *
 * Pass an explicit recipientId to scope to a different user (e.g. an
 * admin "view as" panel):
 *   <livewire:architect-notification-centre :recipient-id="$otherUser->id" />
 *
 * Registration: 'architect-notification-centre'
 */
class NotificationCentre extends Component
{
    public int $recipientId;

    public bool $open = false;

    public int $unreadCount = 0;

    public function mount(?int $recipientId = null): void
    {
        $this->recipientId = $recipientId ?? (int) auth()->id();
        $this->refreshUnreadCount();
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function markRead(int $notificationId): void
    {
        $notification = ArchitectNotification::query()
            ->where('id', $notificationId)
            ->where('recipient_id', $this->recipientId)
            ->first();

        if ($notification !== null) {
            $notification->markRead();
        }

        $this->refreshUnreadCount();
    }

    public function markAllRead(): void
    {
        ArchitectNotification::query()
            ->where('recipient_id', $this->recipientId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $this->refreshUnreadCount();
    }

    private function refreshUnreadCount(): void
    {
        $this->unreadCount = ArchitectNotification::query()
            ->where('recipient_id', $this->recipientId)
            ->whereNull('read_at')
            ->count();
    }

    public function render(): View
    {
        $notifications = ArchitectNotification::query()
            ->where('recipient_id', $this->recipientId)
            ->latest()
            ->limit((int) config('architect.notifications.centre_max_items', 50))
            ->get();

        return view('architect::notifications.centre', [
            'notifications' => $notifications,
        ]);
    }
}
