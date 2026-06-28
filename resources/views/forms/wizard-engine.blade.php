@php
/**
 * @var \Entelechy\Architect\Forms\ArchitectWizardDefinition $definition
 * @var int $currentStep
 */
$totalSteps = $definition->totalSteps();
$stepIndex  = $currentStep - 1;
$step       = $definition->steps[$stepIndex] ?? null;
@endphp

<div class="arch-wizard" data-key="{{ $definition->key }}">
    {{-- Step progress bar --}}
    <div class="arch-wizard__progress">
        @foreach ($definition->steps as $idx => $s)
            @php $num = $idx + 1; @endphp
            <div
                class="arch-wizard__step {{ $num < $currentStep ? 'is-complete' : '' }} {{ $num === $currentStep ? 'is-active' : '' }}"
            >
                <span class="arch-wizard__step-number">{{ $num }}</span>
                <span class="arch-wizard__step-label">{{ $s['label'] }}</span>
            </div>
            @if ($num < $totalSteps)
                <div class="arch-wizard__connector"></div>
            @endif
        @endforeach
    </div>

    {{-- Current step fields --}}
    @if ($step)
        <div class="arch-wizard__body">
            <h3 class="arch-wizard__step-title">{{ $step['label'] }}</h3>

            @foreach ($step['structure'] as $item)
                @include('architect::forms.partials.structure-item', [
                    'item' => $item,
                    'get'  => fn (string $f) => data_get($formData, $f),
                ])
            @endforeach
        </div>
    @endif

    {{-- Navigation --}}
    <div class="arch-wizard__footer">
        @if ($definition->cancelRoute && $currentStep === 1)
            <a href="{{ route($definition->cancelRoute) }}" class="arch-button" data-variant="ghost" data-color="secondary">
                {{ __('Cancel') }}
            </a>
        @endif

        @if ($currentStep > 1)
            <button type="button" class="arch-button" data-variant="ghost" data-color="secondary" wire:click="previousStep">
                {{ __('Back') }}
            </button>
        @endif

        <div class="arch-spacer"></div>

        @if ($currentStep < $totalSteps)
            <button type="button" class="arch-button" data-variant="solid" data-color="primary" wire:click="nextStep">
                {{ __('Next') }}
            </button>
        @else
            <button type="button" class="arch-button" data-variant="solid" data-color="primary" wire:click="submit" wire:loading.attr="disabled" wire:target="submit">
                <span wire:loading.remove wire:target="submit">{{ __('Finish') }}</span>
                <span wire:loading wire:target="submit">{{ __('Saving…') }}</span>
            </button>
        @endif
    </div>
</div>
