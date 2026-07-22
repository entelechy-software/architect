@php
    /** @var \Entelechy\Architect\Forms\Fields\DisplayField $field */
    $displayValue = $field->getDefault();
    if ($field->isRedacted()) {
        $bypass = $field->getRedactUnlessPermission();
        $isBypassed = $bypass !== null
            && app(\Entelechy\Architect\Contracts\PermissionResolver::class)->can(auth()->user(), $bypass);
        if (! $isBypassed) {
            $displayValue = $field->applyRedaction((string) $displayValue);
        }
    }
@endphp
<div class="arch-field" data-type="display">
    <span class="arch-field__label">
        {{ $field->getLabel() }}
        @if ($field->getTooltip() !== null)
            <i
                class="fas fa-circle-info ml-1 text-xs text-gray-400 dark:text-gray-500 cursor-help"
                title="{{ $field->getTooltip() }}"
            ></i>
        @endif
    </span>
    <div class="arch-field__control arch-field__static">{{ $displayValue }}</div>

    @if ($field->getHint() !== null)
        <div class="arch-field__hint">{{ $field->getHint() }}</div>
    @endif
</div>
