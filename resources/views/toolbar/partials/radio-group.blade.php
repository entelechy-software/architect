{{--
    Toolbar partial: ToolbarRadioGroup
    Renders a mutually-exclusive segmented button control.
    Clicking an option calls wire:click="setRadio(key, value)".
    The current value is read from $radioValues[key] on the Livewire component.
--}}
@php
    /** @var \Entelechy\Architect\Toolbar\Items\ToolbarRadioGroup $item */
    $currentValue = $radioValues[$item->getKey()] ?? $item->getDefault() ?? '';
    $persist      = $item->getPersist();
    $lsKey        = $persist === 'local' ? "architectToolbar_{$toolbarKey}_radio_{$item->getKey()}" : null;
@endphp

<div
    class="btn-group{{ $item->getSize() === 'sm' ? ' btn-group-sm' : '' }}"
    role="group"
    aria-label="{{ $item->getKey() }}"
    @if ($lsKey)
        x-init="
            const __stored = localStorage.getItem('{{ $lsKey }}');
            if (__stored !== null) $wire.call('setRadio', '{{ $item->getKey() }}', __stored);
        "
    @endif
>
    @foreach ($item->getOptions() as $option)
        @php
            $isActive  = $currentValue === $option['value'];
            $btnClass  = $isActive ? 'arch-btn-primary' : 'arch-btn-outline-secondary';
        @endphp
        <button
            type="button"
            class="arch-btn arch-btn-sm {{ $btnClass }}"
            wire:click="setRadio('{{ $item->getKey() }}', '{{ $option['value'] }}')"
            @if ($item->isDisabled() || $option['disabled']) disabled @endif
            aria-pressed="{{ $isActive ? 'true' : 'false' }}"
        >
            @if ($option['icon'] !== null)
                <i class="{{ $option['icon'] }}"></i>
            @endif
            <span>{{ $option['label'] }}</span>
        </button>
    @endforeach
</div>
