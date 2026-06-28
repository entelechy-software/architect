<?php

declare(strict_types=1);

namespace Entelechy\Architect\Notifications\Livewire;

use Entelechy\Architect\Notifications\Models\ArchitectAnnouncement;
use Illuminate\View\View;
use Livewire\Component;

/**
 * Shows active site-wide announcement banners.
 *
 * Dismissed announcements are tracked in the session so they don't
 * reappear in the same browser session.
 *
 * Place once per layout:
 *   <livewire:architect-announcement-banner />
 *
 * Registration: 'architect-announcement-banner'
 */
class AnnouncementBanner extends Component
{
    /** @var array<int, int> IDs of announcements dismissed this session. */
    public array $dismissed = [];

    public function mount(): void
    {
        $this->dismissed = array_map(
            'intval',
            session('architect_dismissed_announcements', [])
        );
    }

    public function dismiss(int $id): void
    {
        if (! in_array($id, $this->dismissed, true)) {
            $this->dismissed[] = $id;
            session()->put('architect_dismissed_announcements', $this->dismissed);
        }
    }

    public function render(): View
    {
        $announcements = ArchitectAnnouncement::query()
            ->active()
            ->when(count($this->dismissed) > 0, fn ($q) => $q->whereNotIn('id', $this->dismissed))
            ->latest()
            ->get();

        return view('architect::notifications.announcement-banner', [
            'announcements' => $announcements,
        ]);
    }
}
