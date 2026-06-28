@php
/**
 * @var array<int, array{message: string, severity: string, dismissible: bool}> $alerts
 * @var \Entelechy\Architect\Notifications\Livewire\AlertBannerManager $this
 */
@endphp

@foreach ($alerts as $index => $alert)
    <div class="arch-alert-banner arch-alert-banner--{{ $alert['severity'] ?? 'info' }}" role="alert">
        <span class="arch-alert-banner__message">{{ $alert['message'] }}</span>
        @if ($alert['dismissible'] ?? true)
            <button
                type="button"
                class="arch-alert-banner__close"
                wire:click="dismiss({{ $index }})"
                aria-label="{{ __('Dismiss') }}"
            >
                &times;
            </button>
        @endif
    </div>
@endforeach
