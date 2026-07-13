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
    <span class="arch-field__label">{{ $field->getLabel() }}</span>
    <div class="arch-field__control arch-field__static">{{ $displayValue }}</div>

    @if ($field->getHint() !== null)
        <div class="arch-field__hint">{{ $field->getHint() }}</div>
    @endif
</div>
