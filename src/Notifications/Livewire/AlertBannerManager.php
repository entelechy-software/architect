<?php

declare(strict_types=1);

namespace Entelechy\Architect\Notifications\Livewire;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Manages session-backed alert banners shown below the main navigation.
 *
 * Alerts are stored in the session as an array. Each is individually
 * dismissible. New alerts are added via NotificationBuilder::as('alert').
 *
 * Place once per layout: <livewire:architect-alert-banner-manager />
 *
 * Registration: 'architect-alert-banner-manager'
 */
class AlertBannerManager extends Component
{
    /** @var array<int, array{message: string, severity: string, dismissible: bool}> */
    public array $alerts = [];

    public function mount(): void
    {
        $sessionAlerts = session('architect_alerts', []);

        if (is_array($sessionAlerts)) {
            $this->alerts = array_values($sessionAlerts);
        }

        // Clear from session so they don't re-appear on next request.
        session()->forget('architect_alerts');
    }

    public function dismiss(int $index): void
    {
        if (isset($this->alerts[$index])) {
            array_splice($this->alerts, $index, 1);
        }
    }

    public function render(): View
    {
        return view('architect::notifications.alert-banner');
    }
}
