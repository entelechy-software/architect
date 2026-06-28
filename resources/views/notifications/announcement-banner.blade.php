@php
/**
 * @var \Illuminate\Database\Eloquent\Collection<int, \Entelechy\Architect\Notifications\Models\ArchitectAnnouncement> $announcements
 * @var \Entelechy\Architect\Notifications\Livewire\AnnouncementBanner $this
 */
@endphp

@foreach ($announcements as $announcement)
    <div
        class="arch-announcement-banner arch-announcement-banner--{{ $announcement->severity }}"
        role="alert"
        wire:key="announcement-{{ $announcement->id }}"
    >
        <span class="arch-announcement-banner__message">{{ $announcement->message }}</span>
        <button
            type="button"
            class="arch-announcement-banner__close"
            wire:click="dismiss({{ $announcement->id }})"
            aria-label="{{ __('Dismiss') }}"
        >
            &times;
        </button>
    </div>
@endforeach
