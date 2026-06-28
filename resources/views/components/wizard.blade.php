@props([
    'steps' => [],
    'currentStep' => 1,
    'id' => 'wizard',
])

<div class="arch-wizard"
     x-data="{ currentStep: {{ $currentStep }} }"
     x-on:architect:wizard:next:{{ $id }}.window="currentStep = Math.min(currentStep + 1, {{ count($steps) }})"
     x-on:architect:wizard:prev:{{ $id }}.window="currentStep = Math.max(currentStep - 1, 1)"
     {{ $attributes }}>
    <div class="arch-wizard__steps">
        @foreach ($steps as $i => $step)
            <div class="arch-wizard__step"
                 data-index="{{ $i + 1 }}"
                 :data-state="currentStep > {{ $i + 1 }} ? 'complete' : (currentStep === {{ $i + 1 }} ? 'current' : 'upcoming')">
                <span class="arch-wizard__step-number">{{ $i + 1 }}</span>
                <span class="arch-wizard__step-label">{{ $step['label'] }}</span>
                @if ($step['description'] ?? null)
                    <span class="arch-wizard__step-description">{{ $step['description'] }}</span>
                @endif
            </div>
        @endforeach
    </div>
    <div class="arch-wizard__panel">{{ $slot }}</div>
</div>
