<?php

declare(strict_types=1);

namespace Entelechy\Architect\Notifications\Livewire;

use Illuminate\View\View;
use Livewire\Component;

/**
 * Livewire bridge for Architect toast notifications.
 *
 * On mount, picks up any session flash and re-dispatches it as a browser
 * event so Alpine.js can show the toast. Also listens for
 * 'architect:toast' Livewire events emitted from other components.
 *
 * Place once per layout: <livewire:architect-toast-manager />
 *
 * Registration: 'architect-toast-manager'
 */
class ToastManager extends Component
{
    /** @var array<string, mixed>|null */
    public ?array $toast = null;

    public function mount(): void
    {
        $flashed = session('architect_toast');

        if (is_array($flashed)) {
            $this->toast = $flashed;
            $this->dispatch('architect:toast:show', toast: $flashed);
        }
    }

    /** Called by other Livewire components via $this->dispatch('architect:toast', ...). */
    public function show(string $message, string $severity = 'info', ?int $dismissAfter = null): void
    {
        $toast = [
            'message' => $message,
            'severity' => $severity,
            'dismissAfter' => $dismissAfter ?? (int) config('architect.toast.duration', 4000),
            'dismissible' => true,
        ];

        $this->toast = $toast;
        $this->dispatch('architect:toast:show', toast: $toast);
    }

    public function dismiss(): void
    {
        $this->toast = null;
    }

    public function render(): View
    {
        return view('architect::notifications.toast-manager');
    }
}
