{{--
    ModuleNavigator: stepper style.
    Renders a horizontal numbered step progress indicator.

    Active state: URL prefix matching against each StepItem's href.
    Steps before the active one are rendered as "completed" (green checkmark).
    Steps after are rendered as "upcoming" (gray number).

    validateOnStep: when enabled on the definition, steps beyond the first
    incomplete step are locked — rendered as non-interactive spans with a
    lock icon. Steps at or before the first incomplete step remain clickable
    so users can go back and amend prior answers.

    validateWithForm / validateWithMethod: when either is set, forward-step
    clicks are intercepted by the stepperGuard Alpine component, which runs
    the configured validation gates before allowing navigation.
--}}
@php
    use Entelechy\Architect\Navigator\Items\StepItem;
    use Entelechy\Architect\Navigator\Items\NavSeparator;

    $activeItem  = $definition->activeItem($path);
    $steps       = array_filter($definition->items, fn ($i) => $i instanceof StepItem);
    $steps       = array_values($steps);
    $activeIndex = null;

    foreach ($steps as $idx => $s) {
        if ($activeItem === $s) {
            $activeIndex = $idx;
            break;
        }
    }

    // Determine first incomplete step index for validateOnStep locking.
    // "Incomplete" means the step has not been explicitly marked completed().
    // The active step and all prior steps remain accessible regardless.
    $firstIncompleteIdx = null;
    if ($definition->validateOnStep) {
        foreach ($steps as $idx => $s) {
            if (! $s->isCompleted()) {
                $firstIncompleteIdx = $idx;
                break;
            }
        }
    }

    // Determine whether the stepperGuard Alpine component is needed.
    // Required only when forward-step navigation guards are configured.
    // Locked steps now rely on the native title tooltip.
    $needsGuard  = $definition->validateWithForm !== null || $definition->validateWithMethod !== null;
    $needsAlpine = $needsGuard;
    $guardFormId = $definition->validateWithForm ?? '';
    $guardMethod = $definition->validateWithMethod ?? '';
@endphp

@php $wrapInCard = $wrapInCard ?? true; @endphp
@php $wrapInCard = $wrapInCard ?? true; @endphp
<x-architect::navigator.shell :wrap="$wrapInCard" :position-class="$wrapInCard ? 'mb-3' : ''">
        <div class="module-navigator module-navigator--stepper rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900"
            @if ($needsAlpine)
                x-data="stepperGuard({ formId: @js($guardFormId), wireMethod: @js($guardMethod) })"
            @endif
        >
    <ol class="flex items-center gap-0 mb-0 list-none p-0">
        @foreach ($definition->items as $idx => $item)
            @if ($item instanceof NavSeparator)
                <li class="flex-1 module-navigator__step-connector" aria-hidden="true">
                    <hr class="m-0 border-gray-200 dark:border-gray-700">
                </li>
            @elseif ($item instanceof StepItem)
                @php
                    $stepIdx    = array_search($item, $steps, true);
                    $isActive   = $activeItem === $item;
                    $isComplete = $activeIndex !== null && $stepIdx < $activeIndex;
                    $isDisabled = $item->isDisabled();

                    // A step is locked when validateOnStep is on, this step is
                    // not active, and it falls after the first incomplete step.
                    $isLocked = $definition->validateOnStep
                        && ! $isActive
                        && $firstIncompleteIdx !== null
                        && $stepIdx > $firstIncompleteIdx;

                    // A step link is guarded when navigation guards are set AND
                    // this step is a forward step (after the active one).
                    // Backward navigation is never guarded.
                    $isGuardedLink = $needsGuard
                        && $item->getHref()
                        && ! $isDisabled
                        && ! $isLocked
                        && ($activeIndex === null || $stepIdx > $activeIndex);

                    $circleClass = 'module-navigator__step-circle flex items-center justify-center rounded-full ';

                    if ($isActive) {
                        $circleClass .= 'bg-primary-600 text-white';
                    } elseif ($isComplete) {
                        $circleClass .= 'bg-success-600 text-white';
                    } else {
                        $circleClass .= 'border border-gray-200 bg-gray-100 text-gray-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400';
                    }

                    $liClass = 'module-navigator__step flex flex-col items-center';
                    if ($isDisabled || $isLocked) {
                        $liClass .= ' opacity-50';
                    }
                    if ($isLocked) {
                        $liClass .= ' module-navigator__step--locked pointer-events-none';
                    }
                @endphp
                <li class="{{ $liClass }}"
                    @if ($isLocked)
                        title="Complete previous steps before continuing"
                    @endif
                >
                    @if ($item->getHref() && ! $isDisabled && ! $isLocked)
                        @if ($isGuardedLink)
                            <a href="{{ $item->getHref() }}"
                               x-on:click.prevent="guardNavigate('{{ $item->getHref() }}')"
                               class="flex flex-col items-center no-underline">
                        @else
                            <a href="{{ $item->getHref() }}" class="flex flex-col items-center no-underline">
                        @endif
                    @else
                        <span class="flex flex-col items-center">
                    @endif

                    <span class="{{ $circleClass }}" style="width:2rem;height:2rem;font-size:.8rem;">
                        @if ($isLocked)
                            <i class="fas fa-lock"></i>
                        @elseif ($isComplete)
                            <i class="fas fa-check"></i>
                        @elseif ($item->getIcon())
                            <i class="{{ $item->getIcon() }}"></i>
                        @else
                            {{ $item->getStep() }}
                        @endif
                    </span>
                    <span class="mt-1 text-sm {{ $isActive ? 'font-semibold text-primary-600' : 'text-gray-500 dark:text-gray-400' }}">
                        {{ $item->getLabel() }}
                    </span>
                    @if ($item->getSubLabel())
                        <span class="text-gray-500 dark:text-gray-400" style="font-size:.7rem;">{{ $item->getSubLabel() }}</span>
                    @endif

                    @if ($item->getHref() && ! $isDisabled && ! $isLocked)
                        </a>
                    @else
                        </span>
                    @endif
                </li>
            @endif
        @endforeach
    </ol>
        </div>
</x-architect::navigator.shell>
