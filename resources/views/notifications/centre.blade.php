@php
/**
 * @var \Illuminate\Database\Eloquent\Collection<int, \Entelechy\Architect\Notifications\Models\ArchitectNotification> $notifications
 * @var \Entelechy\Architect\Notifications\Livewire\NotificationCentre $this
 */
@endphp

<div class="arch-notification-centre" wire:poll.{{ config('architect.notifications.polling_interval', '30s') }}="refreshUnreadCount">
    {{-- Trigger button --}}
    <button
        type="button"
        class="arch-notification-centre__trigger"
        wire:click="toggle"
        aria-label="{{ __('Notifications') }}"
    >
        <x-architect::icon name="fas fa-bell" />
        @if ($unreadCount > 0)
            <span class="arch-badge arch-notification-centre__badge" data-color="danger" data-variant="solid">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    {{-- Dropdown --}}
    @if ($open)
        <div class="arch-notification-centre__panel" role="dialog" aria-label="{{ __('Notifications') }}">
            <div class="arch-notification-centre__header">
                <h3>{{ __('Notifications') }}</h3>
                @if ($unreadCount > 0)
                    <button type="button" class="arch-link" wire:click="markAllRead">
                        {{ __('Mark all read') }}
                    </button>
                @endif
                <button type="button" class="arch-notification-centre__close" wire:click="toggle" aria-label="{{ __('Close') }}">
                    &times;
                </button>
            </div>

            <div class="arch-notification-centre__list">
                @forelse ($notifications as $notification)
                    <div
                        class="arch-notification-centre__item {{ $notification->isRead() ? 'is-read' : 'is-unread' }}"
                        wire:key="notification-{{ $notification->id }}"
                    >
                        <div class="arch-notification-centre__item-body">
                            @php $data = is_array($notification->data) ? $notification->data : []; @endphp
                            <p class="arch-notification-centre__item-message">
                                {{ $data['message'] ?? $notification->type }}
                            </p>
                            <span class="arch-notification-centre__item-time">
                                {{ $notification->created_at->diffForHumans() }}
                            </span>
                        </div>
                        @if (! $notification->isRead())
                            <button
                                type="button"
                                class="arch-notification-centre__mark-read"
                                wire:click="markRead({{ $notification->id }})"
                                aria-label="{{ __('Mark as read') }}"
                            ></button>
                        @endif
                        @if ($notification->action_url)
                            <a href="{{ $notification->action_url }}" class="arch-notification-centre__action-link">
                                {{ __('View') }}
                            </a>
                        @endif
                    </div>
                @empty
                    <div class="arch-notification-centre__empty">
                        <x-architect::empty-state
                            icon="fas fa-bell-slash"
                            :heading="__('No notifications')"
                        />
                    </div>
                @endforelse
            </div>
        </div>
    @endif
</div>
