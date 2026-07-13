@php
/**
 * @var \Entelechy\Architect\Forms\ArchitectWizardDefinition $definition
 * @var string $currentStepId
 */
$totalSteps = $definition->totalSteps();
$stepIndex  = $definition->stepIndex($currentStepId);
$currentNum = $stepIndex !== null ? $stepIndex + 1 : 1;
$step       = $definition->findStep($currentStepId);
$isDirty    = $definition->guardDirtyNavigation && $this->isDirty();
@endphp

<div class="arch-wizard" data-key="{{ $definition->key }}">
    {{-- Step progress bar --}}
    <div class="arch-wizard__progress">
        @foreach ($definition->steps as $idx => $s)
            @php $num = $idx + 1; @endphp
            <div
                class="arch-wizard__step {{ $num < $currentNum ? 'is-complete' : '' }} {{ $s['id'] === $currentStepId ? 'is-active' : '' }}"
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

            @if ($step['structure'] === [])
                {{-- Empty structure = a summary/confirmation step: recap the answers gathered so far. --}}
                <div class="arch-wizard__summary">
                    @foreach ($definition->steps as $s)
                        @if ($s['id'] === $currentStepId)
                            @continue
                        @endif
                        @foreach ($s['structure'] as $item)
                            @if (method_exists($item, 'getName') && method_exists($item, 'getLabel'))
                                <div class="arch-wizard__summary-row">
                                    <span class="arch-wizard__summary-label">{{ $item->getLabel() }}</span>
                                    <span class="arch-wizard__summary-value">{{ data_get($formData, $item->getName()) }}</span>
                                </div>
                            @endif
                        @endforeach
                    @endforeach
                </div>
            @else
                @foreach ($step['structure'] as $item)
                    @include('architect::forms.partials.structure-item', [
                        'item' => $item,
                        'get'  => fn (string $f) => data_get($formData, $f),
                    ])
                @endforeach
            @endif
        </div>
    @endif

    {{-- Navigation --}}
    <div class="arch-wizard__footer">
        @if ($definition->cancelRoute && $currentNum === 1)
            <a href="{{ route($definition->cancelRoute) }}" class="arch-button" data-variant="ghost" data-color="secondary">
                {{ __('Cancel') }}
            </a>
        @endif

        @if ($currentNum > 1)
            <button
                type="button"
                class="arch-button"
                data-variant="ghost"
                data-color="secondary"
                wire:click="previousStep"
                @if ($isDirty) wire:confirm="{{ __('You have unsaved changes on this step. Go back anyway?') }}" @endif
            >
                {{ __('Back') }}
            </button>
        @endif

        <div class="arch-spacer"></div>

        @if ($definition->graph->nextStepId($currentStepId, $formData) !== null)
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
